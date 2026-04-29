@extends('layouts.app')
@section('title', $usuario->name)
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('usuarios.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">👤 {{ $usuario->name }}</h4>
        <small class="text-muted">{{ $usuario->email }}</small>
    </div>
    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-outline-success btn-sm ms-auto">
        <i class="bi bi-pencil me-1"></i> Editar
    </a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-4">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#bbf7d0,#86efac);display:flex;align-items:center;justify-content:center;font-weight:700;color:#15803d;font-size:28px;margin:0 auto 12px;">
                {{ strtoupper(substr($usuario->name,0,1)) }}
            </div>
            <h5 class="fw-bold">{{ $usuario->name }}</h5>
            <div class="mb-2">
                @foreach($usuario->roles as $rol)
                    <span class="badge {{ $rol->name === 'admin' ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $rol->name === 'admin' ? '⭐ Admin' : '👤 Empleado' }}
                    </span>
                @endforeach
            </div>
            <small class="text-muted">{{ $usuario->email }}</small>
            <hr>
            <table class="table table-sm mb-0 text-start">
                <thead class="visually-hidden">
                    <tr><th scope="col">Campo</th><th scope="col">Valor</th></tr>
                </thead>
                <tbody>
                    <tr><td class="text-muted">Sucursal</td><td>{{ $usuario->sucursal?->nombre ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Estado</td>
                        <td><span class="badge {{ $usuario->activo ? 'bg-success' : 'bg-secondary' }}">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    </tr>
                    <tr><td class="text-muted">Miembro desde</td><td>{{ $usuario->created_at->format('d/m/Y') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3 mb-3">
            <div class="col-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="fw-bold" style="font-size:24px;color:#22c55e;">{{ $usuario->ventas->count() }}</div>
                    <small class="text-muted">Ventas realizadas</small>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="fw-bold" style="font-size:24px;color:#22c55e;">{{ $usuario->cajaSesiones->count() }}</div>
                    <small class="text-muted">Sesiones de caja</small>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="fw-bold" style="font-size:24px;color:#22c55e;">S/ {{ number_format($usuario->ventas->sum('total'),2) }}</div>
                    <small class="text-muted">Total vendido</small>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 px-4 py-3">
                <h6 class="fw-bold mb-0">📋 Últimas ventas</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size:13px;">
                    <thead>
                        <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                            <th class="px-4 py-3">Boleta</th><th>Fecha</th><th>Cliente</th><th class="text-end px-4">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuario->ventas->sortByDesc('created_at')->take(5) as $venta)
                        <tr>
                            <td class="px-4"><code>{{ $venta->numero_boleta ?? 'N/A' }}</code></td>
                            <td>{{ $venta->created_at->format('d/m/Y') }}</td>
                            <td>{{ $venta->cliente?->nombre ?? 'General' }}</td>
                            <td class="text-end px-4 fw-semibold text-success">S/ {{ number_format($venta->total,2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3 text-muted">Sin ventas registradas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
