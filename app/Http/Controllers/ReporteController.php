<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\CajaMovimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $sucursales = Sucursal::where('activa', true)->get();
        $empleados = User::role('empleado')->get();
        $productos = Producto::orderBy('nombre')->get();
        return view('reportes.index', compact('sucursales', 'empleados', 'productos'));
    }

    public function generar(Request $request)
    {
        $query = Venta::with(['cliente', 'empleado', 'sucursal', 'detalles', 'cordialVentas'])
            ->where('estado', 'completada');

        if (!auth()->user()->isAdmin()) {
            $query->where('sucursal_id', auth()->user()->sucursal_id);
        }
        if ($request->fecha_desde) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->fecha_hasta) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->metodo_pago) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        if ($request->producto_id) {
            $query->whereHas('detalles', fn($q) => $q->where('producto_id', $request->producto_id));
        }

        $ventas = $query->latest()->get();
        $totales = [
            'ventas' => $ventas->count(),
            'total' => $ventas->sum('total'),
            'efectivo' => $ventas->where('metodo_pago', 'efectivo')->sum('total'),
            'yape' => $ventas->where('metodo_pago', 'yape')->sum('total'),
            'plin' => $ventas->where('metodo_pago', 'plin')->sum('total'),
            'otros' => $ventas->whereNotIn('metodo_pago', ['efectivo', 'yape', 'plin'])->sum('total'),
        ];

        // Egresos del mismo período
        $egresosQuery = CajaMovimiento::with(['empleado', 'cajaSesion'])
            ->where('tipo', 'egreso');

        if (!auth()->user()->isAdmin()) {
            $egresosQuery->whereHas('cajaSesion', fn($q) => $q->where('sucursal_id', auth()->user()->sucursal_id));
        }
        if ($request->fecha_desde) {
            $egresosQuery->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->fecha_hasta) {
            $egresosQuery->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        if ($request->sucursal_id) {
            $egresosQuery->whereHas('cajaSesion', fn($q) => $q->where('sucursal_id', $request->sucursal_id));
        }
        if ($request->user_id) {
            $egresosQuery->where('user_id', $request->user_id);
        }

        $egresos = $egresosQuery->latest()->get();
        $totalEgresos = $egresos->sum('monto');
        $totales['egresos'] = $totalEgresos;
        $totales['neto'] = $totales['total'] - $totalEgresos;

        if ($request->exportar === 'pdf') {
            $pdf = Pdf::loadView('reportes.pdf', compact('ventas', 'totales', 'egresos', 'request'))->setPaper('a4', 'landscape');
            return $pdf->download('reporte-ventas-' . now()->format('Ymd') . '.pdf');
        }

        return view('reportes.resultado', compact('ventas', 'totales', 'egresos'));
    }
}
