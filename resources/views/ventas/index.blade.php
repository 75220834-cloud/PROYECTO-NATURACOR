@extends('layouts.app')
@section('title', 'Historial de Ventas')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📋 Historial de Ventas</h4>
        <small class="text-muted">Registro completo de todas las transacciones</small>
    </div>
    <a href="{{ route('ventas.pos') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-cart-plus me-1"></i> Nueva Venta
    </a>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('ventas.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde', now()->startOfMonth()->format('Y-m-d')) }}" class="form-control rounded-3 form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta', now()->format('Y-m-d')) }}" class="form-control rounded-3 form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Pago</label>
                <select name="metodo_pago" class="form-select form-select-sm rounded-3">
                    <option value="">Todos</option>
                    @foreach(['efectivo','yape','plin','tarjeta','otro'] as $m)
                    <option value="{{ $m }}" {{ request('metodo_pago')==$m?'selected':'' }}>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-3 flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('ventas.index') }}" class="btn btn-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                        <th class="px-4 py-3">Boleta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Empleado</th>
                        <th>Pago</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end px-4">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $venta)
                    <tr>
                        <td class="px-4"><code style="font-size:12px;">{{ $venta->numero_boleta ?? 'N/A' }}</code></td>
                        <td style="font-size:12px;">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-size:13px;">{{ $venta->cliente?->nombre ?? 'Cliente general' }}</td>
                        <td style="font-size:13px;">{{ $venta->empleado?->name ?? '—' }}</td>
                        <td>
                            <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:11px;">{{ $venta->metodo_pago }}</span>
                        </td>
                        <td class="text-center">
                            @if($venta->estado === 'completada')
                                <span class="badge" style="background:#dcfce7;color:#15803d;">Completada</span>
                            @elseif($venta->estado === 'anulada')
                                <span class="badge" style="background:#fef2f2;color:#dc2626;">Anulada</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </td>
                        <td class="text-end px-4 fw-bold" style="color:#16a34a;">S/ {{ number_format($venta->total,2) }}</td>
                        <td class="px-2">
                            <a href="{{ route('boletas.show', $venta) }}" class="btn btn-light btn-sm" title="Ver boleta">
                                <i class="bi bi-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt" style="font-size:36px;opacity:0.3;"></i>
                            <div class="mt-2">No hay ventas en este período</div>
                            <a href="{{ route('ventas.pos') }}" class="btn btn-success btn-sm mt-2">Registrar venta</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($ventas->count() > 0)
                <tfoot>
                    <tr style="background:#f0fdf4;font-weight:700;">
                        <td colspan="6" class="px-4 py-3 text-end">TOTAL PERÍODO:</td>
                        <td class="text-end px-4" style="color:#16a34a;">S/ {{ number_format($ventas->sum('total'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @if($ventas->hasPages())
    <div class="card-footer bg-white border-top-0 px-4 py-3">
        {{ $ventas->links() }}
    </div>
    @endif
</div>
@endsection
