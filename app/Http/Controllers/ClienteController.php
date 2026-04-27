<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use App\Services\Recommendation\PerfilSaludService;
use App\Services\Recommendation\RecomendacionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::withCount('ventas')
            ->withSum('ventas', 'total')
            ->addSelect([
                // Total gastado en productos naturales (detalles de venta)
                'total_productos' => \App\Models\DetalleVenta::selectRaw('COALESCE(SUM(detalle_ventas.subtotal), 0)')
                    ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                    ->whereColumn('ventas.cliente_id', 'clientes.id')
                    ->where('ventas.estado', 'completada'),
            ]);
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('dni', 'like', "%{$request->search}%")
                  ->orWhere('nombre', 'like', "%{$request->search}%")
                  ->orWhere('apellido', 'like', "%{$request->search}%");
            });
        }
        $clientes = $query->orderBy('nombre')->paginate(20);
        return view('clientes.index', compact('clientes'));
    }

    public function create() { return view('clientes.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dni' => 'required|string|max:20|unique:clientes',
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:20',
        ]);
        $cliente = Cliente::create($data);
        if ($request->expectsJson()) return response()->json($cliente);
        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente registrado.');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load(['ventas.detalles', 'canjes']);
        $totalCompras = $cliente->ventas()->where('estado', 'completada')->sum('total');
        $canjesPendientes = $cliente->canjes()->where('created_at', '>=', now()->subDays(30))->count();
        return view('clientes.show', compact('cliente', 'totalCompras', 'canjesPendientes'));
    }

    public function edit(Cliente $cliente) { return view('clientes.edit', compact('cliente')); }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:20',
        ]);
        $cliente->update($data);
        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    // Búsqueda AJAX por DNI
    public function buscarDni(Request $request)
    {
        $cliente = Cliente::where('dni', $request->dni)->first();
        if ($cliente) return response()->json(['found' => true, 'cliente' => $cliente]);
        return response()->json(['found' => false]);
    }
    // Autocompletado para POS (búsqueda por DNI o nombre)
    public function autocompletar(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $clientes = Cliente::where('dni', 'like', "%{$q}%")
            ->orWhere('nombre', 'like', "%{$q}%")
            ->orWhere('apellido', 'like', "%{$q}%")
            ->orderBy('nombre')
            ->limit(6)
            ->get(['id', 'dni', 'nombre', 'apellido', 'acumulado_naturales']);

        return response()->json($clientes->map(fn($c) => [
            'id'       => $c->id,
            'dni'      => $c->dni,
            'nombre'   => $c->nombreCompleto(),
            'acumulado'=> (float) $c->acumulado_naturales,
        ]));
    }
    public function padecimientos(Cliente $cliente)
    {
        $padecimientos = $cliente->padecimientos()
            ->with('enfermedad:id,nombre,categoria')
            ->get()
            ->map(fn($p) => [
                'id'     => $p->enfermedad->id,
                'nombre' => $p->enfermedad->nombre,
            ]);

        $enfermedades = \App\Models\Enfermedad::where('activa', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'categoria']);

        return response()->json([
            'padecimientos' => $padecimientos,
            'enfermedades'  => $enfermedades,
        ]);
    }

    public function guardarPadecimientos(
        Request $request,
        Cliente $cliente,
        PerfilSaludService $perfilSalud,
        RecomendacionEngine $engine
    ) {
        $request->validate([
            'enfermedad_ids'   => 'nullable|array',
            'enfermedad_ids.*' => 'exists:enfermedades,id',
        ]);

        $ids = $request->enfermedad_ids ?? [];

        // Sincronizar: borrar los que no están y agregar los nuevos
        \App\Models\ClientePadecimiento::where('cliente_id', $cliente->id)
            ->whereNotIn('enfermedad_id', $ids)
            ->delete();

        foreach ($ids as $enfermedadId) {
            \App\Models\ClientePadecimiento::firstOrCreate([
                'cliente_id'    => $cliente->id,
                'enfermedad_id' => $enfermedadId,
            ], [
                'registrado_por' => auth()->id(),
            ]);
        }

        // [BUG 4 FIX] Invalidar caché y recomputar perfil tras cambio de padecimientos.
        // Sin esto, el JSON de recomendaciones quedaba obsoleto hasta REC_CACHE_MINUTOS
        // (10 min default) para cualquier consumidor que no enviara ?refresh=1.
        // Errores aquí no deben tumbar la respuesta al cliente: degradación silenciosa
        // y log para diagnóstico.
        try {
            $perfilSalud->reconstruirPerfil($cliente->id);
            $engine->invalidarCacheCliente($cliente->id);
        } catch (\Throwable $e) {
            Log::warning('No se pudo refrescar caché de recomendaciones tras guardar padecimientos', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
            ]);
        }

        $padecimientos = $cliente->padecimientos()
            ->with('enfermedad:id,nombre')
            ->get()
            ->map(fn($p) => [
                'id'     => $p->enfermedad->id,
                'nombre' => $p->enfermedad->nombre,
            ]);

        return response()->json(['padecimientos' => $padecimientos]);
    }
    public function enfermedades(Cliente $cliente)
    {
        $cliente->load('padecimientos.enfermedad');
        $padecimientos = $cliente->padecimientos->pluck('enfermedad');
        return view('clientes.enfermedades', compact('cliente', 'padecimientos'));
    }

    public function enfermedadesStore(Request $request, Cliente $cliente)
    {
        $request->validate([
            'enfermedad_id' => 'required|exists:enfermedades,id',
        ]);

        $cliente->padecimientos()->updateOrCreate(
            ['enfermedad_id' => $request->enfermedad_id],
            ['registrado_por' => auth()->id()]
        );

        return redirect()->route('clientes.enfermedades', $cliente)
            ->with('success', 'Enfermedad agregada.');
    }

    public function enfermedadesDestroy(Cliente $cliente, $enfermedadId)
    {
        $cliente->padecimientos()->where('enfermedad_id', $enfermedadId)->delete();
        return redirect()->route('clientes.enfermedades', $cliente)
            ->with('success', 'Enfermedad eliminada.');
    }
}
