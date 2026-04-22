@extends('layouts.app')
@section('title', 'Resultados del Reporte')
@section('page-title', 'Resultados del Reporte')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('reportes.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">📊 Resultados del Reporte</h4>
        <small class="text-muted">{{ $ventas->count() }} ventas encontradas</small>
    </div>
    <form action="{{ route('reportes.generar') }}" method="POST" class="ms-auto">
        @csrf
        <input type="hidden" name="exportar" value="pdf">
        <button type="submit" class="btn btn-danger btn-sm px-3">
            <i class="bi bi-file-pdf me-1"></i> Exportar PDF
        </button>
    </form>
</div>

{{-- KPIs Ventas --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-green mx-auto mb-2"><i class="bi bi-receipt"></i></div>
            <div class="kpi-value">{{ $totales['ventas'] }}</div>
            <div class="kpi-label">Ventas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-green mx-auto mb-2"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-value">S/ {{ number_format($totales['total'],2) }}</div>
            <div class="kpi-label">Total facturado</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-amber mx-auto mb-2"><i class="bi bi-wallet2"></i></div>
            <div class="kpi-value">S/ {{ number_format($totales['efectivo'],2) }}</div>
            <div class="kpi-label">Efectivo</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-blue mx-auto mb-2"><i class="bi bi-phone"></i></div>
            <div class="kpi-value">S/ {{ number_format($totales['yape'] + $totales['plin'],2) }}</div>
            <div class="kpi-label">Yape + Plin</div>
        </div>
    </div>
</div>

{{-- KPIs Egresos --}}
@if($egresos->count() > 0)
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card text-center" style="border-left: 3px solid rgba(231,76,60,0.6);">
            <div class="kpi-icon kpi-icon-rose mx-auto mb-2"><i class="bi bi-arrow-down-circle"></i></div>
            <div class="kpi-value" style="color:#e74c3c;">{{ $egresos->count() }}</div>
            <div class="kpi-label">Egresos registrados</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card text-center" style="border-left: 3px solid rgba(231,76,60,0.6);">
            <div class="kpi-icon kpi-icon-rose mx-auto mb-2"><i class="bi bi-dash-circle"></i></div>
            <div class="kpi-value" style="color:#e74c3c;">- S/ {{ number_format($totales['egresos'],2) }}</div>
            <div class="kpi-label">Total egresos</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card text-center" style="border-left: 3px solid rgba(40,199,111,0.6);">
            <div class="kpi-icon kpi-icon-green mx-auto mb-2"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-value" style="color:var(--neon);">S/ {{ number_format($totales['neto'],2) }}</div>
            <div class="kpi-label">Neto (ventas − egresos)</div>
        </div>
    </div>
</div>

{{-- Tabla de egresos --}}
<div class="nc-card mb-4">
    <div class="nc-card-header">
        <span style="color:#e74c3c;"><i class="bi bi-arrow-down-circle me-2"></i>Detalle de Egresos</span>
    </div>
    <div class="table-responsive">
        <table class="table nc-table mb-0 align-middle">
            <thead>
                <tr>
                    <th class="px-4">Fecha</th>
                    <th>Descripción</th>
                    <th>Empleado</th>
                    <th class="text-end px-4">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($egresos as $egreso)
                <tr>
                    <td class="px-4" style="font-size:12px;">{{ $egreso->created_at->format('d/m/Y H:i') }}</td>
                    <td style="font-size:13px;">{{ $egreso->descripcion }}</td>
                    <td style="font-size:13px;">{{ $egreso->empleado?->name ?? '—' }}</td>
                    <td class="text-end px-4 fw-bold" style="color:#e74c3c;">- S/ {{ number_format($egreso->monto,2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: rgba(231,76,60,0.10); border-top: 1px solid rgba(231,76,60,0.25);">
                    <td colspan="3" class="px-4 py-3 text-end fw-bold" style="color:var(--text-sec); font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Total Egresos:</td>
                    <td class="text-end px-4 fw-bold" style="color:#e74c3c; font-size:17px;">- S/ {{ number_format($totales['egresos'],2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Tabla de ventas --}}
<div class="nc-card">
    <div class="table-responsive">
        <table class="table nc-table mb-0 align-middle">
            <thead>
                <tr>
                    <th class="px-4">Boleta</th>
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
                    <td class="px-4">
                        <code style="font-size:12px; color:var(--neon);">{{ $venta->numero_boleta ?? 'N/A' }}</code>
                    </td>
                    <td style="font-size:12px;">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                    <td style="font-size:13px;">{{ $venta->cliente?->nombre ?? 'Cliente general' }}</td>
                    <td style="font-size:13px;">{{ $venta->empleado?->name ?? '—' }}</td>
                    <td><span class="badge-natural">{{ $venta->metodo_pago }}</span></td>
                    <td class="text-end px-4 fw-bold" style="color:var(--neon);">S/ {{ number_format($venta->total,2) }}</td>
                    <td>
                        <a href="{{ route('boletas.show', $venta) }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">No hay ventas para estos filtros</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                {{-- Total ventas --}}
                <tr style="background: rgba(40,199,111,0.10); border-top: 1px solid rgba(40,199,111,0.20);">
                    <td colspan="5" class="px-4 py-3 text-end fw-bold" style="color:var(--text-sec); font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Total Ventas:</td>
                    <td class="text-end px-4 fw-bold" style="color:var(--neon); font-size:17px;">S/ {{ number_format($totales['total'],2) }}</td>
                    <td></td>
                </tr>
                @if($egresos->count() > 0)
                {{-- Egresos --}}
                <tr style="background: rgba(231,76,60,0.08); border-top: 1px solid rgba(231,76,60,0.15);">
                    <td colspan="5" class="px-4 py-2 text-end fw-bold" style="color:var(--text-sec); font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Egresos:</td>
                    <td class="text-end px-4 fw-bold" style="color:#e74c3c; font-size:15px;">- S/ {{ number_format($totales['egresos'],2) }}</td>
                    <td></td>
                </tr>
                {{-- Neto final --}}
                <tr style="background: rgba(40,199,111,0.15); border-top: 2px solid rgba(40,199,111,0.40);">
                    <td colspan="5" class="px-4 py-3 text-end fw-bold" style="color:rgba(255,255,255,0.88); font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">Neto Final:</td>
                    <td class="text-end px-4 fw-bold" style="color:var(--neon); font-size:20px;">S/ {{ number_format($totales['neto'],2) }}</td>
                    <td></td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>
@endsection
