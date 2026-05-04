<?php

namespace App\Http\Controllers;

use App\Models\Valoracion;
use Illuminate\Http\Request;

class ValoracionController extends Controller
{
    /**
     * Guardar una valoración pública (sin login).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'producto_id'    => 'required|exists:productos,id',
            'nombre_cliente' => 'required|string|max:100',
            'estrellas'      => 'required|integer|min:1|max:5',
            'comentario'     => 'nullable|string|max:500',
        ]);

        $data['aprobada'] = false; // requiere moderación

        Valoracion::create($data);

        return back()->with('success', '¡Gracias por tu valoración! Será publicada pronto.');
    }

    /**
     * Panel de moderación (admin).
     */
    public function index()
    {
        $valoraciones = Valoracion::with('producto')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('valoraciones.index', compact('valoraciones'));
    }

    /**
     * Aprobar valoración.
     */
    public function aprobar(Valoracion $valoracion)
    {
        $valoracion->update(['aprobada' => true]);
        return back()->with('success', 'Valoración aprobada.');
    }

    /**
     * Rechazar (eliminar) valoración.
     */
    public function rechazar(Valoracion $valoracion)
    {
        $valoracion->delete();
        return back()->with('success', 'Valoración eliminada.');
    }
}
