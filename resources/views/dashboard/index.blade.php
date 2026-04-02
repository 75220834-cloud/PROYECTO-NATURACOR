@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('styles')
<style>
.metric-change { font-size: 12px; }
.metric-change.up { color: #16a34a; }
.metric-change.down { color: #dc2626; }
</style>
@endsection

@section('content')
<div class="row g-4 mb-4">
    <!-- KPI Cards -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon kpi-icon-green"><i class="bi bi-currency-dollar"></i></div>
                <span class="badge bg-success-subtle text-success rounded-pill" style="font-size:11px;">Hoy</span>
            </div>
            <div class="kpi-value">S/ {{ number_format($totalHoy, 2) }}</div>
            <div class="kpi-label">Ventas del día</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon kpi-icon-blue"><i class="bi bi-receipt"></i></div>
                <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size:11px;">Hoy</span>
            </div>
            <div class="kpi-value">{{ $countHoy }}</div>
            <div class="kpi-label">Transacciones hoy</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon kpi-icon-amber"><i class="bi bi-calendar-month"></i></div>
                <span class="badge bg-warning-subtle text-warning rounded-pill" style="font-size:11px;">Mes</span>
            </div>
            <div class="kpi-value">S/ {{ number_format($totalMes, 2) }}</div>
            <div class="kpi-label">Ventas del mes</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon kpi-icon-rose"><i class="bi bi-exclamation-triangle"></i></div>
                <span class="badge bg-danger-subtle text-danger rounded-pill" style="font-size:11px;">Atención</span>
            </div>
            <div class="kpi-value">{{ $stockBajo->count() }}</div>
            <div class="kpi-label">Productos stock bajo</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Gráfico de ventas semana -->
    <div class="col-12 col-lg-8">
        <div class="nc-card">
            <div class="nc-card-header">
                <span><i class="bi bi-bar-chart me-2 text-success"></i>Ventas últimos 7 días</span>
            </div>
            <canvas id="ventasChart" height="100"></canvas>
        </div>
    </div>

    <!-- Ingresos por método de pago -->
    <div class="col-12 col-lg-4">
        <div class="nc-card h-100">
            <div class="nc-card-header">
                <span><i class="bi bi-pie-chart me-2 text-success"></i>Métodos de pago hoy</span>
            </div>
            @if($ingresosPorMetodo->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="bi bi-cash-stack" style="font-size:36px; opacity:0.3;"></i>
                    <p class="mt-2 small">Sin ventas hoy</p>
                </div>
            @else
                <canvas id="pagosChart" height="180"></canvas>
                <div class="mt-3">
                @foreach($ingresosPorMetodo as $metodo => $monto)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="d-flex align-items-center gap-2" style="font-size:13px; font-weight:500; text-transform:capitalize;">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ ['efectivo'=>'#4ade80','yape'=>'#818cf8','plin'=>'#f472b6','otro'=>'#fb923c'][$metodo] ?? '#94a3b8' }};"></span>
                            {{ ucfirst($metodo) }}
                        </span>
                        <span style="font-size:13px; font-weight:600;">S/ {{ number_format($monto, 2) }}</span>
                    </div>
                @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Productos más vendidos -->
    <div class="col-12 col-lg-6">
        <div class="nc-card">
            <div class="nc-card-header">
                <span><i class="bi bi-trophy me-2 text-warning"></i>Top productos (30 días)</span>
                <a href="{{ route('reportes.index') }}" class="btn btn-sm btn-naturacor-outline">Ver más</a>
            </div>
            @forelse($masVendidos as $item)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: #e8f5e8 !important;">
                <span style="font-size: 14px; font-weight: 500;">{{ $item->nombre_producto }}</span>
                <span class="badge-natural">{{ $item->total_vendido }} unid.</span>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-box-seam" style="font-size:36px; opacity:0.3;"></i>
                <p class="mt-2 small">Sin datos de ventas aún</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Stock bajo + Caja -->
    <div class="col-12 col-lg-6">
        @if($stockBajo->count() > 0)
        <div class="nc-card mb-4">
            <div class="nc-card-header">
                <span><i class="bi bi-exclamation-diamond me-2 text-warning"></i>Alerta de stock bajo</span>
                <a href="{{ route('productos.index', ['stock_bajo' => 1]) }}" class="btn btn-sm btn-naturacor-outline">Ver todos</a>
            </div>
            @foreach($stockBajo->take(4) as $p)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: #e8f5e8 !important;">
                <span style="font-size: 14px; font-weight: 500;">{{ $p->nombre }}</span>
                <span class="px-2 py-1 rounded-pill {{ $p->stock == 0 ? 'badge-stock-zero' : 'badge-stock-low' }}" style="font-size:12px; font-weight:600;">
                    {{ $p->stock }} / {{ $p->stock_minimo }}
                </span>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Caja activa -->
        @if($cajaActiva)
        <div class="nc-card" style="border: 2px solid var(--nc-green-200);">
            <div class="nc-card-header">
                <span><i class="bi bi-cash-stack me-2 text-success"></i>Caja abierta</span>
                <a href="{{ route('caja.index') }}" class="btn btn-sm btn-naturacor">Ir a caja</a>
            </div>
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div style="background: var(--nc-green-50); border-radius: 10px; padding: 12px;">
                        <div style="font-size: 18px; font-weight: 700; color: var(--nc-green-700);">S/ {{ number_format($cajaActiva->total_efectivo, 2) }}</div>
                        <div style="font-size: 11px; color: #6b7280;">Efectivo</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background: #ede9fe; border-radius: 10px; padding: 12px;">
                        <div style="font-size: 18px; font-weight: 700; color: #7c3aed;">S/ {{ number_format($cajaActiva->total_yape, 2) }}</div>
                        <div style="font-size: 11px; color: #6b7280;">Yape</div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="nc-card text-center py-4" style="border: 2px dashed #d1fae5;">
            <i class="bi bi-cash-stack" style="font-size:36px; color: var(--nc-green-300);"></i>
            <p class="mt-2 mb-3" style="color: #6b7280; font-size: 14px;">No hay caja abierta</p>
            <a href="{{ route('caja.index') }}" class="btn btn-naturacor">Abrir caja</a>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
const ventasData = @json($ventasSemana);
const labels = ventasData.map(v => {
    const d = new Date(v.fecha + 'T00:00:00');
    return d.toLocaleDateString('es-PE', {weekday:'short', day:'numeric'});
});
const totales = ventasData.map(v => parseFloat(v.total));

new Chart(document.getElementById('ventasChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Ventas (S/)',
            data: totales,
            backgroundColor: 'rgba(74, 222, 128, 0.6)',
            borderColor: '#16a34a',
            borderWidth: 2,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0fdf4' }, ticks: { callback: v => 'S/'+v } },
            x: { grid: { display: false } }
        }
    }
});

@if($ingresosPorMetodo->isNotEmpty())
const pagosData = @json($ingresosPorMetodo);
const colores = { efectivo: '#4ade80', yape: '#818cf8', plin: '#f472b6', tarjeta: '#38bdf8', otro: '#fb923c' };
new Chart(document.getElementById('pagosChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(pagosData).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
        datasets: [{
            data: Object.values(pagosData),
            backgroundColor: Object.keys(pagosData).map(k => colores[k] || '#94a3b8'),
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' S/' + parseFloat(ctx.raw).toFixed(2) } } }
    }
});
@endif
</script>
@endsection
