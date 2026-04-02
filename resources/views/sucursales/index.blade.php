@extends('layouts.app')
@section('title', 'Sucursales')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">🏪 Sucursales</h4>
        <small class="text-muted">Gestión de sedes del negocio</small>
    </div>
    <a href="{{ route('sucursales.create') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-plus-circle me-1"></i> Nueva Sucursal
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show rounded-3">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">
    @forelse($sucursales as $suc)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#bbf7d0,#86efac);display:flex;align-items:center;justify-content:center;font-size:22px;">🏪</div>
                    <div>
                        <h6 class="fw-bold mb-0">{{ $suc->nombre }}</h6>
                        <span class="badge {{ $suc->activa ? 'bg-success' : 'bg-secondary' }}" style="font-size:10px;">
                            {{ $suc->activa ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                </div>
                <hr>
                <div class="mb-2" style="font-size:13px;">
                    @if($suc->direccion)<div><i class="bi bi-geo-alt text-muted me-2"></i>{{ $suc->direccion }}</div>@endif
                    @if($suc->telefono)<div><i class="bi bi-telephone text-muted me-2"></i>{{ $suc->telefono }}</div>@endif
                    @if($suc->ruc)<div><i class="bi bi-building text-muted me-2"></i>RUC: {{ $suc->ruc }}</div>@endif
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4 text-center" style="background:#f0fdf4;border-radius:8px;padding:8px;">
                        <div class="fw-bold" style="color:#22c55e;">{{ $suc->usuarios_count }}</div>
                        <small class="text-muted">Usuarios</small>
                    </div>
                    <div class="col-4 text-center" style="background:#f0fdf4;border-radius:8px;padding:8px;">
                        <div class="fw-bold" style="color:#22c55e;">{{ $suc->productos_count }}</div>
                        <small class="text-muted">Productos</small>
                    </div>
                    <div class="col-4 text-center" style="background:#f0fdf4;border-radius:8px;padding:8px;">
                        <div class="fw-bold" style="color:#22c55e;">{{ $suc->ventas_count }}</div>
                        <small class="text-muted">Ventas</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('sucursales.edit', $suc) }}" class="btn btn-outline-success btn-sm flex-grow-1">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                    <form method="POST" action="{{ route('sucursales.destroy', $suc) }}"
                        style="display:inline;"
                        data-confirm="¿Eliminar la sucursal '{{ $suc->nombre }}'? Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-building" style="font-size:48px;opacity:0.3;"></i>
                <div class="mt-2">No hay sucursales registradas</div>
                <a href="{{ route('sucursales.create') }}" class="btn btn-success btn-sm mt-3">Crear primera sucursal</a>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
