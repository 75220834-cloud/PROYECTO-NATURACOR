<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\FidelizacionCanje;
use Illuminate\Http\Request;

class FidelizacionController extends Controller
{
    /**
     * Módulo de Fidelización — Progreso de clientes hacia S/500 en productos.
     */
    public function index(Request $request)
    {
        $umbral = config('naturacor.fidelizacion_monto', 500);
        $filtro = $request->get('estado', 'todos');

        $query = Cliente::withCount('ventas')
            ->addSelect([
                'total_productos' => \App\Models\DetalleVenta::selectRaw('COALESCE(SUM(detalle_ventas.subtotal), 0)')
                    ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                    ->whereColumn('ventas.cliente_id', 'clientes.id')
                    ->where('ventas.estado', 'completada'),
            ]);

        // Búsqueda por DNI o nombre
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('dni', 'like', "%{$request->search}%")
                  ->orWhere('nombre', 'like', "%{$request->search}%")
                  ->orWhere('apellido', 'like', "%{$request->search}%");
            });
        }

        $clientes = $query->orderByDesc('acumulado_naturales')->get();

        // Clasificar clientes
        $enProgreso = $clientes->filter(fn($c) => (float) $c->acumulado_naturales > 0 && (float) $c->acumulado_naturales < $umbral);
        $listosParaPremio = $clientes->filter(fn($c) => (float) $c->acumulado_naturales >= $umbral);

        // Canjes pendientes y entregados
        $canjesPendientes = FidelizacionCanje::with('cliente')
            ->where('tipo_regla', 'regla1_500')
            ->where('entregado', false)
            ->latest()
            ->get();

        $canjesEntregados = FidelizacionCanje::with('cliente')
            ->where('tipo_regla', 'regla1_500')
            ->where('entregado', true)
            ->latest()
            ->limit(20)
            ->get();

        // Contadores
        $totalClientes = $clientes->count();
        $totalEnProgreso = $enProgreso->count();
        $totalListos = $listosParaPremio->count() + $canjesPendientes->count();
        $totalEntregados = $canjesEntregados->count();

        return view('fidelizacion.index', compact(
            'clientes', 'enProgreso', 'listosParaPremio',
            'canjesPendientes', 'canjesEntregados',
            'totalClientes', 'totalEnProgreso', 'totalListos', 'totalEntregados',
            'umbral', 'filtro'
        ));
    }

    /**
     * Marcar un premio como entregado.
     */
    public function entregar(FidelizacionCanje $canje)
    {
        $canje->update([
            'entregado'    => true,
            'entregado_at' => now(),
        ]);

        // Reiniciar acumulado del cliente a 0 para que pueda ganar otro premio
        $canje->cliente->update([
            'acumulado_naturales' => 0,
        ]);

        // Descontar 1 unidad del producto Litro Especial del inventario
        $productoEspecial = \App\Models\Producto::where('activo', true)
            ->where('nombre', 'like', '%litro especial%')
            ->where('tipo', 'cordial')
            ->first();

        if ($productoEspecial && $productoEspecial->stock > 0) {
            $productoEspecial->decrement('stock', 1);
        }

        return back()->with('success', "Premio '{$canje->descripcion_premio}' entregado a {$canje->cliente->nombreCompleto()}. Su acumulado se reinició a S/0 para un nuevo ciclo.");
    }
}
