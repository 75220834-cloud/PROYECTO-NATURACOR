@extends('layouts.app')
@section('title', 'Reportes de Ventas')
@section('page-title', 'Reportes de Ventas')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">📊 Reportes de Ventas</h4>
        <small class="text-muted">Genera reportes con filtros avanzados</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-naturacor alert-dismissible fade show mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filtros -->
<div class="nc-card mb-4">
    <div class="nc-card-header">
        <span><i class="bi bi-funnel me-2" style="color:var(--neon);"></i>Filtros de búsqueda</span>
    </div>
    <form action="{{ route('reportes.generar') }}" method="POST" id="reporteForm">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="fecha_desde" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="form-control" id="fecha_desde">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="fecha_hasta" value="{{ now()->format('Y-m-d') }}" class="form-control" id="fecha_hasta">
            </div>
            <div class="col-md-3">
                <label class="form-label">Método de pago</label>
                <select name="metodo_pago" class="form-select">
                    <option value="">Todos</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>
            @if(auth()->user()->isAdmin())
            <div class="col-md-3">
                <label class="form-label">Sucursal</label>
                <select name="sucursal_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($sucursales as $suc)
                    <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Empleado</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($empleados as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Producto</label>
                <select name="producto_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($productos as $prod)
                    <option value="{{ $prod->id }}">{{ $prod->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-search me-1"></i> Generar Reporte
            </button>
            <button type="submit" name="exportar" value="pdf" class="btn btn-danger px-4">
                <i class="bi bi-file-pdf me-1"></i> Exportar PDF
            </button>
            <button type="button" onclick="setHoy()" class="btn btn-secondary px-3">Hoy</button>
            <button type="button" onclick="setMes()" class="btn btn-secondary px-3">Este mes</button>
            <button type="button" onclick="setSemana()" class="btn btn-secondary px-3">Esta semana</button>
        </div>
    </form>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-green mx-auto mb-2">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="kpi-value">—</div>
            <div class="kpi-label">Total ventas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-green mx-auto mb-2">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="kpi-value">S/ —</div>
            <div class="kpi-label">Ingresos totales</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-blue mx-auto mb-2">
                <i class="bi bi-phone"></i>
            </div>
            <div class="kpi-value">S/ —</div>
            <div class="kpi-label">Yape / Plin</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card text-center">
            <div class="kpi-icon kpi-icon-amber mx-auto mb-2">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="kpi-value">S/ —</div>
            <div class="kpi-label">Efectivo</div>
        </div>
    </div>
</div>

<!-- Resultados vacíos -->
<div class="nc-card">
    <div class="text-center py-5" style="color: var(--text-sec);">
        <i class="bi bi-bar-chart-line" style="font-size:48px; opacity:0.3;"></i>
        <div class="mt-3" style="font-size:14px;">Selecciona los filtros y genera el reporte para ver los resultados aquí.</div>
    </div>
</div>

@if(isset($reporte))
<!-- Totales del reporte generado -->
<div class="nc-card mt-4">
    <div class="nc-card-header">
        <span><i class="bi bi-calculator me-2" style="color:var(--neon);"></i>Resumen del período</span>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-md-4">
            <div style="background: rgba(40,199,111,0.08); border: 1px solid rgba(40,199,111,0.20); border-radius:12px; padding:16px; text-align:center;">
                <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--text-sec); margin-bottom:6px;">Total Ventas</div>
                <div style="font-size:24px; font-weight:700; color:var(--neon);">
                    S/ {{ number_format($reporte['total_ventas'] ?? 0, 2) }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background: rgba(231,76,60,0.08); border: 1px solid rgba(231,76,60,0.20); border-radius:12px; padding:16px; text-align:center;">
                <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--text-sec); margin-bottom:6px;">Total Egresos</div>
                <div style="font-size:24px; font-weight:700; color:#e74c3c;">
                    - S/ {{ number_format($reporte['total_egresos'] ?? 0, 2) }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background: rgba(52,152,219,0.08); border: 1px solid rgba(52,152,219,0.20); border-radius:12px; padding:16px; text-align:center;">
                <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--text-sec); margin-bottom:6px;">Neto Final</div>
                <div style="font-size:24px; font-weight:700; color:#3498db;">
                    S/ {{ number_format(($reporte['total_ventas'] ?? 0) - ($reporte['total_egresos'] ?? 0), 2) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
function setHoy(){
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_desde').value = hoy;
    document.getElementById('fecha_hasta').value = hoy;
}
function setMes(){
    const hoy = new Date();
    document.getElementById('fecha_desde').value = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
    document.getElementById('fecha_hasta').value = hoy.toISOString().split('T')[0];
}
function setSemana(){
    const hoy = new Date();
    const inicio = new Date(hoy); inicio.setDate(hoy.getDate() - hoy.getDay());
    document.getElementById('fecha_desde').value = inicio.toISOString().split('T')[0];
    document.getElementById('fecha_hasta').value = hoy.toISOString().split('T')[0];
}
</script>
@endsection
