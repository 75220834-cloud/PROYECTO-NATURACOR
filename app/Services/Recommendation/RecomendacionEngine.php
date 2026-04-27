<?php

namespace App\Services\Recommendation;

use App\Models\Cliente;
use App\Models\ClientePerfilAfinidad;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Motor de recomendación híbrido:
 *   1) CONTENIDO  — productos ligados a enfermedades fuertes en el perfil del cliente.
 *   2) TENDENCIA  — productos más vendidos recientemente en la sucursal.
 *   3) COLABORATIVO (item-item) — vecinos en la matriz de co-ocurrencia respecto al
 *      carrito actual del POS (cross-sell). Solo se activa cuando $cestaActual no está
 *      vacío; en ese caso la respuesta NO se cachea (la cesta cambia constantemente).
 *
 * Optimización: perfil con validez temporal; respuesta cacheada unos minutos (ver config)
 * SOLO cuando no hay cesta activa.
 */
class RecomendacionEngine
{
    public function __construct(
        private readonly PerfilSaludService $perfilSalud,
        private readonly CoocurrenciaService $coocurrencia,
    ) {}

    /**
     * @param  list<int>  $cestaActual  Producto IDs en el carrito del POS.
     * @return array{
     *   cliente_id:int,
     *   perfil_filas:int,
     *   items:list<array{
     *     producto:array<string,mixed>,
     *     score_final:float,
     *     componente_perfil:float,
     *     componente_trending:float,
     *     componente_coocurrencia:float,
     *     razones:list<string>
     *   }>,
     *   meta:array{
     *     respuesta_desde_cache:bool,
     *     perfil_recalculado:?bool,
     *     reco_sesion_id:?string,
     *     cesta_size:int,
     *     coocurrencia_activa:bool
     *   }
     * }
     */
    public function recomendar(
        Cliente $cliente,
        ?int $sucursalId,
        int $limite,
        bool $forzar = false,
        array $cestaActual = [],
    ): array {
        $cestaActual = array_values(array_unique(array_filter(array_map('intval', $cestaActual), fn ($id) => $id > 0)));
        $coocurrenciaActiva = $cestaActual !== [];

        $cacheKey = $this->cacheKeyRecomendacion((int) $cliente->id, $sucursalId, $limite);
        $minutos = max(1, (int) config('recommendaciones.cache_minutos', 10));

        // Bypass total de caché cuando hay cesta: el carrito cambia continuamente y
        // cachear por (cliente,sucursal,limite,carrito) explotaría el almacenamiento.
        if (! $coocurrenciaActiva && ! $forzar) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['meta'] = array_merge($cached['meta'] ?? [], [
                    'respuesta_desde_cache' => true,
                    'perfil_recalculado' => null,
                ]);

                return $cached;
            }
        } elseif ($forzar) {
            Cache::forget($cacheKey);
        }

        $clienteId = (int) $cliente->id;
        $necesitabaPerfil = $forzar || $this->perfilSalud->debeReconstruirPerfil($clienteId);
        $this->perfilSalud->asegurarPerfilReciente($clienteId, $forzar);

        $filasPerfil = ClientePerfilAfinidad::query()
            ->where('cliente_id', $cliente->id)
            ->with('enfermedad:id,nombre,categoria')
            ->orderByDesc('score')
            ->get();

        $topK = max(1, (int) config('recommendaciones.top_enfermedades', 10));
        $topEnfermedadIds = $filasPerfil->take($topK)->pluck('enfermedad_id')->all();
        /** @var array<int, ClientePerfilAfinidad> $perfilPorEnfermedad */
        $perfilPorEnfermedad = $filasPerfil->keyBy('enfermedad_id')->all();

        $trending = $this->unidadesVendidasRecientesPorSucursal($sucursalId);
        $maxTrend = (float) max($trending->max() ?: 0, 1);

        $pesoPerfil = (float) config('recommendaciones.peso_perfil', 1.0);
        $pesoTrend = (float) config('recommendaciones.peso_trending', 0.45);
        $pesoCooc = (float) config('recommendaciones.cooccurrencia.peso_en_fusion', 0.35);
        $boostCarrito = (float) config('recommendaciones.cooccurrencia.boost_carrito', 1.5);
        $diasTrend = (int) config('recommendaciones.trending_dias', 14);
        $diasExclusion = max(0, (int) config('recommendaciones.excluir_comprados_dias', 30));
        $compradosRecientes = $this->productosCompradosRecientemente((int) $cliente->id, $sucursalId, $diasExclusion);

        // === Señal colaborativa (3) ===
        // vecinos[producto_id] => score Jaccard|NPMI ∈ [-1,1]; por convención solo
        // consideramos vecinos con score >= 0 (excluye anti-correlaciones).
        $vecinos = collect();
        $nombresCarrito = [];
        if ($coocurrenciaActiva) {
            $vecinos = $this->coocurrencia->vecindarioBatch(
                $cestaActual,
                limitePorBase: max(1, (int) config('recommendaciones.cooccurrencia.top_k_persistir', 50)),
                scoreMinimo: 0.0,
            );
            // Etiqueta humana de los productos del carrito (para razones explicables)
            $nombresCarrito = Producto::query()
                ->whereIn('id', $cestaActual)
                ->pluck('nombre', 'id')
                ->all();
        }

        /** @var array<int, array{score: float, perfil: float, trend: float, cooc: float, razones: list<string>,enfermedad_principal:?int}> $acumulado */
        $acumulado = [];

        // --- 1) Candidatos por perfil (contenido) ---
        if ($topEnfermedadIds !== []) {
            $productosContenido = Producto::query()
                ->where('activo', true)
                ->where('stock', '>', 0)
                ->when($compradosRecientes !== [], fn ($q) => $q->whereNotIn('id', $compradosRecientes))
                ->when($sucursalId, function ($q) use ($sucursalId) {
                    $q->where(function ($q2) use ($sucursalId) {
                        $q2->whereNull('sucursal_id')
                            ->orWhere('sucursal_id', $sucursalId);
                    });
                })
                ->whereHas('enfermedades', function ($q) use ($topEnfermedadIds) {
                    $q->whereIn('enfermedades.id', $topEnfermedadIds)
                        ->where('enfermedades.activa', true)
                        ->whereNull('enfermedades.deleted_at');
                })
                ->with('enfermedades')
                ->get();

            foreach ($productosContenido as $producto) {
                $mejorEnfId = null;
                $mejorScorePerfil = 0.0;
                foreach ($producto->enfermedades as $enf) {
                    $fila = $perfilPorEnfermedad[$enf->id] ?? null;
                    if ($fila && (float) $fila->score > $mejorScorePerfil) {
                        $mejorScorePerfil = (float) $fila->score;
                        $mejorEnfId = (int) $enf->id;
                    }
                }

                $unidades = (int) ($trending[$producto->id] ?? 0);
                $trendNorm = log(1 + $unidades) / log(1 + $maxTrend);

                $compPerfil = $mejorScorePerfil * 100;
                $compTrend = $trendNorm * 100;
                $compCooc = isset($vecinos[$producto->id]) ? max(0.0, (float) $vecinos[$producto->id]) * 100 : 0.0;

                $scoreFinal = ($pesoPerfil * $compPerfil) + ($pesoTrend * $compTrend) + ($pesoCooc * $compCooc);
                if ($compCooc > 0.0) {
                    // Boost multiplicativo: el producto aparece tanto por perfil como por co-ocurrencia
                    // con el carrito → señal redundante, refuerza la confianza.
                    $scoreFinal *= $boostCarrito;
                }

                $razones = [];
                if ($mejorEnfId !== null && $mejorScorePerfil > 0) {
                    $filaTop = $perfilPorEnfermedad[$mejorEnfId];
                    $nombreEnf = $filaTop->enfermedad->nombre ?? 'Condición del recetario';
                    $categoria = $filaTop->enfermedad->categoria ?? null;
                    $pct = round($mejorScorePerfil * 100);
                    $linea1 = "Encaja con tu historial: en el recetario aparece «{$nombreEnf}» con peso relativo {$pct}% en tu perfil (no es un diagnóstico; solo refleja compras anteriores).";
                    $razones[] = $categoria
                        ? "{$linea1} Categoría recetario: {$categoria}."
                        : $linea1;
                }
                if ($unidades > 0) {
                    $razones[] = $this->textoTendenciaSucursal($unidades, $diasTrend, $sucursalId);
                }
                if ($compCooc > 0.0) {
                    $razones[] = $this->textoCoocurrencia((int) $producto->id, $cestaActual, $nombresCarrito, $compCooc);
                }

                $acumulado[$producto->id] = [
                    'score' => $scoreFinal,
                    'perfil' => $compPerfil,
                    'trend' => $compTrend,
                    'cooc' => $compCooc,
                    'enfermedad_principal' => $mejorEnfId,
                    'razones' => $razones !== [] ? $razones : [
                        'Sugerido por el recetario NATURACOR según las condiciones vinculadas a tus compras.',
                    ],
                ];
            }
        }

        // --- 2) Refuerzo por tendencia (productos populares en la sucursal aún no incluidos) ---
        foreach ($trending as $productoId => $unidades) {
            if (isset($acumulado[$productoId])) {
                continue;
            }
            if (in_array((int) $productoId, $compradosRecientes, true)) {
                continue;
            }

            $producto = Producto::query()
                ->where('id', $productoId)
                ->where('activo', true)
                ->where('stock', '>', 0)
                ->when($sucursalId, function ($q) use ($sucursalId) {
                    $q->where(function ($q2) use ($sucursalId) {
                        $q2->whereNull('sucursal_id')
                            ->orWhere('sucursal_id', $sucursalId);
                    });
                })
                ->first();

            if (! $producto) {
                continue;
            }

            $unidades = (int) $unidades;
            $trendNorm = log(1 + $unidades) / log(1 + $maxTrend);
            $compTrend = $trendNorm * 100;
            $compCooc = isset($vecinos[$producto->id]) ? max(0.0, (float) $vecinos[$producto->id]) * 100 : 0.0;

            $scoreFinal = ($pesoTrend * $compTrend) + ($pesoCooc * $compCooc);
            if ($compCooc > 0.0) {
                $scoreFinal *= $boostCarrito;
            }

            $razones = [
                'Sugerencia por demanda en tienda: '.$this->textoTendenciaSucursal($unidades, $diasTrend, $sucursalId),
            ];
            if ($compCooc > 0.0) {
                $razones[] = $this->textoCoocurrencia((int) $producto->id, $cestaActual, $nombresCarrito, $compCooc);
            }

            $acumulado[$productoId] = [
                'score' => $scoreFinal,
                'perfil' => 0.0,
                'trend' => $compTrend,
                'cooc' => $compCooc,
                'enfermedad_principal' => null,
                'razones' => $razones,
            ];
        }

        // --- 3) Vecinos colaborativos puros (productos co-comprados con el carrito,
        //        que no entraron por perfil ni por trending). Permite cross-sell genuino. ---
        if ($coocurrenciaActiva && $vecinos->isNotEmpty()) {
            $faltantes = $vecinos->keys()
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => isset($acumulado[$id]) || in_array($id, $compradosRecientes, true))
                ->values();

            if ($faltantes->isNotEmpty()) {
                $productosVecinos = Producto::query()
                    ->whereIn('id', $faltantes->all())
                    ->where('activo', true)
                    ->where('stock', '>', 0)
                    ->when($sucursalId, function ($q) use ($sucursalId) {
                        $q->where(function ($q2) use ($sucursalId) {
                            $q2->whereNull('sucursal_id')
                                ->orWhere('sucursal_id', $sucursalId);
                        });
                    })
                    ->get();

                foreach ($productosVecinos as $producto) {
                    $score = max(0.0, (float) $vecinos[$producto->id]);
                    $compCooc = $score * 100;
                    if ($compCooc <= 0.0) {
                        continue;
                    }
                    $scoreFinal = ($pesoCooc * $compCooc) * $boostCarrito;

                    $acumulado[$producto->id] = [
                        'score' => $scoreFinal,
                        'perfil' => 0.0,
                        'trend' => 0.0,
                        'cooc' => $compCooc,
                        'enfermedad_principal' => null,
                        'razones' => [
                            $this->textoCoocurrencia((int) $producto->id, $cestaActual, $nombresCarrito, $compCooc),
                        ],
                    ];
                }
            }
        }

        $ordenados = $this->seleccionarDiverso(collect($acumulado), $limite);
        $productos = Producto::query()
            ->whereIn('id', $ordenados->keys()->all())
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($ordenados as $productoId => $meta) {
            $p = $productos->get($productoId);
            if (! $p) {
                continue;
            }
            $items[] = [
                'producto' => $this->serializarProducto($p),
                'score_final' => round($meta['score'], 2),
                'componente_perfil' => round($meta['perfil'], 2),
                'componente_trending' => round($meta['trend'], 2),
                'componente_coocurrencia' => round($meta['cooc'] ?? 0.0, 2),
                'razones' => $meta['razones'],
            ];
        }

        $payload = [
            'cliente_id' => $clienteId,
            'perfil_filas' => $filasPerfil->count(),
            'items' => $items,
            'meta' => [
                'respuesta_desde_cache' => false,
                'perfil_recalculado' => $necesitabaPerfil,
                'reco_sesion_id' => $items !== [] ? (string) Str::uuid() : null,
                'cesta_size' => count($cestaActual),
                'coocurrencia_activa' => $coocurrenciaActiva,
            ],
        ];

        // No se cachea cuando hay cesta: la dimensión "carrito" es demasiado volátil
        // y combinatoria (ver doc del bypass arriba).
        if (! $coocurrenciaActiva) {
            Cache::put($cacheKey, $payload, now()->addMinutes($minutos));
        }

        return $payload;
    }

    /**
     * @param  Collection<int, array{score: float, perfil: float, trend: float, razones: list<string>,enfermedad_principal:?int}>  $acumulado
     * @return Collection<int, array{score: float, perfil: float, trend: float, razones: list<string>,enfermedad_principal:?int}>
     */
    private function seleccionarDiverso(Collection $acumulado, int $limite): Collection
    {
        $ordenados = $acumulado->sortByDesc('score');
        if ($ordenados->isEmpty()) {
            return collect();
        }

        $seleccion = collect();
        $enfermedadesUsadas = [];

        foreach ($ordenados as $productoId => $meta) {
            if ($seleccion->count() >= $limite) {
                break;
            }
            $eid = $meta['enfermedad_principal'];
            if ($eid !== null && in_array($eid, $enfermedadesUsadas, true)) {
                continue;
            }
            $seleccion->put($productoId, $meta);
            if ($eid !== null) {
                $enfermedadesUsadas[] = $eid;
            }
        }

        if ($seleccion->count() < $limite) {
            foreach ($ordenados as $productoId => $meta) {
                if ($seleccion->count() >= $limite) {
                    break;
                }
                if (! $seleccion->has($productoId)) {
                    $seleccion->put($productoId, $meta);
                }
            }
        }

        return $seleccion;
    }

    /**
     * @return list<int>
     */
    private function productosCompradosRecientemente(int $clienteId, ?int $sucursalId, int $dias): array
    {
        if ($dias <= 0) {
            return [];
        }

        return DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.cliente_id', $clienteId)
            ->where('ventas.estado', 'completada')
            ->whereNotNull('detalle_ventas.producto_id')
            ->where('ventas.created_at', '>=', now()->subDays($dias))
            ->when($sucursalId !== null, fn ($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->select('detalle_ventas.producto_id')
            ->distinct()
            ->pluck('detalle_ventas.producto_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * [BUG 4 FIX] Invalida toda la caché de recomendaciones para un cliente,
     * sin importar la sucursal o el límite con que se haya cacheado.
     *
     * Implementación: técnica de "versioned cache keys". Bumpear la versión
     * del cliente cambia la clave que el engine genera, dejando huérfanas las
     * entradas anteriores (que expiran solas por TTL). Funciona en cualquier
     * driver de caché soportado por Laravel (file, redis, database, etc.).
     *
     * Llamar después de:
     *  - guardar/actualizar padecimientos del cliente;
     *  - reconstrucción manual del perfil;
     *  - cualquier evento que invalide datos previos del recomendador para ese cliente.
     */
    public function invalidarCacheCliente(int $clienteId): void
    {
        Cache::forever(
            $this->cacheKeyClienteVersion($clienteId),
            (string) microtime(true)
        );
    }

    private function cacheKeyClienteVersion(int $clienteId): string
    {
        return "recommendaciones.cliente_version.{$clienteId}";
    }

    private function cacheKeyRecomendacion(int $clienteId, ?int $sucursalId, int $limite): string
    {
        $s = $sucursalId === null ? 'null' : (string) $sucursalId;
        $v = (string) Cache::get($this->cacheKeyClienteVersion($clienteId), '0');

        return "recommendaciones.json.v1.{$clienteId}.{$s}.{$limite}.v{$v}";
    }

    private function textoTendenciaSucursal(int $unidades, int $diasTrend, ?int $sucursalId): string
    {
        if ($sucursalId !== null) {
            return "En esta sucursal se vendieron {$unidades} unidad(es) en los últimos {$diasTrend} días (suma de todas las boletas registradas en esta tienda).";
        }

        return "Se vendieron {$unidades} unidad(es) en los últimos {$diasTrend} días (vista global: tu usuario no tiene sucursal asignada).";
    }

    /**
     * Construye la razón "🛒 Clientes que compraron X también compraron Y", buscando
     * el producto del carrito con MAYOR co-ocurrencia hacia el candidato (producto-puente).
     *
     * @param  list<int>             $cesta
     * @param  array<int,string>     $nombresCarrito  producto_id => nombre
     */
    private function textoCoocurrencia(int $candidatoId, array $cesta, array $nombresCarrito, float $compCooc): string
    {
        $puenteId = null;
        $mejorScore = -INF;

        // Buscar entre los productos del carrito cuál tiene la mayor coocurrencia con el candidato.
        // Coste: máximo |cesta| filas → barato (usuario rara vez tiene >20 ítems en POS).
        $rows = DB::table('producto_coocurrencias')
            ->where(function ($q) use ($candidatoId, $cesta) {
                $q->where(function ($q1) use ($candidatoId, $cesta) {
                    $q1->where('producto_a_id', $candidatoId)->whereIn('producto_b_id', $cesta);
                })->orWhere(function ($q2) use ($candidatoId, $cesta) {
                    $q2->where('producto_b_id', $candidatoId)->whereIn('producto_a_id', $cesta);
                });
            })
            ->orderByDesc('score')
            ->limit(1)
            ->get(['producto_a_id', 'producto_b_id', 'score', 'co_count']);

        if ($rows->isNotEmpty()) {
            $r = $rows->first();
            $puenteId = ((int) $r->producto_a_id === $candidatoId)
                ? (int) $r->producto_b_id
                : (int) $r->producto_a_id;
            $mejorScore = (float) $r->score;
        }

        $pct = (int) round(min(100, max(0, $compCooc)));

        if ($puenteId !== null && isset($nombresCarrito[$puenteId])) {
            $nombrePuente = $nombresCarrito[$puenteId];

            return "🛒 Clientes que llevaron «{$nombrePuente}» también llevaron este producto (similitud {$pct}%).";
        }

        return "🛒 Combinación frecuente con tu carrito actual (similitud {$pct}%).";
    }

    /**
     * Unidades vendidas solo de ventas `completadas` de la sucursal indicada.
     * Si no hay sucursal, se consideran todas las sucursales (compatibilidad).
     *
     * @return Collection<int, int> producto_id => unidades
     */
    private function unidadesVendidasRecientesPorSucursal(?int $sucursalId): Collection
    {
        $dias = max(1, (int) config('recommendaciones.trending_dias', 14));

        return DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereNotNull('detalle_ventas.producto_id')
            ->where('ventas.created_at', '>=', now()->subDays($dias))
            ->when($sucursalId !== null, fn ($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->groupBy('detalle_ventas.producto_id')
            ->selectRaw('detalle_ventas.producto_id as pid, SUM(detalle_ventas.cantidad) as u')
            ->orderByDesc('u')
            ->limit(40)
            ->pluck('u', 'pid');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarProducto(Producto $p): array
    {
        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'precio' => (float) $p->precio,
            'stock' => (int) $p->stock,
            'tipo' => $p->tipo,
            'sucursal_id' => $p->sucursal_id,
            'imagen' => $p->imagen,
        ];
    }
}
