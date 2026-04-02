@extends('layouts.app')
@section('title', 'Reportes de Ventas')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📊 Reportes de Ventas</h4>
        <small class="text-muted">Genera reportes con filtros avanzados</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 px-4 pt-4 pb-2">
        <h6 class="fw-bold mb-0">🔍 Filtros de búsqueda</h6>
    </div>
    <div class="card-body px-4 pb-4">
        <form action="{{ route('reportes.generar') }}" method="POST" id="reporteForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Fecha desde</label>
                    <input type="date" name="fecha_desde" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="form-control rounded-3" id="fecha_desde">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Fecha hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ now()->format('Y-m-d') }}" class="form-control rounded-3" id="fecha_hasta">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Método de pago</label>
                    <select name="metodo_pago" class="form-select rounded-3">
                        <option value="">Todos</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="tarjeta">Tarjeta</option>
                    </select>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Sucursal</label>
                    <select name="sucursal_id" class="form-select rounded-3">
                        <option value="">Todas</option>
                        @foreach($sucursales as $suc)
                        <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Empleado</label>
                    <select name="user_id" class="form-select rounded-3">
                        <option value="">Todos</option>
                        @foreach($empleados as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#6b7280;">Producto</label>
                    <select name="producto_id" class="form-select rounded-3">
                        <option value="">Todos</option>
                        @foreach($productos as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-search me-1"></i> Generar Reporte
                </button>
                <button type="submit" name="exportar" value="pdf" class="btn btn-outline-danger px-4">
                    <i class="bi bi-file-pdf me-1"></i> Exportar PDF
                </button>
                <button type="button" onclick="setHoy()" class="btn btn-light px-3">Hoy</button>
                <button type="button" onclick="setMes()" class="btn btn-light px-3">Este mes</button>
                <button type="button" onclick="setSemana()" class="btn btn-light px-3">Esta semana</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <i class="bi bi-receipt text-success" style="font-size:22px;"></i>
            <div class="fw-bold mt-1" style="font-size:22px;color:#1a2e1a;">—</div>
            <small class="text-muted">Total ventas</small>
        </div>
    </div>
    <div class="col-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <i class="bi bi-cash-coin text-success" style="font-size:22px;"></i>
            <div class="fw-bold mt-1" style="font-size:22px;color:#1a2e1a;">S/ —</div>
            <small class="text-muted">Ingresos totales</small>
        </div>
    </div>
    <div class="col-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <i class="bi bi-phone text-success" style="font-size:22px;"></i>
            <div class="fw-bold mt-1" style="font-size:22px;color:#1a2e1a;">S/ —</div>
            <small class="text-muted">Yape / Plin</small>
        </div>
    </div>
    <div class="col-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <i class="bi bi-wallet2 text-success" style="font-size:22px;"></i>
            <div class="fw-bold mt-1" style="font-size:22px;color:#1a2e1a;">S/ —</div>
            <small class="text-muted">Efectivo</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-bar-chart-line" style="font-size:48px;opacity:0.3;"></i>
        <div class="mt-3">Selecciona los filtros y genera el reporte para ver los resultados aquí.</div>
    </div>
</div>

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
