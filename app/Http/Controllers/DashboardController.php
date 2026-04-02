<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\CajaSesion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $sucursalId = auth()->user()->sucursal_id;

        $ventasHoy = Venta::whereDate('created_at', $hoy)
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->where('estado', 'completada');

        $totalHoy = (clone $ventasHoy)->sum('total');
        $countHoy = (clone $ventasHoy)->count();

        // Ingresos por método de pago hoy
        $ingresosPorMetodo = (clone $ventasHoy)->selectRaw('metodo_pago, SUM(total) as total')
            ->groupBy('metodo_pago')->pluck('total', 'metodo_pago');

        // Productos más vendidos (últimos 30 días)
        $masVendidos = \App\Models\DetalleVenta::join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->where('ventas.created_at', '>=', Carbon::now()->subDays(30))
            ->when($sucursalId, fn($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->selectRaw('producto_id, nombre_producto, SUM(cantidad) as total_vendido')
            ->groupBy('producto_id', 'nombre_producto')
            ->orderByDesc('total_vendido')
            ->limit(5)->get();

        // Ventas por día (últimos 7 días)
        $ventasSemana = Venta::where('estado', 'completada')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->selectRaw('DATE(created_at) as fecha, SUM(total) as total, COUNT(*) as cantidad')
            ->groupBy('fecha')->orderBy('fecha')->get();

        // Stock bajo
        $stockBajo = Producto::where('activo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->get();

        // Caja activa
        $cajaActiva = CajaSesion::where('estado', 'abierta')
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->where('user_id', auth()->id())->first();

        // Ventas mes
        $totalMes = Venta::whereMonth('created_at', $hoy->month)
            ->whereYear('created_at', $hoy->year)
            ->where('estado', 'completada')
            ->when($sucursalId, fn($q) => $q->where('sucursal_id', $sucursalId))
            ->sum('total');

        return view('dashboard.index', compact(
            'totalHoy', 'countHoy', 'ingresosPorMetodo',
            'masVendidos', 'ventasSemana', 'stockBajo',
            'cajaActiva', 'totalMes'
        ));
    }
}
