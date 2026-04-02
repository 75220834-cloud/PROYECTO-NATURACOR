@extends('layouts.app')
@section('title', 'Usuarios del Sistema')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">👥 Usuarios del Sistema</h4>
        <small class="text-muted">Gestión de accesos y roles</small>
    </div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show rounded-3">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                        <th class="px-4 py-3">Usuario</th>
                        <th>Rol</th>
                        <th>Sucursal</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#bbf7d0,#86efac);display:flex;align-items:center;justify-content:center;font-weight:700;color:#15803d;font-size:14px;">
                                    {{ strtoupper(substr($usuario->name,0,1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:14px;">{{ $usuario->name }}</div>
                                    <small class="text-muted">{{ $usuario->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @foreach($usuario->roles as $rol)
                                <span class="badge {{ $rol->name === 'admin' ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size:11px;">
                                    {{ $rol->name === 'admin' ? '⭐ Admin' : '👤 Empleado' }}
                                </span>
                            @endforeach
                        </td>
                        <td style="font-size:13px;">{{ $usuario->sucursal?->nombre ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $usuario->activo ? 'bg-success' : 'bg-secondary' }}" style="font-size:10px;">
                                {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-center text-muted" style="font-size:12px;">{{ $usuario->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-light btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($usuario->id !== auth()->id())
                                <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" style="display:inline;"
                                    data-confirm="¿Eliminar al usuario '{{ $usuario->name }}'?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm text-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No hay usuarios registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($usuarios->hasPages())
    <div class="card-footer bg-white border-top-0 px-4 py-3">{{ $usuarios->links() }}</div>
    @endif
</div>
@endsection
