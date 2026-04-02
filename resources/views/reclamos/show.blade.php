@extends('layouts.app')
@section('title', 'Detalle del Reclamo')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📋 Reclamo #{{ $reclamo->id }}</h4>
        <small class="text-muted">Detalle y gestión del reclamo</small>
    </div>
    <a href="{{ route('reclamos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3">
    {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    {{-- Información del reclamo --}}
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:11px; letter-spacing:1px;">Información del Reclamo</h6>

                <table class="table table-borderless table-sm">
                    <tr>
                        <th class="text-muted" style="width:140px; font-size:13px;">Tipo:</th>
                        <td>
                            @php $tipos = ['producto'=>'danger','servicio'=>'warning','otro'=>'secondary']; @endphp
                            <span class="badge bg-{{ $tipos[$reclamo->tipo] ?? 'secondary' }}-subtle text-{{ $tipos[$reclamo->tipo] ?? 'secondary' }} border border-{{ $tipos[$reclamo->tipo] ?? 'secondary' }} rounded-pill">
                                {{ ucfirst($reclamo->tipo) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted" style="font-size:13px;">Estado:</th>
                        <td>
                            @php $estados = ['pendiente'=>['🟡','warning'],'en_proceso'=>['🔵','primary'],'resuelto'=>['🟢','success']]; [$ico,$col] = $estados[$reclamo->estado] ?? ['⚪','secondary']; @endphp
                            <span class="badge rounded-pill bg-{{ $col }}-subtle text-{{ $col }} border border-{{ $col }}">
                                {{ $ico }} {{ ucwords(str_replace('_',' ',$reclamo->estado)) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted" style="font-size:13px;">Escalado:</th>
                        <td>
                            @if($reclamo->escalado)
                                <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill">🔺 Sí — Escalado al administrador</span>
                            @else
                                <span class="text-muted">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted" style="font-size:13px;">Vendedor:</th>
                        <td>{{ $reclamo->vendedor->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted" style="font-size:13px;">Fecha:</th>
                        <td>{{ $reclamo->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($reclamo->adminResolutor)
                    <tr>
                        <th class="text-muted" style="font-size:13px;">Resuelto por:</th>
                        <td>{{ $reclamo->adminResolutor->name }}</td>
                    </tr>
                    @endif
                </table>

                <div class="mt-3">
                    <p class="text-muted mb-1" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Descripción</p>
                    <div class="p-3 rounded-3" style="background:#f9fafb; font-size:14px; border-left: 3px solid #16a34a;">
                        {{ $reclamo->descripcion }}
                    </div>
                </div>

                @if($reclamo->resolucion)
                <div class="mt-3">
                    <p class="text-muted mb-1" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Resolución</p>
                    <div class="p-3 rounded-3" style="background:#f0fdf4; font-size:14px; border-left: 3px solid #22c55e;">
                        {{ $reclamo->resolucion }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="col-md-5">
        {{-- Cliente --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:11px; letter-spacing:1px;">👤 Cliente</h6>
                @if($reclamo->cliente)
                    <div class="fw-semibold">{{ $reclamo->cliente->nombre }} {{ $reclamo->cliente->apellido }}</div>
                    <div class="text-muted" style="font-size:13px;">DNI: {{ $reclamo->cliente->dni }}</div>
                    @if($reclamo->cliente->telefono)
                        <div class="text-muted" style="font-size:13px;">📞 {{ $reclamo->cliente->telefono }}</div>
                    @endif
                @else
                    <p class="text-muted mb-0">Cliente no identificado / anónimo</p>
                @endif
            </div>
        </div>

        {{-- Cambiar estado (admin) --}}
        @if($reclamo->estado !== 'resuelto')
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:11px; letter-spacing:1px;">⚙️ Actualizar Estado</h6>
                <form method="POST" action="{{ route('reclamos.update', $reclamo) }}">
                    @csrf @method('PUT')
                    <div class="mb-2">
                        <select name="estado" class="form-select form-select-sm rounded-3" required>
                            <option value="pendiente"  @selected($reclamo->estado=='pendiente')>🟡 Pendiente</option>
                            <option value="en_proceso" @selected($reclamo->estado=='en_proceso')>🔵 En proceso</option>
                            <option value="resuelto"   @selected($reclamo->estado=='resuelto')>🟢 Resuelto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea name="resolucion" rows="3" class="form-control form-control-sm rounded-3"
                            placeholder="Describe la resolución aplicada...">{{ old('resolucion', $reclamo->resolucion) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-check-circle me-1"></i> Actualizar
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Escalar --}}
        @if(!$reclamo->escalado && $reclamo->estado !== 'resuelto')
        <div class="card border-0 shadow-sm rounded-4 border-warning">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2" style="color:#b45309; font-size:13px;">🔺 Escalar al Administrador</h6>
                <p class="text-muted mb-3" style="font-size:13px;">Si el reclamo no puede resolverse en tienda, escálalo para que el administrador lo atienda directamente.</p>
                <form method="POST" action="{{ route('reclamos.escalar', $reclamo) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-arrow-up-circle me-1"></i> Escalar Reclamo
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
