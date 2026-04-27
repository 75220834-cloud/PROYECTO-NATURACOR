<?php

namespace App\Services\Recommendation;

use App\Models\ClientePerfilAfinidad;
use App\Models\ClienteHistorialPerfil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Construye el perfil de afinidad cliente ↔ enfermedades a partir de:
 * - ventas completadas y detalle_ventas con producto_id;
 * - grafo producto ↔ enfermedad (tabla pivote enfermedad_producto).
 *
 * No es diagnóstico médico: solo señal estadística de comportamiento de compra.
 */
class PerfilSaludService
{
    private function cacheKeyPerfilVacio(int $clienteId): string
    {
        return 'recommendaciones.perfil_vacio_comprobado.'.$clienteId;
    }

    /**
     * Recalcula el perfil solo si venció la validez (computed_at + horas) o no hay datos persistidos.
     * Evita trabajo pesado en cada request al motor de recomendación.
     *
     * @param  bool  $forzar  Si true, ignora validez y vuelve a calcular (ej. ?refresh=1).
     */
    public function asegurarPerfilReciente(int $clienteId, bool $forzar = false): void
    {
        if ($forzar) {
            Cache::forget($this->cacheKeyPerfilVacio($clienteId));
            $this->reconstruirPerfil($clienteId);

            return;
        }

        if (! $this->debeReconstruirPerfil($clienteId)) {
            return;
        }

        $this->reconstruirPerfil($clienteId);
    }

    /**
     * Indica si hace falta volver a leer ventas y escribir cliente_perfil_afinidad.
     */
    public function debeReconstruirPerfil(int $clienteId): bool
    {
        $horasValidez = max(1, (int) config('recommendaciones.perfil_horas_validez', 6));
        $ultimaComputacion = ClientePerfilAfinidad::where('cliente_id', $clienteId)->max('computed_at');

        if ($ultimaComputacion === null) {
            $marcadorVacio = Cache::get($this->cacheKeyPerfilVacio($clienteId));
            if ($marcadorVacio && Carbon::parse($marcadorVacio)->addHours($horasValidez)->isFuture()) {
                return false;
            }

            return true;
        }

        return Carbon::parse($ultimaComputacion)->addHours($horasValidez)->isPast();
    }

    /**
     * Recalcula y persiste todas las filas de cliente_perfil_afinidad para un cliente.
     * Operación síncrona e idempotente (borra filas previas del cliente e inserta de nuevo).
     *
     * Fuentes de señal combinadas:
     *  1. Observada: ventas completadas + grafo enfermedad_producto (con decaimiento temporal).
     *  2. Declarada: registros en cliente_padecimientos (BUG 2 FIX) — inyectan un score
     *     base configurable como FLOOR para que clientes nuevos con diagnóstico previo
     *     reciban recomendaciones útiles aunque nunca hayan comprado.
     */
    public function reconstruirPerfil(int $clienteId): void
    {
        $ventanaDias = max(1, (int) config('recommendaciones.ventana_dias', 365));
        $lambda = (float) config('recommendaciones.decaimiento_lambda', 0.008);
        $padecimientoScoreBase = max(
            0.0,
            min(1.0, (float) config('recommendaciones.padecimiento_score_base', 0.80))
        );

        // [BUG 2 FIX] Padecimientos declarados explícitamente por el cliente.
        // Filtrados por enfermedad activa y no eliminada para mantener consistencia
        // con el resto del motor.
        $padecimientos = DB::table('cliente_padecimientos as cp')
            ->join('enfermedades as e', 'cp.enfermedad_id', '=', 'e.id')
            ->where('cp.cliente_id', $clienteId)
            ->where('e.activa', true)
            ->whereNull('e.deleted_at')
            ->get(['cp.enfermedad_id', 'cp.created_at as fecha_declarado']);

        /** @var list<int> $declaradoIds */
        $declaradoIds = $padecimientos
            ->pluck('enfermedad_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $fechaDeclaradoPorEnf = $padecimientos->keyBy('enfermedad_id');

        $lineas = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.cliente_id', $clienteId)
            ->where('ventas.estado', 'completada')
            ->whereNotNull('detalle_ventas.producto_id')
            ->where('ventas.created_at', '>=', now()->subDays($ventanaDias))
            ->orderBy('ventas.id')
            ->get([
                'detalle_ventas.producto_id',
                'detalle_ventas.cantidad',
                'ventas.created_at as fecha_venta',
            ]);

        /**
         * Acumulador por enfermedad:
         * - raw: suma de contribuciones ponderadas por cantidad, decaimiento temporal y 1/grado(producto)
         * - evidencias: número de líneas de detalle que aportaron señal hacia esa enfermedad
         * - ultima: fecha de la última señal (venta o declaración) que refuerza esa enfermedad
         *
         * @var array<int, array{raw: float, evidencias: int, ultima: ?string}> $acumulado
         */
        $acumulado = [];

        // === 1) Señal observada (compras × recetario) ===
        $productoIds = $lineas->pluck('producto_id')->unique()->filter()->values();
        if ($productoIds->isNotEmpty()) {
            $pivotRows = DB::table('enfermedad_producto')
                ->join('enfermedades', 'enfermedad_producto.enfermedad_id', '=', 'enfermedades.id')
                ->whereIn('enfermedad_producto.producto_id', $productoIds)
                ->where('enfermedades.activa', true)
                ->whereNull('enfermedades.deleted_at')
                ->get(['enfermedad_producto.producto_id', 'enfermedad_producto.enfermedad_id']);

            if ($pivotRows->isNotEmpty()) {
                /** @var array<int, list<int>> $productoAEnfermedades */
                $productoAEnfermedades = [];
                foreach ($pivotRows as $row) {
                    $pid = (int) $row->producto_id;
                    $eid = (int) $row->enfermedad_id;
                    $productoAEnfermedades[$pid] ??= [];
                    $productoAEnfermedades[$pid][] = $eid;
                }

                /** @var array<int, int> $gradoProducto número de enfermedades enlazadas al producto */
                $gradoProducto = [];
                foreach ($productoAEnfermedades as $pid => $lista) {
                    $gradoProducto[$pid] = max(1, count(array_unique($lista)));
                }

                foreach ($lineas as $linea) {
                    $pid = (int) $linea->producto_id;
                    if (! isset($productoAEnfermedades[$pid])) {
                        continue;
                    }

                    $fechaVenta = Carbon::parse($linea->fecha_venta);
                    $dias = $fechaVenta->diffInDays(now());
                    $pesoLinea = (float) $linea->cantidad * exp(-$lambda * $dias);
                    $grado = $gradoProducto[$pid] ?? 1;
                    $contribucionBase = $pesoLinea / $grado;

                    foreach (array_unique($productoAEnfermedades[$pid]) as $eid) {
                        if (! isset($acumulado[$eid])) {
                            $acumulado[$eid] = ['raw' => 0.0, 'evidencias' => 0, 'ultima' => null];
                        }
                        $acumulado[$eid]['raw'] += $contribucionBase;
                        $acumulado[$eid]['evidencias'] += 1;
                        $fechaStr = $fechaVenta->toDateTimeString();
                        if ($acumulado[$eid]['ultima'] === null || $fechaStr > $acumulado[$eid]['ultima']) {
                            $acumulado[$eid]['ultima'] = $fechaStr;
                        }
                    }
                }
            }
        }

        // === 2) [BUG 2 FIX] Señal declarada (cliente_padecimientos) ===
        // Si la enfermedad ya tiene señal observada, NO se sobreescribe el raw
        // (evita inflar artificialmente compras reales). El FLOOR de score
        // se aplica al final para garantizar visibilidad mínima.
        // Si la enfermedad NO tiene compras, se inicializa con raw = score base
        // y evidencia_count = 0 (marcador de "declarado puro").
        foreach ($declaradoIds as $eid) {
            if (! isset($acumulado[$eid])) {
                $fechaDecl = $fechaDeclaradoPorEnf->get($eid)?->fecha_declarado
                    ?? now()->toDateTimeString();
                $acumulado[$eid] = [
                    'raw' => $padecimientoScoreBase,
                    'evidencias' => 0,
                    'ultima' => (string) $fechaDecl,
                ];
            }
        }

        // === 3) Si no hay señal de ningún tipo: limpiar y marcar vacío ===
        if ($acumulado === []) {
            ClientePerfilAfinidad::where('cliente_id', $clienteId)->delete();
            $this->marcarPerfilVacioComprobado($clienteId);

            return;
        }

        $rawVals = array_column($acumulado, 'raw');
        $minRaw = min($rawVals);
        $maxRaw = max($rawVals);

        $now = now();
        $declaradoLookup = array_flip($declaradoIds);

        DB::transaction(function () use (
            $clienteId,
            $acumulado,
            $minRaw,
            $maxRaw,
            $now,
            $declaradoLookup,
            $padecimientoScoreBase
        ) {
            ClientePerfilAfinidad::where('cliente_id', $clienteId)->delete();

            $historialRows = [];
            foreach ($acumulado as $enfermedadId => $datos) {
                $scoreNorm = $this->normalizarScore($datos['raw'], $minRaw, $maxRaw);

                // [BUG 2 FIX] FLOOR: garantiza score mínimo para padecimientos declarados.
                // Sin esto, un cliente con muchas compras de otra enfermedad podría
                // hundir el score normalizado del padecimiento declarado a 0.
                if (isset($declaradoLookup[$enfermedadId]) && $scoreNorm < $padecimientoScoreBase) {
                    $scoreNorm = $padecimientoScoreBase;
                }

                ClientePerfilAfinidad::create([
                    'cliente_id' => $clienteId,
                    'enfermedad_id' => $enfermedadId,
                    'score' => $scoreNorm,
                    'evidencia_count' => $datos['evidencias'],
                    'ultima_evidencia_at' => $datos['ultima'],
                    'computed_at' => $now,
                ]);

                $historialRows[] = [
                    'cliente_id' => $clienteId,
                    'enfermedad_id' => $enfermedadId,
                    'score' => $scoreNorm,
                    'evidencia_count' => $datos['evidencias'],
                    'fecha_computacion' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($historialRows !== []) {
                foreach (array_chunk($historialRows, 200) as $chunk) {
                    ClienteHistorialPerfil::insert($chunk);
                }
            }
        });

        Cache::forget($this->cacheKeyPerfilVacio($clienteId));
    }

    /**
     * Evita recalcular en bucle cuando el cliente no tiene señal útil (sin líneas o sin pivote recetario).
     */
    private function marcarPerfilVacioComprobado(int $clienteId): void
    {
        $horas = max(1, (int) config('recommendaciones.perfil_horas_validez', 6));
        Cache::put($this->cacheKeyPerfilVacio($clienteId), now()->toIso8601String(), now()->addHours($horas));
    }

    /**
     * Normaliza el score bruto al intervalo [0, 1] dentro del mismo cliente.
     */
    private function normalizarScore(float $raw, float $minRaw, float $maxRaw): float
    {
        if ($maxRaw <= 0) {
            return 0.0;
        }
        if (abs($maxRaw - $minRaw) < 1e-12) {
            return 1.0;
        }

        return ($raw - $minRaw) / ($maxRaw - $minRaw);
    }
}
