<?php

namespace App\Services\Forecasting;

use App\Models\Producto;
use App\Models\ProductoDemandaSemana;
use App\Models\ProductoPrediccionDemanda;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Bloque 5 — DemandaForecastService.
 *
 * Implementa Suavizado Exponencial Simple (Simple Exponential Smoothing, SES)
 * para predecir las unidades vendidas de cada producto la próxima semana ISO.
 *
 *     S_t = α · Y_t + (1 − α) · S_{t-1},        S_0 = Y_0
 *     ŷ_{t+1} = S_t          (previsión one-step-ahead)
 *
 * con α ∈ (0,1). Valores típicos: 0.3-0.5 (más reactivo si α↑, más suave si α↓).
 *
 * Pipeline público:
 *   1. `materializarHistorico(...)` — recomputa `producto_demanda_semana` desde
 *      detalle_ventas. Idempotente: se puede correr cuantas veces quieras.
 *   2. `recomputarPredicciones(...)` — para cada (producto, sucursal) con
 *      historia suficiente, ajusta SES y persiste un snapshot en
 *      `producto_prediccion_demanda` con MAE, MAPE e intervalo naive 95%.
 *   3. `productosEnRiesgoStock(...)` — Top-K productos donde la predicción
 *      EXCEDE el stock actual (lo que el dashboard pinta como widget).
 *
 * Limitaciones académicas (mencionar honestamente en tesis):
 *   - SES asume nivel constante; NO captura tendencia ni estacionalidad.
 *     Para series con efecto Navidad o Día de la Madre, propusimos en el
 *     paper extender a Holt o Holt-Winters como trabajo futuro.
 *   - El intervalo de confianza usa σ_residuos in-sample como aproximación.
 *     Es honesto pero no equivale a un CI bayesiano riguroso.
 *   - Necesita ≥ `min_observaciones` semanas (default 8) para no devolver
 *     ruido. Si la historia es menor, ese producto se omite y se loguea.
 */
class DemandaForecastService
{
    public const MODELO = 'SES';

    /**
     * Materializa unidades vendidas por (producto, sucursal, semana ISO)
     * desde la tabla `detalle_ventas` para la ventana indicada.
     *
     * Estrategia: borra TODAS las filas de la ventana y reinserta agregadas
     * desde la fuente. Esto hace el job idempotente y resistente a re-procesos.
     *
     * @param  int   $semanasVentana  Cuántas semanas hacia atrás incluir
     *                                (default = forecast.historia_semanas).
     * @param  ?int  $sucursalId      Si se pasa, restringe el cómputo a esa sucursal.
     * @return array{semanas:int, filas_insertadas:int, productos_distintos:int}
     */
    public function materializarHistorico(?int $semanasVentana = null, ?int $sucursalId = null): array
    {
        $semanas = max(4, (int) ($semanasVentana ?? config('recommendaciones.forecast.historia_semanas', 16)));
        $hoy = CarbonImmutable::now()->startOfWeek(); // Lunes ISO de la semana actual
        $desde = $hoy->subWeeks($semanas)->startOfDay();

        // 1) Agregamos unidades por (producto, sucursal, año ISO, semana ISO).
        //    YEARWEEK + ISO no existe portable en SQLite; calculamos en PHP
        //    iterando con chunkById sobre el rango.
        $base = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->where('ventas.estado', 'completada')
            ->whereNotNull('detalle_ventas.producto_id')
            ->where('ventas.created_at', '>=', $desde)
            ->when($sucursalId !== null, fn ($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->select(
                'detalle_ventas.producto_id',
                'ventas.sucursal_id',
                'ventas.created_at',
                'detalle_ventas.cantidad',
            );

        /** @var array<string, array{producto_id:int,sucursal_id:?int,anio:int,semana_iso:int,semana_inicio:string,unidades_vendidas:int}> $bucket */
        $bucket = [];

        $base->orderBy('detalle_ventas.id')->chunk(2000, function ($filas) use (&$bucket) {
            foreach ($filas as $fila) {
                $fecha = CarbonImmutable::parse($fila->created_at);
                $anio = (int) $fecha->isoWeekYear;
                $sem = (int) $fecha->isoWeek;
                $sucKey = $fila->sucursal_id === null ? 'NULL' : (int) $fila->sucursal_id;
                $key = "{$fila->producto_id}|{$sucKey}|{$anio}|{$sem}";
                if (! isset($bucket[$key])) {
                    $bucket[$key] = [
                        'producto_id'       => (int) $fila->producto_id,
                        'sucursal_id'       => $fila->sucursal_id !== null ? (int) $fila->sucursal_id : null,
                        'anio'              => $anio,
                        'semana_iso'        => $sem,
                        'semana_inicio'     => $fecha->startOfWeek()->toDateString(),
                        'unidades_vendidas' => 0,
                    ];
                }
                $bucket[$key]['unidades_vendidas'] += (int) $fila->cantidad;
            }
        });

        // 2) Reemplazo atómico de la ventana: borrar y re-insertar.
        //    No usamos truncate porque preservamos historia anterior a la
        //    ventana (si el operador la cargó manualmente desde un seeder).
        DB::transaction(function () use ($desde, $sucursalId, $bucket) {
            $del = DB::table('producto_demanda_semana')
                ->where('semana_inicio', '>=', $desde->startOfWeek()->toDateString())
                ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId));
            $del->delete();

            if ($bucket !== []) {
                $now = now();
                $rows = array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], array_values($bucket));
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('producto_demanda_semana')->insert($chunk);
                }
            }
        });

        $productosDistintos = collect($bucket)->pluck('producto_id')->unique()->count();

        return [
            'semanas'             => $semanas,
            'filas_insertadas'    => count($bucket),
            'productos_distintos' => $productosDistintos,
        ];
    }

    /**
     * Recomputa predicciones SES para todos los pares (producto, sucursal)
     * con historia suficiente y persiste un snapshot por (producto, sucursal,
     * semana_objetivo).
     *
     * @return array{productos_pronosticados:int, omitidos_historia_corta:int, alpha:float}
     */
    public function recomputarPredicciones(?int $sucursalId = null, ?float $alpha = null): array
    {
        $alphaUsado = $this->normalizarAlpha($alpha);
        $minObs = max(2, (int) config('recommendaciones.forecast.min_observaciones', 8));
        // CarbonImmutable directo: Eloquent normaliza con el cast 'date:Y-m-d'
        // del modelo, evitando el mismatch INSERT vs WHERE típico en SQLite.
        $semanaObjetivo = CarbonImmutable::now()->startOfWeek()->addWeek()->startOfDay();

        $pares = ProductoDemandaSemana::query()
            ->select('producto_id', 'sucursal_id')
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->groupBy('producto_id', 'sucursal_id')
            ->get();

        $pronosticados = 0;
        $omitidos = 0;
        $now = now();

        foreach ($pares as $par) {
            $serie = ProductoDemandaSemana::query()
                ->where('producto_id', $par->producto_id)
                ->when(
                    $par->sucursal_id === null,
                    fn ($q) => $q->whereNull('sucursal_id'),
                    fn ($q) => $q->where('sucursal_id', $par->sucursal_id),
                )
                ->orderBy('semana_inicio')
                ->pluck('unidades_vendidas')
                ->map(fn ($v) => (float) $v)
                ->all();

            if (count($serie) < $minObs) {
                $omitidos++;
                continue;
            }

            $r = $this->ajustarSes($serie, $alphaUsado);

            ProductoPrediccionDemanda::updateOrCreate(
                [
                    'producto_id'     => (int) $par->producto_id,
                    'sucursal_id'     => $par->sucursal_id !== null ? (int) $par->sucursal_id : null,
                    'semana_objetivo' => $semanaObjetivo,
                ],
                [
                    'prediccion'      => round($r['prediccion'], 2),
                    'intervalo_inf'   => round($r['intervalo_inf'], 2),
                    'intervalo_sup'   => round($r['intervalo_sup'], 2),
                    'alpha_usado'     => $alphaUsado,
                    'modelo'          => self::MODELO,
                    'n_observaciones' => count($serie),
                    'mae'             => $r['mae'] !== null ? round($r['mae'], 4) : null,
                    'mape'            => $r['mape'] !== null ? round($r['mape'], 4) : null,
                    'computed_at'     => $now,
                ],
            );

            $pronosticados++;
        }

        return [
            'productos_pronosticados' => $pronosticados,
            'omitidos_historia_corta' => $omitidos,
            'alpha'                   => $alphaUsado,
        ];
    }

    /**
     * Ajusta SES sobre la serie y devuelve la previsión one-step-ahead más
     * MAE / MAPE / intervalo naive 95%.
     *
     * Algoritmo:
     *   S_0 = Y_0
     *   S_t = α·Y_t + (1−α)·S_{t-1}
     *   ŷ_t (one-step from history) = S_{t-1}
     *   ŷ_{T+1} = S_T
     *   residuos in-sample: e_t = Y_t − S_{t-1}    para t ≥ 1
     *   MAE  = mean(|e_t|)
     *   MAPE = mean(|e_t / Y_t|)  (descartando Y_t = 0)
     *   CI95 ≈ ŷ_{T+1} ± 1.96 · sd(e_t)            (clamp inferior a 0)
     *
     * @param  list<float>  $serie  Cronológica, índice 0 = más antiguo.
     * @return array{prediccion:float, intervalo_inf:float, intervalo_sup:float, mae:?float, mape:?float}
     */
    public function ajustarSes(array $serie, float $alpha): array
    {
        $n = count($serie);
        if ($n === 0) {
            return [
                'prediccion'    => 0.0,
                'intervalo_inf' => 0.0,
                'intervalo_sup' => 0.0,
                'mae'           => null,
                'mape'          => null,
            ];
        }

        $alpha = $this->normalizarAlpha($alpha);

        $s = $serie[0];
        $residuos = [];
        $errPctSum = 0.0;
        $errPctN = 0;

        for ($t = 1; $t < $n; $t++) {
            $yPrev = $s;                  // ŷ_t = S_{t-1}
            $y = $serie[$t];
            $err = $y - $yPrev;
            $residuos[] = $err;
            if (abs($y) > 1e-9) {
                $errPctSum += abs($err / $y);
                $errPctN++;
            }
            $s = $alpha * $y + (1 - $alpha) * $s;
        }

        $prediccion = max(0.0, $s);
        $mae = $residuos !== [] ? array_sum(array_map('abs', $residuos)) / count($residuos) : null;
        $mape = $errPctN > 0 ? $errPctSum / $errPctN : null;

        // Intervalo naive 95% via desviación estándar de residuos (n-1).
        $sdRes = 0.0;
        if (count($residuos) >= 2) {
            $mediaRes = array_sum($residuos) / count($residuos);
            $sumSq = 0.0;
            foreach ($residuos as $r) {
                $sumSq += ($r - $mediaRes) ** 2;
            }
            $sdRes = sqrt($sumSq / (count($residuos) - 1));
        }

        $z = 1.96;
        $inf = max(0.0, $prediccion - $z * $sdRes);
        $sup = $prediccion + $z * $sdRes;

        return [
            'prediccion'    => $prediccion,
            'intervalo_inf' => $inf,
            'intervalo_sup' => $sup,
            'mae'           => $mae,
            'mape'          => $mape,
        ];
    }

    /**
     * Top-K productos en riesgo: predicción para la próxima semana > stock_actual.
     * Devuelve filas listas para el widget del dashboard.
     *
     * @return list<array{
     *   producto_id:int, nombre:string, stock:int, prediccion:float,
     *   intervalo_inf:?float, intervalo_sup:?float, deficit:float, mape:?float
     * }>
     */
    public function productosEnRiesgoStock(?int $sucursalId, int $top = 10): array
    {
        $top = max(1, min(50, $top));
        $semanaObjetivo = CarbonImmutable::now()->startOfWeek()->addWeek();

        // whereDate normaliza la comparación a YYYY-MM-DD ignorando hora,
        // robusto frente a SQLite (TEXT) y MySQL (DATE).
        $rows = ProductoPrediccionDemanda::query()
            ->whereDate('semana_objetivo', $semanaObjetivo->toDateString())
            ->when(
                $sucursalId === null,
                fn ($q) => $q->whereNull('sucursal_id'),
                fn ($q) => $q->where('sucursal_id', $sucursalId),
            )
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $productos = Producto::query()
            ->whereIn('id', $rows->pluck('producto_id')->unique())
            ->where('activo', true)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($rows as $r) {
            $p = $productos->get($r->producto_id);
            if (! $p) {
                continue;
            }
            $deficit = (float) $r->prediccion - (int) $p->stock;
            if ($deficit <= 0) {
                continue; // stock cubre la predicción → no hay riesgo
            }
            $items[] = [
                'producto_id'   => (int) $p->id,
                'nombre'        => (string) $p->nombre,
                'stock'         => (int) $p->stock,
                'prediccion'    => (float) $r->prediccion,
                'intervalo_inf' => $r->intervalo_inf !== null ? (float) $r->intervalo_inf : null,
                'intervalo_sup' => $r->intervalo_sup !== null ? (float) $r->intervalo_sup : null,
                'deficit'       => round($deficit, 2),
                'mape'          => $r->mape !== null ? (float) $r->mape : null,
            ];
        }

        usort($items, fn ($a, $b) => $b['deficit'] <=> $a['deficit']);

        return array_slice($items, 0, $top);
    }

    private function normalizarAlpha(?float $alpha): float
    {
        $configured = (float) config('recommendaciones.forecast.alpha', 0.4);
        $a = $alpha ?? $configured;
        // Clamp a (0,1) abierto: SES degenera en 0 (no aprende) y 1 (random walk).
        return max(0.01, min(0.99, $a));
    }
}
