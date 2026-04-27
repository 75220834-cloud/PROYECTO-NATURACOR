<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\CajaSesion;
use App\Models\CajaMovimiento;
use App\Models\FidelizacionCanje;
use App\Services\Forecasting\DemandaForecastService;
use App\Services\Recommendation\MetricsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(MetricsService $metrics, DemandaForecastService $forecast)
    {
        $hoy = Carbon::today();
        $sucursalId = auth()->user()->sucursal_id;

        $ventasHoy = Venta::whereDate('created_at', $hoy)
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->where('estado', 'completada');

        $totalHoy = (clone $ventasHoy)->sum('total');
        $countHoy = (clone $ventasHoy)->count();

        $ingresosPorMetodo = (clone $ventasHoy)->selectRaw('metodo_pago, SUM(total) as total')
            ->groupBy('metodo_pago')->pluck('total', 'metodo_pago');

        $egresosHoy = CajaMovimiento::whereDate('created_at', $hoy)
            ->where('tipo', 'egreso')
            ->when($sucursalId, fn($q) => $q->whereHas('cajaSesion', fn($s) => $s->where('sucursal_id', $sucursalId)))
            ->sum('monto');

        $efectivoNetoHoy = $totalHoy - $egresosHoy;

        $masVendidos = \App\Models\DetalleVenta::join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->where('ventas.created_at', '>=', Carbon::now()->subDays(30))
            ->when($sucursalId, fn($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->selectRaw('producto_id, nombre_producto, SUM(cantidad) as total_vendido')
            ->groupBy('producto_id', 'nombre_producto')
            ->orderByDesc('total_vendido')
            ->limit(5)->get();

        $ventasSemana = Venta::where('estado', 'completada')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->selectRaw('DATE(created_at) as fecha, SUM(total) as total, COUNT(*) as cantidad')
            ->groupBy('fecha')->orderBy('fecha')->get();

        $stockBajo = Producto::where('activo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->get();

        $cajaActiva = CajaSesion::where('estado', 'abierta')
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->where('user_id', auth()->id())->first();

        $totalMes = Venta::whereMonth('created_at', $hoy->month)
            ->whereYear('created_at', $hoy->year)
            ->where('estado', 'completada')
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->sum('total');

        $premiosPendientes = FidelizacionCanje::where('entregado', false)
            ->where('tipo_regla', 'regla1_500')
            ->count();

        // Widget IA — métricas del recomendador (últimas 24h)
        $metricasIA = $metrics->resumenDashboard(1, $sucursalId);

        // Widget Forecast (Bloque 5) — top productos en riesgo de quiebre
        // según el modelo SES corrido por ActualizarDemandaJob.
        $topWidget = (int) config('recommendaciones.forecast.top_riesgo_widget', 10);
        $forecastRiesgo = $forecast->productosEnRiesgoStock($sucursalId, $topWidget);

        return view('dashboard.index', compact(
            'totalHoy', 'countHoy', 'ingresosPorMetodo',
            'masVendidos', 'ventasSemana', 'stockBajo',
            'cajaActiva', 'totalMes', 'premiosPendientes',
            'egresosHoy', 'efectivoNetoHoy', 'metricasIA',
            'forecastRiesgo'
        ));
    }
}
