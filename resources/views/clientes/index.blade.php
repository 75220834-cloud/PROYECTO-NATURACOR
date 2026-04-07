@extends('layouts.app')
@section('title', 'Clientes')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">👥 Gestión de Clientes</h4>
        <small class="text-muted">Fidelización y historial de compras</small>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-person-plus-fill me-1"></i> Nuevo Cliente
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
    {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show rounded-3">
    {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                class="form-control form-control-sm rounded-3" placeholder="🔍 Buscar por DNI, nombre o apellido..." style="max-width:360px;">
            <button class="btn btn-success btn-sm px-3">Buscar</button>
            @if(request()->anyFilled(['search']))
                <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary btn-sm">✕ Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr style="background:#f0fdf4; font-size:12px; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">
                        <th class="px-4 py-3">Cliente</th>
                        <th>DNI</th>
                        <th>Teléfono</th>
                        <th class="text-center">Compras</th>
                        <th class="text-end">Total Productos</th>
                        <th class="text-center">Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#bbf7d0,#86efac);display:flex;align-items:center;justify-content:center;font-weight:700;color:#15803d;font-size:14px;">
                                    {{ strtoupper(substr($cliente->nombre,0,1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:14px;">{{ $cliente->nombre }} {{ $cliente->apellido }}</div>
                                    @if($cliente->email)<small class="text-muted">{{ $cliente->email }}</small>@endif
                                </div>
                            </div>
                        </td>
                        <td><code style="font-size:13px;">{{ $cliente->dni }}</code></td>
                        <td>{{ $cliente->telefono ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;">{{ $cliente->ventas_count }} ventas</span>
                        </td>
                        <td class="text-end">
                            <div class="fw-semibold" style="color:#16a34a;">
                                S/ {{ number_format($cliente->total_productos ?? 0, 2) }}
                            </div>
                            @if(($cliente->acumulado_naturales ?? 0) > 0)
                            <small class="text-muted">💚 Acum: S/{{ number_format($cliente->acumulado_naturales, 2) }}/500</small>
                            @endif
                        </td>
                        <td class="text-center text-muted" style="font-size:12px;">
                            {{ $cliente->created_at->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-light btn-sm" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-light btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                                    style="display:inline;"
                                    data-confirm="¿Eliminar al cliente {{ $cliente->nombre }} {{ $cliente->apellido }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm text-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-people" style="font-size:36px; opacity:0.3;"></i>
                            <div class="mt-2">No hay clientes registrados</div>
                            <a href="{{ route('clientes.create') }}" class="btn btn-success btn-sm mt-2">Registrar primer cliente</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($clientes->hasPages())
    <div class="card-footer bg-white border-top-0 px-4 py-3">
        {{ $clientes->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
