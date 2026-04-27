<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\Recommendation\AbTestingService;
use App\Services\Recommendation\MetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard de evaluación del módulo de recomendaciones (tesis / evidencia cuantitativa).
 */
class RecomendacionMetricasController extends Controller
{
    public function __construct(
        private readonly MetricsService $metrics,
        private readonly AbTestingService $ab,
    ) {}

    public function index(Request $request): View
    {
        $dias = max(1, min(365, (int) $request->query('dias', (int) config('recommendaciones.metricas_dashboard_dias', 30))));

        $sucursalId = $request->user()->isAdmin()
            ? ($request->filled('sucursal_id') ? (int) $request->sucursal_id : null)
            : $request->user()->sucursal_id;

        $resumen = $this->metrics->resumenDashboard($dias, $sucursalId);

        $nombresProducto = Producto::query()
            ->whereIn('id', $resumen['top_productos']->pluck('producto_id')->filter())
            ->pluck('nombre', 'id');

        $barras = $resumen['top_productos']->map(function ($row) use ($nombresProducto) {
            $nombre = $nombresProducto[$row->producto_id] ?? ('#'.$row->producto_id);

            return [
                'nombre' => $nombre,
                'veces' => (int) $row->veces,
            ];
        });

        $maxBar = $barras->isEmpty() ? 1 : max(1, (int) $barras->max('veces'));
        $serie = $this->metrics->serieEventosPorDia($dias, $sucursalId);

        $chartData = [
            'conversiones' => [
                'labels' => ['Mostradas', 'Compradas'],
                'values' => [
                    (int) ($resumen['total_mostrada'] ?? 0),
                    (int) ($resumen['total_comprada'] ?? 0),
                ],
            ],
            'interacciones' => [
                'labels' => ['Clic', 'Agregada'],
                'values' => [
                    (int) ($resumen['total_clic'] ?? 0),
                    (int) ($resumen['total_agregada'] ?? 0),
                ],
            ],
            'topProductos' => [
                'labels' => $barras->pluck('nombre')->values()->all(),
                'values' => $barras->pluck('veces')->values()->all(),
            ],
            'timeline' => $serie,
            'meta_debug' => [
                'generated_at' => now()->toIso8601String(),
                'dias' => $dias,
                'sucursal_id' => $sucursalId,
                'total_mostrada' => (int) ($resumen['total_mostrada'] ?? 0),
                'total_clic' => (int) ($resumen['total_clic'] ?? 0),
                'total_agregada' => (int) ($resumen['total_agregada'] ?? 0),
                'total_comprada' => (int) ($resumen['total_comprada'] ?? 0),
            ],
        ];

        $abComparativa = $this->metrics->comparativaAbTesting($dias, $sucursalId, $this->ab);

        return view('metricas-recomendacion.index', compact(
            'resumen', 'barras', 'maxBar', 'dias', 'sucursalId', 'chartData', 'abComparativa'
        ));
    }
}
