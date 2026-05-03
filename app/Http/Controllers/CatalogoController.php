<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\CordialVenta;
use App\Models\Enfermedad;

class CatalogoController extends Controller
{
    public function index()
    {
        $search     = request('search');
        $beneficio  = request('beneficio');   // enfermedad_id
        $tipo       = request('tipo');        // natural | cordial

        $query = Producto::where('activo', true)
            ->where('stock', '>', 0);

        if ($search) {
            $query->where('nombre', 'like', '%' . $search . '%');
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if ($beneficio) {
            $query->whereHas('enfermedades', function ($q) use ($beneficio) {
                $q->where('enfermedades.id', $beneficio);
            });
        }

        $productos = $query->with('enfermedades')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        // Beneficios disponibles (enfermedades activas que tienen productos)
        $beneficios = Enfermedad::where('activa', true)
            ->whereHas('productos', function ($q) {
                $q->where('activo', true)->where('stock', '>', 0);
            })
            ->orderBy('nombre')
            ->get();

        $cordiales = collect(CordialVenta::$labels)
            ->filter(fn($label, $key) => $key !== 'invitado')
            ->map(fn($label, $key) => [
                'tipo'   => $key,
                'label'  => $label,
                'precio' => CordialVenta::$precios[$key] ?? 0,
            ]);

        $whatsapp = config('naturacor.empresa.whatsapp', '932857118');

        return view('catalogo.index', compact('productos', 'cordiales', 'whatsapp', 'search', 'beneficios', 'beneficio', 'tipo'));
    }
}
