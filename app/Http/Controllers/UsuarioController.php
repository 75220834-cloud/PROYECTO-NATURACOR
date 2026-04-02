<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('sucursal')->with('roles')->latest()->paginate(20);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $sucursales = Sucursal::where('activa', true)->get();
        return view('usuarios.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|string|min:8|confirmed',
            'rol'         => 'required|in:admin,empleado',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);
        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'sucursal_id' => $data['sucursal_id'] ?? null,
            'activo'      => true,
        ]);
        $user->assignRole($data['rol']);
        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $usuario)
    {
        $usuario->load(['sucursal', 'roles', 'ventas', 'cajaSesiones']);
        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $sucursales = Sucursal::where('activa', true)->get();
        return view('usuarios.edit', compact('usuario', 'sucursales'));
    }

    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $usuario->id,
            'password'    => 'nullable|string|min:8|confirmed',
            'rol'         => 'required|in:admin,empleado',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);
        $usuario->update([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'sucursal_id' => $data['sucursal_id'] ?? null,
            'activo'      => $request->boolean('activo'),
        ]);
        if ($data['password'] ?? null) {
            $usuario->update(['password' => Hash::make($data['password'])]);
        }
        $usuario->syncRoles([$data['rol']]);
        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }
        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado.');
    }
}
