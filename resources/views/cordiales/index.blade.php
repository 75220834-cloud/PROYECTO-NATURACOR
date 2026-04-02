@extends('layouts.app')
@section('title', 'Ventas de Cordiales')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">🥤 Ventas de Cordiales</h4>
        <small class="text-muted">Historial de consumo de cordiales en tienda y para llevar</small>
    </div>
    <a href="{{ route('cordiales.create') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-plus-circle me-1"></i> Registrar Venta
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
    {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Precios de referencia --}}
<div class="row g-3 mb-4">
    @foreach($precios as $tipo => $precio)
        @if($tipo !== 'invitado')
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2 px-1">
                <div class="fw-bold" style="color:#16a34a; font-size:18px;">S/ {{ number_format($precio, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $labels[$tipo] ?? $tipo }}</div>
            </div>
        </div>
        @endif
    @endforeach
</div>

{{-- Filtro por fecha --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control form-control-sm rounded-3" style="max-width:180px;">
            <button class="btn btn-success btn-sm px-3">Filtrar</button>
            @if(request('fecha'))
                <a href="{{ route('cordiales.index') }}" class="btn btn-outline-secondary btn-sm">✕ Limpiar</a>
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
                        <th class="px-4 py-3">Tipo</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Precio Unit.</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Invitado</th>
                        <th>Vendedor</th>
                        <th class="text-center">Fecha / Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cordiales as $cordial)
                    <tr>
                        <td class="px-4">
                            <span class="fw-semibold" style="font-size:14px;">
                                {{ $labels[$cordial->tipo] ?? $cordial->tipo }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill" style="background:#dcfce7; color:#15803d;">{{ $cordial->cantidad }}</span>
                        </td>
                        <td class="text-end">
                            @if($cordial->es_invitado)
                                <span class="text-muted">Gratis</span>
                            @else
                                S/ {{ number_format($cordial->precio, 2) }}
                            @endif
                        </td>
                        <td class="text-end fw-semibold" style="color:#16a34a;">
                            @if($cordial->es_invitado)
                                <span class="text-muted">—</span>
                            @else
                                S/ {{ number_format($cordial->precio * $cordial->cantidad, 2) }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if($cordial->es_invitado)
                                <span class="badge bg-warning-subtle text-warning border border-warning rounded-pill">🎁 Invitado</span>
                            @else
                                <span class="badge bg-light text-muted rounded-pill">No</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:13px;">
                            {{ $cordial->venta->empleado->name ?? '—' }}
                        </td>
                        <td class="text-center text-muted" style="font-size:12px;">
                            {{ $cordial->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-cup-hot" style="font-size:36px; opacity:0.3;"></i>
                            <div class="mt-2">No hay ventas de cordiales registradas</div>
                            <a href="{{ route('cordiales.create') }}" class="btn btn-success btn-sm mt-2">Registrar primera venta</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cordiales->hasPages())
    <div class="card-footer bg-white border-top-0 px-4 py-3">
        {{ $cordiales->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
