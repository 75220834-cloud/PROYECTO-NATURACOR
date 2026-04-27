<?php

namespace App\Http\Controllers;

use App\Services\Analytics\HeatmapEnfermedadesService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Bloque 6 — Mapa de calor de enfermedades.
 *
 * Endpoint que renderiza la matriz Enfermedades × Sucursales con tres modos
 * de evidencia (declarada / observada / combinada) y permite descargarla en
 * CSV para reportes ejecutivos o para incluir como tabla en la tesis.
 *
 * Nota de autorización: por consistencia con el dashboard de métricas, los
 * NO admin solo pueden ver el filtrado a su sucursal. Aún así el heatmap
 * tiene sentido global, así que el filtro de sucursal NO restringe los
 * datos (es un panel agregado), solo el catálogo si se quiere extender.
 */
class HeatmapEnfermedadesController extends Controller
{
    public function __construct(
        private readonly HeatmapEnfermedadesService $service,
    ) {}

    public function index(Request $request): View
    {
        [$fuente, $orden, $dias, $umbral, $topSuc] = $this->parsearFiltros($request);

        $matriz = $this->service->construirMatriz(
            fuente: $fuente,
            dias: $dias,
            orden: $orden,
            umbralScore: $umbral,
            topPorSucursal: $topSuc,
        );

        return view('metricas-recomendacion.heatmap', [
            'matriz' => $matriz,
            'filtros' => [
                'fuente' => $fuente,
                'orden'  => $orden,
                'dias'   => $dias,
            ],
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        [$fuente, $orden, $dias, $umbral, $topSuc] = $this->parsearFiltros($request);

        $matriz = $this->service->construirMatriz(
            fuente: $fuente,
            dias: $dias,
            orden: $orden,
            umbralScore: $umbral,
            topPorSucursal: $topSuc,
        );

        $csv = $this->service->exportarCsv($matriz);
        $nombre = sprintf(
            'heatmap_enfermedades_%s_%dd_%s.csv',
            $fuente,
            $dias,
            now()->format('Ymd_His'),
        );

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }

    /**
     * @return array{0:string,1:string,2:int,3:float,4:int}
     */
    private function parsearFiltros(Request $request): array
    {
        $fuente = $request->query('fuente', HeatmapEnfermedadesService::FUENTE_COMBINADA);
        $orden = $request->query('orden', HeatmapEnfermedadesService::ORDEN_TOTAL);
        $dias = (int) $request->query('dias', (int) config('recommendaciones.heatmap_enfermedades.dias_default', 90));
        $umbral = (float) $request->query('umbral_score',
            (float) config('recommendaciones.heatmap_enfermedades.umbral_score', 0.20));
        $topSuc = (int) config('recommendaciones.heatmap_enfermedades.top_por_sucursal', 3);

        return [(string) $fuente, (string) $orden, $dias, $umbral, $topSuc];
    }
}
