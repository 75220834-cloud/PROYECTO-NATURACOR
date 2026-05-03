<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\CordialVenta;

class CatalogoController extends Controller
{
    public function index()
    {
        $search = request('search');

        $query = Producto::where('activo', true)
            ->where('stock', '>', 0);

        if ($search) {
            $query->where('nombre', 'like', '%' . $search . '%');
        }

        $productos = $query->orderBy('tipo')
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

        return view('catalogo.index', compact('productos', 'cordiales', 'whatsapp', 'search'));
    }
}
