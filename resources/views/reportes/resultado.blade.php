@extends('layouts.app')
@section('title', 'Resultados del Reporte')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('reportes.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📊 Resultados del Reporte</h4>
        <small class="text-muted">{{ $ventas->count() }} ventas encontradas</small>
    </div>
    <form action="{{ route('reportes.generar') }}" method="POST" class="ms-auto">
        @csrf
        <input type="hidden" name="exportar" value="pdf">
        <button type="submit" class="btn btn-outline-danger btn-sm px-3">
            <i class="bi bi-file-pdf me-1"></i> Exportar PDF
        </button>
    </form>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="fw-bold" style="font-size:28px;color:#22c55e;">{{ $totales['ventas'] }}</div>
            <small class="text-muted">Ventas</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="fw-bold" style="font-size:28px;color:#22c55e;">S/ {{ number_format($totales['total'],2) }}</div>
            <small class="text-muted">Total facturado</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="fw-bold" style="font-size:28px;color:#22c55e;">S/ {{ number_format($totales['efectivo'],2) }}</div>
            <small class="text-muted">Efectivo</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="fw-bold" style="font-size:28px;color:#22c55e;">S/ {{ number_format($totales['yape'] + $totales['plin'],2) }}</div>
            <small class="text-muted">Yape + Plin</small>
        </div>
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
                        <td class="text-end px-4 fw-bold" style="color:#16a34a;">S/ {{ number_format($venta->total,2) }}</td>
                        <td>
                            <a href="{{ route('boletas.show', $venta) }}" class="btn btn-light btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No hay ventas para estos filtros</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:#f0fdf4;font-weight:700;">
                        <td colspan="5" class="px-4 py-3 text-end">TOTAL:</td>
                        <td class="text-end px-4" style="color:#16a34a;font-size:16px;">S/ {{ number_format($totales['total'],2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
