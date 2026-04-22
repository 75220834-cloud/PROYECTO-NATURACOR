<?php

namespace App\Http\Controllers;

use App\Models\Enfermedad;
use App\Models\Producto;
use Illuminate\Http\Request;

class RecetarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Enfermedad::with('productos')->where('activa', true);
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                  ->orWhere('categoria', 'like', "%{$request->search}%");
            });
        }
        $enfermedades = $query->orderBy('nombre')->get();
        return view('recetario.index', compact('enfermedades'));
    }

    public function create()
    {
        $productos = Producto::where('activo', true)->where('tipo', 'natural')->orderBy('nombre')->get();
        return view('recetario.create', compact('productos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'productos' => 'nullable|array',
            'productos.*.id' => 'exists:productos,id',
            'productos.*.instrucciones' => 'nullable|string',
        ]);

        $enfermedad = Enfermedad::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'categoria' => $data['categoria'] ?? null,
        ]);

        if (!empty($data['productos'])) {
            $sync = [];
            foreach ($data['productos'] as $i => $p) {
                $sync[$p['id']] = ['instrucciones' => $p['instrucciones'] ?? null, 'orden' => $i];
            }
            $enfermedad->productos()->sync($sync);
        }

        return redirect()->route('recetario.index')->with('success', 'Enfermedad registrada en el recetario.');
    }

    public function show(Enfermedad $recetario)
    {
        $recetario->load('productos');
        return view('recetario.show', compact('recetario'));
    }

    public function edit(Enfermedad $recetario)
    {
        $recetario->load('productos');
        $productos = Producto::where('activo', true)->where('tipo', 'natural')->orderBy('nombre')->get();
        return view('recetario.edit', compact('recetario', 'productos'));
    }

    public function update(Request $request, Enfermedad $recetario)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'productos' => 'nullable|array',
        ]);

        $recetario->update($data);
        $sync = [];
        foreach ($request->productos ?? [] as $i => $p) {
            $sync[$p['id']] = ['instrucciones' => $p['instrucciones'] ?? null, 'orden' => $i];
        }
        $recetario->productos()->sync($sync);

        return redirect()->route('recetario.index')->with('success', 'Recetario actualizado.');
    }

    public function destroy(Enfermedad $recetario)
    {
        $recetario->delete();
        return redirect()->route('recetario.index')->with('success', 'Eliminado del recetario.');
    }
}
