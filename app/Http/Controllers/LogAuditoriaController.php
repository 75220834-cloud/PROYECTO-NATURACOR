<?php

namespace App\Http\Controllers;

use App\Models\LogAuditoria;
use Illuminate\Http\Request;

class LogAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = LogAuditoria::with(['usuario', 'sucursal'])
            ->orderBy('created_at', 'desc');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->accion) {
            $query->where('accion', 'like', "%{$request->accion}%");
        }
        if ($request->fecha) {
            $query->whereDate('created_at', $request->fecha);
        }

        $logs = $query->paginate(30);
        $usuarios = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('logs.index', compact('logs', 'usuarios'));
    }
}
