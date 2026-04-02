<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::withCount('ventas')->withSum('ventas', 'total');
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
}
