<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::withCount(['usuarios', 'ventas', 'productos'])
            ->latest()->get();
        return view('sucursales.index', compact('sucursales'));
    }

    public function create()
    {
        return view('sucursales.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono'  => 'nullable|string|max:30',
            'ruc'       => 'nullable|string|max:20',
        ]);
        $data['activa'] = true;
        Sucursal::create($data);
        return redirect()->route('sucursales.index')->with('success', 'Sucursal creada correctamente.');
    }

    public function show(Sucursal $sucursale)
    {
        $sucursale->load(['usuarios', 'productos', 'ventas']);
        return view('sucursales.show', compact('sucursale'));
    }

    public function edit(Sucursal $sucursale)
    {
        return view('sucursales.edit', compact('sucursale'));
    }

    public function update(Request $request, Sucursal $sucursale)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono'  => 'nullable|string|max:30',
            'ruc'       => 'nullable|string|max:20',
        ]);
        $data['activa'] = $request->boolean('activa');
        $sucursale->update($data);
        return redirect()->route('sucursales.index')->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Sucursal $sucursale)
    {
        $sucursale->delete();
        return redirect()->route('sucursales.index')->with('success', 'Sucursal eliminada.');
    }
}
