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
            @if($egresosHoy > 0)
                <div style="margin-top:8px; padding-top:8px; border-top:1px dashed #d1fae5; font-size:12px;">
                    <div class="d-flex justify-content-between text-danger">
                        <span>Egresos:</span>
                        <span style="font-weight:600;">- S/ {{ number_format($egresosHoy, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="color:#16a34a; font-weight:700;">
                        <span>Neto:</span>
                        <span>S/ {{ number_format($efectivoNetoHoy, 2) }}</span>
                    </div>
                </div>
            @endif
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

@if($premiosPendientes > 0)
<div class="alert mb-4 d-flex align-items-center gap-3" style="border-radius:14px; border:2px solid #bbf7d0; background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
    <div style="font-size:28px;">🎁</div>
    <div class="flex-grow-1">
        <strong style="color:#166534;">¡{{ $premiosPendientes }} premio(s) pendiente(s) de entrega!</strong>
        <div style="font-size:13px; color:#15803d;">Clientes que alcanzaron S/500 en productos y esperan su botella 2L de Nopal</div>
    </div>
    <a href="{{ route('fidelizacion.index') }}" class="btn btn-success btn-sm px-3" style="border-radius:10px; font-weight:600;">
        <i class="bi bi-star-fill me-1"></i>Ver Fidelización
    </a>
</div>
@endif

@if(auth()->user()->isAdmin())
{{-- ── WIDGET IA RECOMENDADOR (solo admin) ── --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="nc-card" style="border:1px solid rgba(129,140,248,0.30); background:rgba(129,140,248,0.06);">
            <div class="nc-card-header">
                <span>
                    <i class="bi bi-cpu me-2" style="color:#818cf8;"></i>
                    <span style="color:#818cf8; font-weight:700;">Módulo IA</span>
                    — Recomendador inteligente de productos
                    <span class="ms-2" style="font-size:11px; color:rgba(255,255,255,0.35);">últimas 24h</span>
                </span>
                <a href="{{ route('metricas.recomendaciones') }}"
                   class="btn btn-sm"
                   style="border:1px solid rgba(129,140,248,0.40); color:#818cf8; background:rgba(129,140,248,0.10); border-radius:8px; font-size:12px; font-weight:600;">
                    Ver métricas completas →
                </a>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div style="background:rgba(129,140,248,0.10); border:1px solid rgba(129,140,248,0.20); border-radius:12px; padding:14px; text-align:center;">
                        <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Recomendaciones</div>
                        <div style="font-size:28px; font-weight:700; color:#818cf8;">{{ number_format($metricasIA['total_mostrada']) }}</div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.35);">listas mostradas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div style="background:rgba(56,189,248,0.10); border:1px solid rgba(56,189,248,0.20); border-radius:12px; padding:14px; text-align:center;">
                        <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Convertidas</div>
                        <div style="font-size:28px; font-weight:700; color:#38bdf8;">{{ number_format($metricasIA['total_comprada']) }}</div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.35);">compras atribuidas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div style="background:rgba(134,239,172,0.10); border:1px solid rgba(134,239,172,0.20); border-radius:12px; padding:14px; text-align:center;">
                        <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Tasa conversión</div>
                        <div style="font-size:28px; font-weight:700; color:#86efac;">
                            @if($metricasIA['conversion_compra_sobre_mostrada'] !== null)
                                {{ number_format($metricasIA['conversion_compra_sobre_mostrada'] * 100, 1) }}%
                            @else
                                —
                            @endif
                        </div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.35);">compra / mostrada</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div style="background:rgba(252,211,77,0.10); border:1px solid rgba(252,211,77,0.20); border-radius:12px; padding:14px; text-align:center;">
                        <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Precision@{{ $metricasIA['precision_k'] }}</div>
                        <div style="font-size:28px; font-weight:700; color:#fcd34d;">
                            @if($metricasIA['precision_at_k'] !== null)
                                {{ number_format($metricasIA['precision_at_k'] * 100, 1) }}%
                            @else
                                —
                            @endif
                        </div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.35);">acierto en top-k</div>
                    </div>
                </div>
            </div>

            @if($metricasIA['total_mostrada'] > 0)
            <div style="background:rgba(0,0,0,0.20); border-radius:10px; padding:12px 16px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px; color:rgba(255,255,255,0.50);">Efectividad del motor IA hoy</span>
                    <span style="font-size:12px; color:#818cf8; font-weight:600;">
                        {{ $metricasIA['total_comprada'] }} de {{ $metricasIA['total_mostrada'] }} recomendaciones resultaron en venta
                    </span>
                </div>
                @php
                    $pct = $metricasIA['total_mostrada'] > 0
                        ? min(100, round($metricasIA['total_comprada'] / $metricasIA['total_mostrada'] * 100))
                        : 0;
                @endphp
                <div style="background:rgba(255,255,255,0.07); border-radius:20px; height:8px;">
                    <div style="width:{{ $pct }}%; background:linear-gradient(90deg,#818cf8,#38bdf8); border-radius:20px; height:8px;"></div>
                </div>
            </div>
            @else
            <div style="text-align:center; padding:8px; color:rgba(255,255,255,0.30); font-size:13px;">
                <i class="bi bi-info-circle me-1"></i>
                Sin actividad del recomendador en las últimas 24h. Los datos aparecen cuando se atiende un cliente en el POS.
            </div>
            @endif

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
@endif

<div class="row g-4">
    @if(auth()->user()->isAdmin())
    <!-- Productos más vendidos (solo admin) -->
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
    @endif

    <!-- Stock bajo + Caja (todos) -->
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

        @if(auth()->user()->isAdmin())
        {{-- ── BLOQUE 5 · WIDGET PRONÓSTICO DE DEMANDA (SES) (solo admin) ── --}}
        <div class="nc-card mb-4" @if(!empty($forecastRiesgo)) style="border: 2px dashed #fcd34d;" @endif>
            <div class="nc-card-header">
                <span>
                    <i class="bi bi-graph-up-arrow me-2 text-warning"></i>Pronóstico de demanda · próxima semana
                </span>
                <span class="badge bg-warning-subtle text-warning rounded-pill" style="font-size:11px;">SES</span>
            </div>
            @if(empty($forecastRiesgo))
                <div class="text-center text-muted py-4">
                    <i class="bi bi-graph-up" style="font-size:36px; opacity:0.3;"></i>
                    <p class="mt-2 small mb-1">Sin productos en riesgo de quiebre.</p>
                    <p class="small text-muted mb-0" style="font-size:11px;">
                        El modelo se entrena cada lunes 03:00. Ejecuta
                        <code>php artisan tinker</code> y dispara
                        <code>(new App\Jobs\Recommendation\ActualizarDemandaJob)->handle(app(App\Services\Forecasting\DemandaForecastService::class))</code>
                        para forzar.
                    </p>
                </div>
            @else
                <p class="text-muted small mb-2" style="font-size:11px;">
                    Productos cuya predicción para la próxima semana ISO
                    <strong>excede el stock actual</strong>. Ordenados por déficit
                    (predicción − stock). El modelo es Suavizado Exponencial
                    Simple; no captura estacionalidad.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead style="font-size:11px; color:#6b7280; text-transform:uppercase;">
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">Predicción</th>
                                <th class="text-end">Déficit</th>
                                <th class="text-end">MAPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forecastRiesgo as $row)
                                <tr>
                                    <td style="font-size:13px; font-weight:500;">{{ $row['nombre'] }}</td>
                                    <td class="text-end" style="font-size:13px;">{{ $row['stock'] }}</td>
                                    <td class="text-end" style="font-size:13px;">
                                        {{ number_format($row['prediccion'], 1) }}
                                        @if($row['intervalo_inf'] !== null && $row['intervalo_sup'] !== null)
                                            <span class="text-muted" style="font-size:10px;">
                                                [{{ number_format($row['intervalo_inf'], 0) }}–{{ number_format($row['intervalo_sup'], 0) }}]
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end" style="font-size:13px; font-weight:600; color:#dc2626;">
                                        +{{ number_format($row['deficit'], 1) }}
                                    </td>
                                    <td class="text-end" style="font-size:11px; color:#6b7280;">
                                        @if($row['mape'] !== null)
                                            {{ number_format($row['mape'] * 100, 1) }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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
@if(auth()->user()->isAdmin())
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
@endif
@endsection
