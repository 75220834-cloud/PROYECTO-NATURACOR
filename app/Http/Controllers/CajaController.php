<?php

namespace App\Http\Controllers;

use App\Models\CajaSesion;
use App\Models\CajaMovimiento;
use App\Models\Venta;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CajaController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->sucursal_id;
        $sesionActiva = CajaSesion::where('estado', 'abierta')
            ->where('user_id', auth()->id())
            ->with(['movimientos', 'ventas'])
            ->first();
        $sesionesAnteriores = CajaSesion::where('user_id', auth()->id())
            ->where('estado', 'cerrada')
            ->latest()->limit(10)->get();
        return view('caja.index', compact('sesionActiva', 'sesionesAnteriores'));
    }

    public function abrir(Request $request)
    {
        $request->validate(['monto_inicial' => 'required|numeric|min:0']);

        $yaAbierta = CajaSesion::where('estado', 'abierta')->where('user_id', auth()->id())->exists();
        if ($yaAbierta) return back()->with('error', 'Ya tienes una caja abierta.');

        CajaSesion::create([
            'user_id' => auth()->id(),
            'sucursal_id' => auth()->user()->sucursal_id ?? 1,
            'monto_inicial' => $request->monto_inicial,
            'apertura_at' => now(),
        ]);

        return back()->with('success', 'Caja abierta correctamente.');
    }

    public function movimiento(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:255',
            'metodo_pago' => 'required|string',
        ]);

        $sesion = CajaSesion::where('estado', 'abierta')->where('user_id', auth()->id())->firstOrFail();

        CajaMovimiento::create([
            'caja_sesion_id' => $sesion->id,
            'user_id' => auth()->id(),
            'tipo' => $request->tipo,
            'monto' => $request->monto,
            'descripcion' => $request->descripcion,
            'metodo_pago' => $request->metodo_pago,
        ]);

        // Actualizar totales de la sesión
        if ($request->tipo === 'ingreso') {
            $sesion->increment('total_esperado', $request->monto);
            $campo = match($request->metodo_pago) {
                'yape' => 'total_yape', 'plin' => 'total_plin',
                'efectivo' => 'total_efectivo', default => 'total_otros',
            };
            $sesion->increment($campo, $request->monto);
        } else {
            $sesion->decrement('total_esperado', $request->monto);
        }

        return back()->with('success', 'Movimiento registrado.');
    }

    public function cerrar(Request $request)
    {
        $request->validate(['monto_real' => 'required|numeric|min:0']);

        $sesion = CajaSesion::where('estado', 'abierta')->where('user_id', auth()->id())->firstOrFail();
        $sesion->monto_real_cierre = $request->monto_real;
        $sesion->diferencia = $request->monto_real - $sesion->total_esperado;
        $sesion->cierre_at = now();
        $sesion->estado = 'cerrada';
        $sesion->notas_cierre = $request->notas;
        $sesion->save();

        return redirect()->route('caja.index')->with('success', "Caja cerrada. Diferencia: S/" . number_format($sesion->diferencia, 2));
    }

    public function show(CajaSesion $cajaSesion)
    {
        $cajaSesion->load(['movimientos', 'ventas.detalles', 'empleado']);
        return view('caja.show', compact('cajaSesion'));
    }
}
