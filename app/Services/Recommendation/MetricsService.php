<?php

namespace App\Services\Recommendation;

use App\Models\DetalleVenta;
use App\Models\RecomendacionEvento;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Métricas y evaluación del módulo de recomendación (tesis / artículo).
 * Registro ligero: inserts en bloque para "mostrada", observer para "comprada".
 */
class MetricsService
{
    /**
     * Inserta en bloque eventos tipo "mostrada" por cada ítem devuelto al POS/API.
     *
     * @param  list<array{producto: array, score_final: float, razones: list<string>}>  $items
     * @param  string  $grupoAb  Etiqueta A/B del cliente al momento del request
     *                           (control | tratamiento | sin_ab). Default 'sin_ab'
     *                           para retrocompatibilidad con tests previos al Bloque 4.
     */
    public function registrarMostradas(
        string $recoSesionId,
        int $clienteId,
        ?int $sucursalId,
        ?int $userId,
        array $items,
        string $grupoAb = AbTestingService::GRUPO_SIN_AB,
    ): void {
        if ($items === []) {
            return;
        }

        $now = now();
        $rows = [];
        $pos = 1;
        foreach ($items as $item) {
            $pid = (int) ($item['producto']['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $rows[] = [
                'reco_sesion_id' => $recoSesionId,
                'cliente_id' => $clienteId,
                'producto_id' => $pid,
                'score' => $item['score_final'] ?? null,
                'razones' => json_encode($item['razones'] ?? [], JSON_THROW_ON_ERROR),
                'accion' => RecomendacionEvento::ACCION_MOSTRADA,
                'posicion' => $pos,
                'venta_id' => null,
                'user_id' => $userId,
                'sucursal_id' => $sucursalId,
                'grupo_ab' => $grupoAb,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $pos++;
        }

        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('recomendacion_eventos')->insert($chunk);
        }
    }

    /**
     * Registra clic o agregada al carrito desde el POS (llamada explícita).
     */
    public function registrarInteraccionPos(
        string $accion,
        string $recoSesionId,
        int $clienteId,
        int $productoId,
        ?int $sucursalId,
        ?int $userId,
        ?string $grupoAb = null,
    ): bool {
        if (! in_array($accion, [RecomendacionEvento::ACCION_CLIC, RecomendacionEvento::ACCION_AGREGADA], true)) {
            return false;
        }

        $filaMostrada = RecomendacionEvento::query()
            ->where('reco_sesion_id', $recoSesionId)
            ->where('cliente_id', $clienteId)
            ->where('producto_id', $productoId)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
            ->orderBy('posicion')
            ->first();
        if (! $filaMostrada) {
            Log::warning('Reco métrica descartada: interacción sin mostrada base', [
                'accion' => $accion,
                'reco_sesion_id' => $recoSesionId,
                'cliente_id' => $clienteId,
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
            ]);

            return false;
        }

        // [BLOQUE 4] Hereda grupo_ab de la fila "mostrada" base si no se
        // pasa explícito: garantiza que toda la cadena (mostrada→clic→agregada
        // →comprada) quede etiquetada con el mismo grupo experimental.
        $grupoAbFinal = $grupoAb ?? $filaMostrada?->grupo_ab ?? AbTestingService::GRUPO_SIN_AB;

        RecomendacionEvento::create([
            'reco_sesion_id' => $recoSesionId,
            'cliente_id' => $clienteId,
            'producto_id' => $productoId,
            'score' => $filaMostrada?->score,
            'razones' => $filaMostrada?->razones,
            'accion' => $accion,
            'posicion' => $filaMostrada?->posicion,
            'venta_id' => null,
            'user_id' => $userId,
            'sucursal_id' => $sucursalId,
            'grupo_ab' => $grupoAbFinal,
        ]);

        return true;
    }

    /**
     * Si el detalle corresponde a una venta con cliente y hubo exposición reciente
     * del mismo producto (mostrada/agregada/clic), registra "comprada".
     */
    public function registrarCompradaSiCorresponde(DetalleVenta $detalle): void
    {
        $detalle->loadMissing('venta');
        $venta = $detalle->venta;
        if (! $venta || $venta->estado !== 'completada' || ! $venta->cliente_id || ! $detalle->producto_id) {
            return;
        }

        $exists = RecomendacionEvento::query()
            ->where('venta_id', $venta->id)
            ->where('producto_id', $detalle->producto_id)
            ->where('accion', RecomendacionEvento::ACCION_COMPRADA)
            ->exists();

        if ($exists) {
            return;
        }

        $horas = max(1, (int) config('recommendaciones.metricas_lookback_horas', 72));
        $since = $venta->created_at->copy()->subHours($horas);

        $previo = RecomendacionEvento::query()
            ->whereNotNull('reco_sesion_id')
            ->where('cliente_id', $venta->cliente_id)
            ->where('producto_id', $detalle->producto_id)
            ->whereIn('accion', [
                RecomendacionEvento::ACCION_MOSTRADA,
                RecomendacionEvento::ACCION_AGREGADA,
                RecomendacionEvento::ACCION_CLIC,
            ])
            ->where('created_at', '<=', $venta->created_at)
            ->where('created_at', '>=', $since)
            ->orderByRaw("CASE accion WHEN 'agregada' THEN 1 WHEN 'clic' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $previo) {
            return;
        }

        $sesionValida = RecomendacionEvento::query()
            ->where('reco_sesion_id', $previo->reco_sesion_id)
            ->where('cliente_id', $venta->cliente_id)
            ->where('producto_id', $detalle->producto_id)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
            ->exists();
        if (! $sesionValida) {
            Log::info('Compra sin atribución sólida: sesión no verificable', [
                'venta_id' => $venta->id,
                'cliente_id' => $venta->cliente_id,
                'producto_id' => $detalle->producto_id,
                'reco_sesion_id' => $previo->reco_sesion_id,
            ]);

            return;
        }

        RecomendacionEvento::create([
            'reco_sesion_id' => $previo->reco_sesion_id,
            'cliente_id' => $venta->cliente_id,
            'producto_id' => $detalle->producto_id,
            'score' => $previo->score,
            'razones' => $previo->razones,
            'accion' => RecomendacionEvento::ACCION_COMPRADA,
            'posicion' => $previo->posicion,
            'venta_id' => $venta->id,
            'user_id' => $venta->user_id,
            'sucursal_id' => $venta->sucursal_id,
            // [BLOQUE 4] Coherencia A/B: la "comprada" hereda el grupo del
            // "mostrada" originario; esto evita que un re-asignamiento posterior
            // contamine la atribución histórica del experimento.
            'grupo_ab' => $previo->grupo_ab ?? $venta->grupo_ab ?? AbTestingService::GRUPO_SIN_AB,
        ]);
    }

    /**
     * Resumen para dashboard (intervalo en días, filtro sucursal opcional).
     *
     * @return array<string, mixed>
     */
    public function resumenDashboard(int $dias, ?int $sucursalId): array
    {
        $desde = now()->subDays(max(1, $dias));
        $k = max(1, (int) config('recommendaciones.metricas_precision_k', 6));

        $totalMostradas = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)->count();
        $totalAgregadas = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_AGREGADA)->count();
        $totalClics = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_CLIC)->count();
        $totalCompradas = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_COMPRADA)->count();

        $conversionCompraSobreAgregada = $totalAgregadas > 0
            ? round($totalCompradas / $totalAgregadas, 4)
            : null;

        $conversionCompraSobreMostrada = $totalMostradas > 0
            ? round($totalCompradas / $totalMostradas, 4)
            : null;

        $precisionAtK = $this->calcularPrecisionAtK($desde, $sucursalId, $k);

        $tickets = $this->ticketPromedioConYSinReco($desde, $sucursalId);

        $topProductos = $this->queryEventosDesde($desde, $sucursalId)
            ->selectRaw('producto_id, COUNT(*) as veces')
            ->whereIn('accion', [
                RecomendacionEvento::ACCION_MOSTRADA,
                RecomendacionEvento::ACCION_AGREGADA,
                RecomendacionEvento::ACCION_COMPRADA,
            ])
            ->whereNotNull('producto_id')
            ->groupBy('producto_id')
            ->orderByDesc('veces')
            ->limit(10)
            ->get();

        $sesionesDistintas = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
            ->whereNotNull('reco_sesion_id')
            ->select('reco_sesion_id')
            ->distinct()
            ->get()
            ->count();

        return [
            'periodo_dias' => $dias,
            'precision_k' => $k,
            'total_mostrada' => $totalMostradas,
            'total_agregada' => $totalAgregadas,
            'total_clic' => $totalClics,
            'total_comprada' => $totalCompradas,
            'sesiones_reco_distintas' => $sesionesDistintas,
            'conversion_compra_sobre_agregada' => $conversionCompraSobreAgregada,
            'conversion_compra_sobre_mostrada' => $conversionCompraSobreMostrada,
            'precision_at_k' => $precisionAtK,
            'ticket_promedio_con_reco' => $tickets['con'],
            'ticket_promedio_sin_reco' => $tickets['sin'],
            'ventas_con_reco_count' => $tickets['n_con'],
            'ventas_sin_reco_count' => $tickets['n_sin'],
            'top_productos' => $topProductos,
        ];
    }

    /**
     * Serie temporal diaria de eventos del recomendador.
     *
     * @return array{
     *   labels:list<string>,
     *   mostrada:list<int>,
     *   clic:list<int>,
     *   agregada:list<int>,
     *   comprada:list<int>
     * }
     */
    public function serieEventosPorDia(int $dias, ?int $sucursalId): array
    {
        $dias = max(1, min(365, $dias));
        $inicio = now()->startOfDay()->subDays($dias - 1);

        $rows = RecomendacionEvento::query()
            ->where('created_at', '>=', $inicio)
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->selectRaw('DATE(created_at) as fecha, accion, COUNT(*) as total')
            ->groupBy('fecha', 'accion')
            ->orderBy('fecha')
            ->get();

        $porFechaAccion = [];
        foreach ($rows as $row) {
            $f = (string) $row->fecha;
            $a = (string) $row->accion;
            $porFechaAccion[$f][$a] = (int) $row->total;
        }

        $labels = [];
        $mostrada = [];
        $clic = [];
        $agregada = [];
        $comprada = [];

        for ($i = 0; $i < $dias; $i++) {
            $fecha = $inicio->copy()->addDays($i);
            $clave = $fecha->toDateString();
            $labels[] = $fecha->format('d/m');
            $mostrada[] = (int) ($porFechaAccion[$clave][RecomendacionEvento::ACCION_MOSTRADA] ?? 0);
            $clic[] = (int) ($porFechaAccion[$clave][RecomendacionEvento::ACCION_CLIC] ?? 0);
            $agregada[] = (int) ($porFechaAccion[$clave][RecomendacionEvento::ACCION_AGREGADA] ?? 0);
            $comprada[] = (int) ($porFechaAccion[$clave][RecomendacionEvento::ACCION_COMPRADA] ?? 0);
        }

        return compact('labels', 'mostrada', 'clic', 'agregada', 'comprada');
    }

    private function queryEventosDesde(Carbon $desde, ?int $sucursalId)
    {
        $q = RecomendacionEvento::query()->where('created_at', '>=', $desde);
        if ($sucursalId !== null) {
            $q->where('sucursal_id', $sucursalId);
        }

        return $q;
    }

    /**
     * Precision@k operativa: entre sesiones con lista mostrada, ¿cuántas tuvieron
     * al menos una compra de un producto que estaba en el top-k de esa sesión?
     */
    private function calcularPrecisionAtK(Carbon $desde, ?int $sucursalId, int $k): ?float
    {
        $sesiones = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
            ->whereNotNull('reco_sesion_id')
            ->select('reco_sesion_id')
            ->distinct()
            ->pluck('reco_sesion_id')
            ->filter();
        if ($sesiones->isEmpty()) {
            return null;
        }

        $hits = 0;
        $total = 0;
        foreach ($sesiones as $sid) {
            $mostradas = RecomendacionEvento::query()
                ->where('reco_sesion_id', $sid)
                ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
                ->orderBy('posicion')
                ->get();

            if ($mostradas->isEmpty()) {
                continue;
            }
            $total++;
            $topIds = $mostradas->take($k)->pluck('producto_id')->filter()->all();
            if ($topIds === []) {
                continue;
            }
            $comproTopk = RecomendacionEvento::query()
                ->where('reco_sesion_id', $sid)
                ->where('accion', RecomendacionEvento::ACCION_COMPRADA)
                ->whereIn('producto_id', $topIds)
                ->exists();
            if ($comproTopk) {
                $hits++;
            }
        }

        return $total > 0 ? round($hits / $total, 4) : null;
    }

    /**
     * @return array{con: ?float, sin: ?float, n_con: int, n_sin: int}
     */
    private function ticketPromedioConYSinReco(Carbon $desde, ?int $sucursalId): array
    {
        $ventasConRecoIds = RecomendacionEvento::query()
            ->where('accion', RecomendacionEvento::ACCION_COMPRADA)
            ->whereNotNull('venta_id')
            ->where('created_at', '>=', $desde)
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->distinct()
            ->pluck('venta_id');

        $qVentas = Venta::query()
            ->where('estado', 'completada')
            ->whereNotNull('cliente_id')
            ->where('created_at', '>=', $desde)
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId));

        if ($ventasConRecoIds->isEmpty()) {
            $nSin = (clone $qVentas)->count();

            return [
                'con' => null,
                'sin' => $nSin > 0 ? round((float) (clone $qVentas)->avg('total'), 2) : null,
                'n_con' => 0,
                'n_sin' => $nSin,
            ];
        }

        $con = (clone $qVentas)->whereIn('id', $ventasConRecoIds);
        $sin = (clone $qVentas)->whereNotIn('id', $ventasConRecoIds);

        $nCon = (clone $con)->count();
        $nSin = (clone $sin)->count();

        return [
            'con' => $nCon > 0 ? round((float) (clone $con)->avg('total'), 2) : null,
            'sin' => $nSin > 0 ? round((float) (clone $sin)->avg('total'), 2) : null,
            'n_con' => $nCon,
            'n_sin' => $nSin,
        ];
    }

    /**
     * [BLOQUE 4] Comparativa A/B documentada — evidencia para artículo Scopus.
     *
     * Compara grupo control (no recibió recos) vs tratamiento (sí las recibió)
     * en dos métricas operativas:
     *   - Ticket promedio por venta (impacto monetario directo).
     *   - Tasa de conversión por sesión (¿la lista convierte en compra?).
     *
     * Devuelve además el resultado del Welch t-test sobre el ticket
     * (delegado a `AbTestingService::welchTTest`) más la nota metodológica
     * que el dashboard renderiza al evaluador.
     *
     * @return array{
     *   activo: bool,
     *   estrategia: string,
     *   periodo_dias: int,
     *   ticket: array{
     *     control: array{n: int, media: ?float, sd: ?float},
     *     tratamiento: array{n: int, media: ?float, sd: ?float},
     *     test: array<string, mixed>
     *   },
     *   conversion: array{
     *     control: array{n_sesiones: int, n_compras: int, tasa: ?float},
     *     tratamiento: array{n_sesiones: int, n_compras: int, tasa: ?float},
     *     diferencia_pct: ?float
     *   }
     * }
     */
    public function comparativaAbTesting(int $dias, ?int $sucursalId, AbTestingService $ab): array
    {
        $desde = now()->subDays(max(1, $dias));

        // --- TICKET por grupo (vienen de la columna ventas.grupo_ab) ---
        $ventasControl = Venta::query()
            ->where('estado', 'completada')
            ->whereNotNull('cliente_id')
            ->where('grupo_ab', AbTestingService::GRUPO_CONTROL)
            ->where('created_at', '>=', $desde)
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->pluck('total')
            ->map(fn ($t) => (float) $t)
            ->all();

        $ventasTrat = Venta::query()
            ->where('estado', 'completada')
            ->whereNotNull('cliente_id')
            ->where('grupo_ab', AbTestingService::GRUPO_TRATAMIENTO)
            ->where('created_at', '>=', $desde)
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->pluck('total')
            ->map(fn ($t) => (float) $t)
            ->all();

        $test = $ab->welchTTest($ventasControl, $ventasTrat);

        // --- CONVERSIÓN por grupo: sesiones distintas con mostrada vs ventas con grupo ---
        $sesionesControl = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
            ->where('grupo_ab', AbTestingService::GRUPO_CONTROL)
            ->whereNotNull('reco_sesion_id')
            ->distinct('reco_sesion_id')
            ->count('reco_sesion_id');

        $sesionesTrat = $this->queryEventosDesde($desde, $sucursalId)
            ->where('accion', RecomendacionEvento::ACCION_MOSTRADA)
            ->where('grupo_ab', AbTestingService::GRUPO_TRATAMIENTO)
            ->whereNotNull('reco_sesion_id')
            ->distinct('reco_sesion_id')
            ->count('reco_sesion_id');

        $comprasControl = count($ventasControl);
        $comprasTrat = count($ventasTrat);

        // Para "control" no hay mostradas (no se les muestra nada), entonces
        // la conversión por sesión queda definida sólo en tratamiento. Para
        // honestidad metodológica reportamos los conteos brutos del control
        // y el evaluador interpreta correctamente "n_sesiones=0 control".
        $tasaControl = $sesionesControl > 0 ? round($comprasControl / $sesionesControl, 4) : null;
        $tasaTrat = $sesionesTrat > 0 ? round($comprasTrat / $sesionesTrat, 4) : null;

        $diferenciaPct = ($tasaControl !== null && $tasaTrat !== null)
            ? round(($tasaTrat - $tasaControl) * 100, 2)
            : null;

        return [
            'activo'       => (bool) config('recommendaciones.ab_testing.enabled', false),
            'estrategia'   => (string) config('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH),
            'periodo_dias' => $dias,
            'ticket' => [
                'control' => [
                    'n'     => $test['n_a'],
                    'media' => $test['n_a'] > 0 ? round($test['media_a'], 2) : null,
                    'sd'    => $test['n_a'] > 1 ? round(sqrt($test['var_a']), 2) : null,
                ],
                'tratamiento' => [
                    'n'     => $test['n_b'],
                    'media' => $test['n_b'] > 0 ? round($test['media_b'], 2) : null,
                    'sd'    => $test['n_b'] > 1 ? round(sqrt($test['var_b']), 2) : null,
                ],
                'test' => $test,
            ],
            'conversion' => [
                'control' => [
                    'n_sesiones' => $sesionesControl,
                    'n_compras'  => $comprasControl,
                    'tasa'       => $tasaControl,
                ],
                'tratamiento' => [
                    'n_sesiones' => $sesionesTrat,
                    'n_compras'  => $comprasTrat,
                    'tasa'       => $tasaTrat,
                ],
                'diferencia_pct' => $diferenciaPct,
            ],
        ];
    }
}
