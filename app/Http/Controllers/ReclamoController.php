<?php

namespace App\Http\Controllers;

use App\Models\Reclamo;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ReclamoController extends Controller
{
    /**
     * Lista de reclamos de la sucursal del usuario autenticado.
     */
    public function index(Request $request)
    {
        $query = Reclamo::with(['cliente', 'vendedor', 'sucursal'])
            ->where('sucursal_id', auth()->user()->sucursal_id);

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        $reclamos = $query->latest()->paginate(20);

        return view('reclamos.index', compact('reclamos'));
    }

    /**
     * Formulario para registrar un nuevo reclamo.
     */
    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        return view('reclamos.create', compact('clientes'));
    }

    /**
     * Registrar un nuevo reclamo.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'  => 'nullable|exists:clientes,id',
            'tipo'        => 'required|in:producto,servicio,otro',
            'descripcion' => 'required|string|min:10|max:1000',
        ]);

        $reclamo = Reclamo::create([
            'cliente_id'  => $data['cliente_id'] ?? null,
            'vendedor_id' => auth()->id(),
            'sucursal_id' => auth()->user()->sucursal_id,
            'tipo'        => $data['tipo'],
            'descripcion' => $data['descripcion'],
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);

        // Log de auditoría
        \App\Models\LogAuditoria::create([
            'user_id'         => auth()->id(),
            'accion'          => 'reclamo.registrado',
            'tabla_afectada'  => 'reclamos',
            'registro_id'     => $reclamo->id,
            'datos_nuevos'    => ['tipo' => $reclamo->tipo, 'estado' => $reclamo->estado],
            'ip'              => request()->ip(),
            'sucursal_id'     => auth()->user()->sucursal_id,
        ]);

        return redirect()->route('reclamos.index')
            ->with('success', 'Reclamo registrado correctamente. Se atenderá a la brevedad.');
    }

    /**
     * Detalle de un reclamo.
     */
    public function show(Reclamo $reclamo)
    {
        $reclamo->load(['cliente', 'vendedor', 'adminResolutor']);
        return view('reclamos.show', compact('reclamo'));
    }

    /**
     * Actualizar estado y/o resolución de un reclamo (admin).
     */
    public function update(Request $request, Reclamo $reclamo)
    {
        $data = $request->validate([
            'estado'     => 'required|in:pendiente,en_proceso,resuelto',
            'resolucion' => 'nullable|string|max:1000',
        ]);

        $reclamo->update([
            'estado'             => $data['estado'],
            'resolucion'         => $data['resolucion'] ?? $reclamo->resolucion,
            'admin_resolutor_id' => auth()->id(),
        ]);

        return redirect()->route('reclamos.index')
            ->with('success', 'Reclamo actualizado correctamente.');
    }

    /**
     * Escalar un reclamo al administrador.
     */
    public function escalar(Reclamo $reclamo)
    {
        $reclamo->update([
            'escalado' => true,
            'estado'   => 'en_proceso',
        ]);

        return redirect()->route('reclamos.index')
            ->with('success', 'Reclamo escalado al administrador.');
    }

    /**
     * Eliminar un reclamo (solo admin).
     */
    public function destroy(Reclamo $reclamo)
    {
        // BUG #9 FIX: Solo admin puede eliminar reclamos
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Solo el administrador puede eliminar reclamos.');
        }

        $reclamo->delete();
        return redirect()->route('reclamos.index')
            ->with('success', 'Reclamo eliminado.');
    }
}
