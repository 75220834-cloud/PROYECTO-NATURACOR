<?php

namespace App\Services\Analytics;

use App\Models\Enfermedad;
use App\Models\Sucursal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Bloque 6 — Mapa de calor de enfermedades.
 *
 * Construye una matriz Enfermedades × Sucursales con tres modos de evidencia:
 *   - DECLARADA: clientes con padecimiento auto-reportado (`cliente_padecimientos`).
 *   - OBSERVADA: clientes con afinidad inferida >= umbral (`cliente_perfil_afinidad`).
 *   - COMBINADA: unión de ambos conjuntos (un cliente declarado y observado cuenta 1).
 *
 * Cada celda almacena CLIENTES ÚNICOS, NO ventas. Esto evita que un cliente
 * frecuente de NopalCordial infle artificialmente la celda "Diabetes × Jauja".
 *
 * Bonus académico: clusterFilas() implementa un clustering aglomerativo
 * single-linkage con distancia coseno entre filas para reordenar la matriz
 * y revelar grupos de enfermedades co-ocurrentes (ej. "digestivo" y
 * "estreñimiento" caen cercanas si comparten clientes en mismas sucursales).
 *
 * Limitación a documentar en tesis:
 *   - El clustering es jerárquico simple (no k-means ni Louvain): apropiado
 *     para datasets pequeños (<200 enfermedades) que es el caso de uso real.
 *   - La sucursal del cliente se infiere desde sus VENTAS (un cliente puede
 *     aparecer en múltiples sucursales si compró en ambas). El modelo Cliente
 *     no tiene `sucursal_id` propio.
 */
class HeatmapEnfermedadesService
{
    public const FUENTE_DECLARADA  = 'declarada';
    public const FUENTE_OBSERVADA  = 'observada';
    public const FUENTE_COMBINADA  = 'combinada';

    public const ORDEN_TOTAL       = 'total';        // por suma de fila descendente
    public const ORDEN_ALFABETICO  = 'alfabetico';
    public const ORDEN_CLUSTER     = 'cluster';      // dendrograma single-linkage

    /**
     * Devuelve la matriz lista para el dashboard:
     *   [
     *     'enfermedades' => [{id,nombre,categoria}],
     *     'sucursales'   => [{id,nombre}],
     *     'celdas'       => [enfId => [sucId => int]],
     *     'fila_total'   => [enfId => int],   // suma por enfermedad
     *     'col_total'    => [sucId => int],   // suma por sucursal
     *     'max_celda'    => int,              // para normalizar el degradado
     *     'orden_filas'  => [int, ...],       // ids de enfermedad en el orden a pintar
     *     'top_por_sucursal' => [sucId => [{nombre,total}]],
     *     'meta'         => ['fuente','dias','desde','hasta','umbral_score','total_clientes_unicos'],
     *   ]
     *
     * @param string $fuente self::FUENTE_*
     * @param int    $dias   Ventana hacia atrás en días (filtro de evidencia).
     * @param string $orden  self::ORDEN_*
     */
    public function construirMatriz(
        string $fuente = self::FUENTE_COMBINADA,
        int $dias = 90,
        string $orden = self::ORDEN_TOTAL,
        ?float $umbralScore = null,
        int $topPorSucursal = 3,
    ): array {
        $fuente = in_array($fuente, [self::FUENTE_DECLARADA, self::FUENTE_OBSERVADA, self::FUENTE_COMBINADA], true)
            ? $fuente : self::FUENTE_COMBINADA;
        $orden = in_array($orden, [self::ORDEN_TOTAL, self::ORDEN_ALFABETICO, self::ORDEN_CLUSTER], true)
            ? $orden : self::ORDEN_TOTAL;
        $dias = max(1, min(3650, $dias));
        $umbralScore = $umbralScore ?? (float) config('recommendaciones.heatmap_enfermedades.umbral_score', 0.20);
        $topPorSucursal = max(1, min(10, $topPorSucursal));

        $desde = CarbonImmutable::now()->subDays($dias)->startOfDay();

        // 1) Catálogos: solo entidades activas. Las inactivas no deberían
        //    aparecer en el panel del operador.
        $enfermedades = Enfermedad::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'categoria']);

        $sucursales = Sucursal::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        if ($enfermedades->isEmpty() || $sucursales->isEmpty()) {
            return $this->matrizVacia($fuente, $dias, $desde, $umbralScore);
        }

        // 2) Pares (enfermedad_id, sucursal_id, cliente_id) con UN registro
        //    por cliente para evitar contar dos veces si su padecimiento es
        //    declarado Y observado.
        $paresDeclarada = collect();
        $paresObservada = collect();

        if ($fuente === self::FUENTE_DECLARADA || $fuente === self::FUENTE_COMBINADA) {
            $paresDeclarada = $this->paresDesdeDeclarada($desde);
        }

        if ($fuente === self::FUENTE_OBSERVADA || $fuente === self::FUENTE_COMBINADA) {
            $paresObservada = $this->paresDesdeObservada($desde, $umbralScore);
        }

        // Unimos y deduplicamos en PHP (clave = "enf|suc|cli"). Esta es la
        // clave del "1 cliente, 1 voto" por celda.
        $set = [];
        foreach ($paresDeclarada as $r) {
            $set[(int) $r->enfermedad_id.'|'.(int) $r->sucursal_id.'|'.(int) $r->cliente_id] = $r;
        }
        foreach ($paresObservada as $r) {
            $set[(int) $r->enfermedad_id.'|'.(int) $r->sucursal_id.'|'.(int) $r->cliente_id] = $r;
        }

        // 3) Inicializa matriz a cero para cada celda visible.
        $celdas = [];
        $filaTotal = [];
        $colTotal = [];
        foreach ($enfermedades as $e) {
            $celdas[$e->id] = [];
            $filaTotal[$e->id] = 0;
            foreach ($sucursales as $s) {
                $celdas[$e->id][$s->id] = 0;
                $colTotal[$s->id] = $colTotal[$s->id] ?? 0;
            }
        }

        $clientesUnicosGlobal = [];
        foreach ($set as $r) {
            $eId = (int) $r->enfermedad_id;
            $sId = (int) $r->sucursal_id;
            $cId = (int) $r->cliente_id;
            if (! isset($celdas[$eId][$sId])) {
                continue; // enfermedad o sucursal inactiva → fuera del panel
            }
            $celdas[$eId][$sId]++;
            $filaTotal[$eId]++;
            $colTotal[$sId] = ($colTotal[$sId] ?? 0) + 1;
            $clientesUnicosGlobal[$cId] = true;
        }

        // 4) Ordenar filas según el modo solicitado.
        $idsOrdenados = match ($orden) {
            self::ORDEN_ALFABETICO => $enfermedades->sortBy('nombre')->pluck('id')->values()->all(),
            self::ORDEN_CLUSTER    => $this->ordenarPorCluster($celdas, $enfermedades->pluck('id')->all()),
            default                => collect($filaTotal)->sortDesc()->keys()->all(),
        };

        // 5) Top-K por sucursal (insight de negocio: "en Jauja predomina X").
        $topPorSuc = [];
        $nombresEnf = $enfermedades->pluck('nombre', 'id');
        foreach ($sucursales as $s) {
            $col = collect($celdas)->mapWithKeys(fn ($fila, $eId) => [$eId => $fila[$s->id] ?? 0])
                ->filter(fn ($v) => $v > 0)
                ->sortDesc()
                ->take($topPorSucursal);

            $topPorSuc[$s->id] = $col->map(fn ($v, $eId) => [
                'enfermedad_id' => (int) $eId,
                'nombre'        => (string) ($nombresEnf[$eId] ?? "#{$eId}"),
                'total'         => (int) $v,
            ])->values()->all();
        }

        $maxCelda = 0;
        foreach ($celdas as $fila) {
            foreach ($fila as $v) {
                if ($v > $maxCelda) {
                    $maxCelda = $v;
                }
            }
        }

        return [
            'enfermedades' => $enfermedades->map(fn ($e) => [
                'id' => (int) $e->id,
                'nombre' => (string) $e->nombre,
                'categoria' => $e->categoria !== null ? (string) $e->categoria : null,
            ])->all(),
            'sucursales' => $sucursales->map(fn ($s) => [
                'id' => (int) $s->id,
                'nombre' => (string) $s->nombre,
            ])->all(),
            'celdas'      => $celdas,
            'fila_total'  => $filaTotal,
            'col_total'   => $colTotal,
            'max_celda'   => (int) $maxCelda,
            'orden_filas' => array_map('intval', $idsOrdenados),
            'top_por_sucursal' => $topPorSuc,
            'meta' => [
                'fuente'                => $fuente,
                'orden'                 => $orden,
                'dias'                  => $dias,
                'desde'                 => $desde->toDateString(),
                'hasta'                 => CarbonImmutable::now()->toDateString(),
                'umbral_score'          => $umbralScore,
                'total_clientes_unicos' => count($clientesUnicosGlobal),
            ],
        ];
    }

    /**
     * Exporta la matriz a CSV simple para descarga desde la vista.
     * Formato:
     *   enfermedad,categoria,Suc1,Suc2,...,total
     *
     * @return string Contenido CSV listo para `Response::make($csv, 200, [...])`.
     */
    public function exportarCsv(array $matriz): string
    {
        $headers = ['enfermedad', 'categoria'];
        foreach ($matriz['sucursales'] as $s) {
            $headers[] = $s['nombre'];
        }
        $headers[] = 'total';

        $lineas = [implode(',', array_map([$this, 'csvEscape'], $headers))];

        $idIndex = collect($matriz['enfermedades'])->keyBy('id');

        foreach ($matriz['orden_filas'] as $eId) {
            $enf = $idIndex->get($eId);
            if (! $enf) {
                continue;
            }
            $fila = [$enf['nombre'], $enf['categoria'] ?? ''];
            foreach ($matriz['sucursales'] as $s) {
                $fila[] = (int) ($matriz['celdas'][$eId][$s['id']] ?? 0);
            }
            $fila[] = (int) ($matriz['fila_total'][$eId] ?? 0);
            $lineas[] = implode(',', array_map([$this, 'csvEscape'], $fila));
        }

        return implode("\n", $lineas)."\n";
    }

    /**
     * Pares (enfermedad_id, sucursal_id, cliente_id) desde padecimientos
     * declarados, restringidos a clientes que tuvieron una venta completada
     * dentro de la ventana, en cada sucursal donde ocurrió.
     */
    private function paresDesdeDeclarada(CarbonImmutable $desde)
    {
        return DB::table('cliente_padecimientos as cp')
            ->join('ventas as v', 'v.cliente_id', '=', 'cp.cliente_id')
            ->where('v.estado', 'completada')
            ->where('v.created_at', '>=', $desde)
            ->whereNotNull('v.sucursal_id')
            ->select([
                'cp.enfermedad_id',
                'v.sucursal_id',
                'cp.cliente_id',
            ])
            ->distinct()
            ->get();
    }

    /**
     * Pares (enfermedad_id, sucursal_id, cliente_id) desde el perfil de
     * afinidad observada con score >= umbral, también ancladas a la sucursal
     * donde el cliente compró durante la ventana.
     */
    private function paresDesdeObservada(CarbonImmutable $desde, float $umbral)
    {
        return DB::table('cliente_perfil_afinidad as a')
            ->join('ventas as v', 'v.cliente_id', '=', 'a.cliente_id')
            ->where('a.score', '>=', $umbral)
            ->where('v.estado', 'completada')
            ->where('v.created_at', '>=', $desde)
            ->whereNotNull('v.sucursal_id')
            ->select([
                'a.enfermedad_id',
                'v.sucursal_id',
                'a.cliente_id',
            ])
            ->distinct()
            ->get();
    }

    /**
     * Clustering aglomerativo single-linkage sobre filas.
     *
     * Algoritmo:
     *   1. Cada enfermedad empieza siendo su propio cluster (vector = fila).
     *   2. Mientras haya más de 1 cluster, fusionamos el par más cercano según
     *      distancia coseno entre sus vectores promedio.
     *   3. El recorrido de fusiones determina un orden DFS-izquierda que es
     *      el dendrograma "aplanado" listo para pintar.
     *
     * Complejidad: O(n³) — aceptable para n ≤ ~200 enfermedades del recetario.
     * Para datasets más grandes habría que migrar a UPGMA con heap (no es el caso).
     *
     * @param  array<int,array<int,int>> $celdas
     * @param  array<int,int>            $idsBase
     * @return array<int,int>
     */
    public function ordenarPorCluster(array $celdas, array $idsBase): array
    {
        $vectores = [];
        foreach ($idsBase as $eId) {
            $vectores[$eId] = array_values($celdas[$eId] ?? []);
        }

        // Filas vacías van al final ordenadas por id; no aportan a la similitud.
        $vivos = [];
        $vacias = [];
        foreach ($vectores as $eId => $vec) {
            if (array_sum($vec) > 0) {
                $vivos[$eId] = ['ids' => [$eId], 'centroide' => $vec];
            } else {
                $vacias[] = $eId;
            }
        }

        if (count($vivos) <= 1) {
            return array_merge(array_keys($vivos), $vacias);
        }

        while (count($vivos) > 1) {
            // Buscar el par con menor distancia coseno.
            $mejor = ['a' => null, 'b' => null, 'd' => INF];
            $claves = array_keys($vivos);
            $n = count($claves);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $a = $claves[$i];
                    $b = $claves[$j];
                    $d = $this->distanciaCoseno($vivos[$a]['centroide'], $vivos[$b]['centroide']);
                    if ($d < $mejor['d']) {
                        $mejor = ['a' => $a, 'b' => $b, 'd' => $d];
                    }
                }
            }

            // Fusionar a y b → centroide promedio elemento a elemento.
            $a = $mejor['a'];
            $b = $mejor['b'];
            $centroide = [];
            $vecA = $vivos[$a]['centroide'];
            $vecB = $vivos[$b]['centroide'];
            $len = max(count($vecA), count($vecB));
            for ($k = 0; $k < $len; $k++) {
                $centroide[] = (($vecA[$k] ?? 0) + ($vecB[$k] ?? 0)) / 2;
            }
            $vivos[$a] = [
                'ids' => array_merge($vivos[$a]['ids'], $vivos[$b]['ids']),
                'centroide' => $centroide,
            ];
            unset($vivos[$b]);
        }

        // Solo queda 1 cluster con todos los ids dendrograma-ordenados.
        $orden = array_values($vivos)[0]['ids'];

        return array_merge($orden, $vacias);
    }

    private function distanciaCoseno(array $a, array $b): float
    {
        $len = max(count($a), count($b));
        $dot = 0.0;
        $sumA = 0.0;
        $sumB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $x = (float) ($a[$i] ?? 0);
            $y = (float) ($b[$i] ?? 0);
            $dot += $x * $y;
            $sumA += $x * $x;
            $sumB += $y * $y;
        }
        $denom = sqrt($sumA) * sqrt($sumB);
        if ($denom < 1e-12) {
            // Vector cero contra cualquier cosa: máxima distancia (1.0)
            // para que esos clusters se fusionen al final.
            return 1.0;
        }
        $sim = $dot / $denom;

        return max(0.0, min(2.0, 1 - $sim));
    }

    private function csvEscape(int|string $v): string
    {
        $s = (string) $v;
        if (str_contains($s, ',') || str_contains($s, '"') || str_contains($s, "\n")) {
            return '"'.str_replace('"', '""', $s).'"';
        }

        return $s;
    }

    private function matrizVacia(string $fuente, int $dias, CarbonImmutable $desde, float $umbralScore): array
    {
        return [
            'enfermedades' => [],
            'sucursales'   => [],
            'celdas'       => [],
            'fila_total'   => [],
            'col_total'    => [],
            'max_celda'    => 0,
            'orden_filas'  => [],
            'top_por_sucursal' => [],
            'meta' => [
                'fuente'                => $fuente,
                'orden'                 => self::ORDEN_TOTAL,
                'dias'                  => $dias,
                'desde'                 => $desde->toDateString(),
                'hasta'                 => CarbonImmutable::now()->toDateString(),
                'umbral_score'          => $umbralScore,
                'total_clientes_unicos' => 0,
            ],
        ];
    }
}
