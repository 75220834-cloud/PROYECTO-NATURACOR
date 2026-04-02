@extends('layouts.app')
@section('title', 'Reclamos')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📋 Gestión de Reclamos</h4>
        <small class="text-muted">Registro y seguimiento de quejas y reclamos de clientes</small>
    </div>
    <a href="{{ route('reclamos.create') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Reclamo
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
    {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="estado" class="form-select form-select-sm rounded-3" style="max-width:180px;">
                <option value="">Todos los estados</option>
                <option value="pendiente"   @selected(request('estado')=='pendiente')>🟡 Pendiente</option>
                <option value="en_proceso"  @selected(request('estado')=='en_proceso')>🔵 En proceso</option>
                <option value="resuelto"    @selected(request('estado')=='resuelto')>🟢 Resuelto</option>
            </select>
            <select name="tipo" class="form-select form-select-sm rounded-3" style="max-width:180px;">
                <option value="">Todos los tipos</option>
                <option value="producto"  @selected(request('tipo')=='producto')>Producto</option>
                <option value="servicio"  @selected(request('tipo')=='servicio')>Servicio</option>
                <option value="otro"      @selected(request('tipo')=='otro')>Otro</option>
            </select>
            <button class="btn btn-success btn-sm px-3">Filtrar</button>
            @if(request()->anyFilled(['estado','tipo']))
                <a href="{{ route('reclamos.index') }}" class="btn btn-outline-secondary btn-sm">✕ Limpiar</a>
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
                        <th class="px-4 py-3">#</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Escalado</th>
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reclamos as $reclamo)
                    <tr>
                        <td class="px-4"><code>{{ $reclamo->id }}</code></td>
                        <td>
                            @if($reclamo->cliente)
                                <span class="fw-semibold">{{ $reclamo->cliente->nombre }} {{ $reclamo->cliente->apellido }}</span>
                                <br><small class="text-muted">{{ $reclamo->cliente->dni }}</small>
                            @else
                                <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $tipos = ['producto' => 'danger', 'servicio' => 'warning', 'otro' => 'secondary'];
                                $color = $tipos[$reclamo->tipo] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }} rounded-pill border border-{{ $color }}">
                                {{ ucfirst($reclamo->tipo) }}
                            </span>
                        </td>
                        <td style="max-width:250px; font-size:13px;">
                            {{ Str::limit($reclamo->descripcion, 60) }}
                        </td>
                        <td class="text-center">
                            @php
                                $estados = ['pendiente'=>['🟡','warning'], 'en_proceso'=>['🔵','primary'], 'resuelto'=>['🟢','success']];
                                [$icon, $col] = $estados[$reclamo->estado] ?? ['⚪','secondary'];
                            @endphp
                            <span class="badge rounded-pill bg-{{ $col }}-subtle text-{{ $col }} border border-{{ $col }}">
                                {{ $icon }} {{ ucwords(str_replace('_',' ',$reclamo->estado)) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($reclamo->escalado)
                                <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill">🔺 Sí</span>
                            @else
                                <span class="badge bg-light text-muted rounded-pill">No</span>
                            @endif
                        </td>
                        <td class="text-center text-muted" style="font-size:12px;">
                            {{ $reclamo->created_at->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('reclamos.show', $reclamo) }}" class="btn btn-light btn-sm" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(!$reclamo->escalado && $reclamo->estado !== 'resuelto')
                                <form method="POST" action="{{ route('reclamos.escalar', $reclamo) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm" title="Escalar al administrador">
                                        <i class="bi bi-arrow-up-circle"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-x" style="font-size:36px; opacity:0.3;"></i>
                            <div class="mt-2">No hay reclamos registrados</div>
                            <a href="{{ route('reclamos.create') }}" class="btn btn-success btn-sm mt-2">Registrar primer reclamo</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reclamos->hasPages())
    <div class="card-footer bg-white border-top-0 px-4 py-3">
        {{ $reclamos->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
