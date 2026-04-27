@extends('layouts.app')

@section('title', 'Métricas recomendaciones')
@section('page-title', '📈 Métricas del recomendador')

@section('content')
<div class="py-3">

    {{-- Acceso rápido al panel hermano (Bloque 6) --}}
    <div class="d-flex justify-content-end mb-2">
        <a href="{{ route('metricas.heatmap_enfermedades') }}" class="btn btn-sm btn-naturacor-outline">
            <i class="bi bi-grid-3x3-gap"></i> Mapa de calor de enfermedades
        </a>
    </div>

    {{-- ── FILTROS ── --}}
    <div class="nc-card p-4 mb-4">
        <p class="text-muted small mb-3">
            Indicadores para evaluar el impacto del módulo de recomendación
            (eventos registrados en <code>recomendacion_eventos</code>).
            Las conversiones usan una ventana de lookback configurable hacia atrás desde cada venta.
        </p>
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">Días</label>
                <input type="number" name="dias" value="{{ $dias }}" min="1" max="365"
                       class="form-control form-control-sm" style="width:90px;">
            </div>
            @if(auth()->user()->isAdmin())
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">Sucursal (opcional)</label>
                <input type="number" name="sucursal_id" value="{{ request('sucursal_id') }}"
                       class="form-control form-control-sm" placeholder="ID" style="width:100px;">
            </div>
            @endif
            <div class="col-auto">
                <button type="submit" class="btn btn-naturacor btn-sm">Aplicar</button>
            </div>
        </form>
    </div>

    {{-- ── KPIs ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Mostradas</div>
                <div class="fs-3 fw-bold text-white">{{ number_format($resumen['total_mostrada']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Agregadas (POS)</div>
                <div class="fs-3 fw-bold" style="color:#86efac;">{{ number_format($resumen['total_agregada']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Clics</div>
                <div class="fs-3 fw-bold" style="color:#fcd34d;">{{ number_format($resumen['total_clic']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Compradas (atribuidas)</div>
                <div class="fs-3 fw-bold" style="color:#38bdf8;">{{ number_format($resumen['total_comprada']) }}</div>
            </div>
        </div>
    </div>

    {{-- ── CONVERSIONES + TICKET ── --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="nc-card p-4 h-100">
                <h6 class="text-white mb-3">Conversiones y precisión</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-2">
                        <strong class="text-white">Sesiones distintas (listas mostradas):</strong>
                        {{ number_format($resumen['sesiones_reco_distintas']) }}
                    </li>
                    <li class="mb-2">
                        <strong class="text-white">Compra / agregada:</strong>
                        @if($resumen['conversion_compra_sobre_agregada'] !== null)
                            {{ number_format($resumen['conversion_compra_sobre_agregada'] * 100, 2) }} %
                        @else
                            <span class="text-muted">— (sin agregadas en el periodo)</span>
                        @endif
                    </li>
                    <li class="mb-2">
                        <strong class="text-white">Compra / mostrada (aprox.):</strong>
                        @if($resumen['conversion_compra_sobre_mostrada'] !== null)
                            {{ number_format($resumen['conversion_compra_sobre_mostrada'] * 100, 2) }} %
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </li>
                    <li>
                        <strong class="text-white">Precision@{{ $resumen['precision_k'] }} (sesiones con hit en top-k):</strong>
                        @if($resumen['precision_at_k'] !== null)
                            {{ number_format($resumen['precision_at_k'] * 100, 2) }} %
                        @else
                            <span class="text-muted">— (sin sesiones con listas)</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="nc-card p-4 h-100">
                <h6 class="text-white mb-3">Ticket promedio (ventas con cliente)</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-2">
                        <strong class="text-white">Con atribución "comprada" por reco:</strong>
                        @if($resumen['ticket_promedio_con_reco'] !== null)
                            S/ {{ number_format($resumen['ticket_promedio_con_reco'], 2) }}
                            <span class="text-muted">({{ $resumen['ventas_con_reco_count'] }} ventas)</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </li>
                    <li>
                        <strong class="text-white">Sin esa atribución:</strong>
                        @if($resumen['ticket_promedio_sin_reco'] !== null)
                            S/ {{ number_format($resumen['ticket_promedio_sin_reco'], 2) }}
                            <span class="text-muted">({{ $resumen['ventas_sin_reco_count'] }} ventas)</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ── BLOQUE 4 · EXPERIMENTO A/B ── --}}
    <div class="nc-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="text-white mb-0">🧪 Experimento A/B del recomendador</h6>
            @if($abComparativa['activo'])
                <span class="badge bg-success">Activo · estrategia: <code>{{ $abComparativa['estrategia'] }}</code></span>
            @else
                <span class="badge bg-secondary">Desactivado (REC_AB_ENABLED=false)</span>
            @endif
        </div>
        <p class="text-muted small mb-3">
            Comparativa entre <strong class="text-white">grupo control</strong>
            (no recibió recomendaciones) y <strong class="text-white">grupo tratamiento</strong>
            (recibió recomendaciones del motor híbrido). Métricas: <em>ticket promedio por venta</em>
            con Welch's t-test (varianzas distintas) y tasa de conversión por sesión.
            @if(!$abComparativa['activo'])
                <br><span class="text-warning">Para producir datos comparables activa A/B en
                <code>config/recommendaciones.php</code> o <code>REC_AB_ENABLED=true</code>.</span>
            @endif
        </p>

        {{-- Tabla comparativa de ticket --}}
        <div class="table-responsive mb-3">
            <table class="table table-sm table-borderless align-middle mb-0" style="color:#dbe6df;">
                <thead style="color:#9ca3af; font-size:.78rem; text-transform:uppercase;">
                    <tr>
                        <th>Grupo</th>
                        <th class="text-end">N ventas</th>
                        <th class="text-end">Ticket promedio</th>
                        <th class="text-end">Desv. std.</th>
                        <th class="text-end">Conversión por sesión</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-secondary">control</span></td>
                        <td class="text-end">{{ number_format($abComparativa['ticket']['control']['n']) }}</td>
                        <td class="text-end">
                            @if($abComparativa['ticket']['control']['media'] !== null)
                                S/ {{ number_format($abComparativa['ticket']['control']['media'], 2) }}
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($abComparativa['ticket']['control']['sd'] !== null)
                                {{ number_format($abComparativa['ticket']['control']['sd'], 2) }}
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($abComparativa['conversion']['control']['tasa'] !== null)
                                {{ number_format($abComparativa['conversion']['control']['tasa'] * 100, 2) }} %
                                <span class="text-muted small">({{ $abComparativa['conversion']['control']['n_compras'] }}/{{ $abComparativa['conversion']['control']['n_sesiones'] }})</span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">tratamiento</span></td>
                        <td class="text-end">{{ number_format($abComparativa['ticket']['tratamiento']['n']) }}</td>
                        <td class="text-end">
                            @if($abComparativa['ticket']['tratamiento']['media'] !== null)
                                S/ {{ number_format($abComparativa['ticket']['tratamiento']['media'], 2) }}
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($abComparativa['ticket']['tratamiento']['sd'] !== null)
                                {{ number_format($abComparativa['ticket']['tratamiento']['sd'], 2) }}
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($abComparativa['conversion']['tratamiento']['tasa'] !== null)
                                {{ number_format($abComparativa['conversion']['tratamiento']['tasa'] * 100, 2) }} %
                                <span class="text-muted small">({{ $abComparativa['conversion']['tratamiento']['n_compras'] }}/{{ $abComparativa['conversion']['tratamiento']['n_sesiones'] }})</span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Estadística inferencial --}}
        @php $test = $abComparativa['ticket']['test']; @endphp
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 rounded" style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);">
                    <div class="text-muted small mb-1">Welch's t-test (ticket)</div>
                    <div class="text-white">
                        <code>t</code> = {{ $test['t_statistic'] !== null ? number_format($test['t_statistic'], 4) : '—' }}
                        &nbsp;·&nbsp;
                        <code>df</code> = {{ $test['df'] !== null ? number_format($test['df'], 2) : '—' }}
                        &nbsp;·&nbsp;
                        <code>p</code> ≈
                        @if($test['p_value_aprox'] !== null)
                            @if($test['p_value_aprox'] < 0.0001)
                                <span class="text-success fw-bold">&lt; 0.0001</span>
                            @else
                                {{ number_format($test['p_value_aprox'], 4) }}
                            @endif
                        @else — @endif
                    </div>
                    <div class="small mt-1">
                        @if($test['significativo_5pct'] === true)
                            <span class="text-success">Diferencia estadísticamente significativa al 5%.</span>
                        @elseif($test['significativo_5pct'] === false)
                            <span class="text-warning">Sin diferencia significativa al 5% en la muestra actual.</span>
                        @else
                            <span class="text-muted">Sin t-test (datos insuficientes en algún grupo).</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded" style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);">
                    <div class="text-muted small mb-1">Tamaño de efecto (Cohen's d)</div>
                    <div class="text-white">
                        <code>d</code> = {{ $test['cohens_d'] !== null ? number_format($test['cohens_d'], 4) : '—' }}
                        @if($test['cohens_d'] !== null)
                            @php $d = abs($test['cohens_d']); @endphp
                            @if($d < 0.2) <span class="text-muted small">(despreciable)</span>
                            @elseif($d < 0.5) <span class="text-info small">(pequeño)</span>
                            @elseif($d < 0.8) <span class="text-warning small">(medio)</span>
                            @else <span class="text-success small">(grande)</span>
                            @endif
                        @endif
                    </div>
                    @if(!empty($test['nota']))
                        <div class="small text-warning mt-1">{{ $test['nota'] }}</div>
                    @else
                        <div class="small text-muted mt-1">
                            Diferencia de medias: S/ {{ number_format($test['diferencia_medias'], 2) }}
                            (tratamiento − control).
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── GRÁFICO TIMELINE ── --}}
    <div class="nc-card p-4 mb-4">
        <h6 class="text-white mb-1">Eventos por día</h6>
        <p class="text-muted small mb-3">Evolución diaria de mostradas, clics, agregadas y compradas atribuidas.</p>
        <div style="position:relative; width:100%; height:260px;">
            <canvas id="chartTimeline" style="display:block; width:100% !important; height:260px !important;"></canvas>
        </div>
    </div>

    {{-- ── DONUT + TOP PRODUCTOS ── --}}
    <div class="row g-3 mb-2">
        <div class="col-md-4">
            <div class="nc-card p-4 h-100">
                <h6 class="text-white mb-1">Conversiones globales</h6>
                <p class="text-muted small mb-3">Distribución de eventos en el periodo.</p>
                <div style="position:relative; width:100%; height:240px;">
                    <canvas id="chartDonut" style="display:block; width:100% !important; height:240px !important;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="nc-card p-4 h-100">
                <h6 class="text-white mb-1">Top productos en eventos de recomendación</h6>
                <p class="text-muted small mb-3">Frecuencia de aparición en eventos mostrada / agregada / comprada.</p>
                @if($barras->isEmpty())
                    <p class="text-muted small">Aún no hay eventos en este periodo.</p>
                @else
                    <div style="position:relative; width:100%; height:240px;">
                        <canvas id="chartTopProductos" style="display:block; width:100% !important; height:240px !important;"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
// Esperamos que el DOM esté completamente listo
window.addEventListener('DOMContentLoaded', function () {

    // ── Paleta Naturacor ──────────────────────────────────────────
    var C = {
        mostrada : 'rgba(134,239,172,0.90)',
        clic     : 'rgba(252,211,77,0.90)',
        agregada : 'rgba(129,140,248,0.90)',
        comprada : 'rgba(56,189,248,0.90)',
        grid     : 'rgba(255,255,255,0.07)',
        tick     : 'rgba(255,255,255,0.50)',
    };

    // ── Datos desde PHP ───────────────────────────────────────────
    var timeline     = @json($chartData['timeline']);
    var conversiones = @json($chartData['conversiones']);
    var interacciones= @json($chartData['interacciones']);
    var topProductos = @json($chartData['topProductos']);

    // ── Opciones de ejes reutilizables ───────────────────────────
    var baseScales = {
        x: {
            grid : { color: C.grid },
            ticks: { color: C.tick, font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            grid : { color: C.grid },
            ticks: { color: C.tick, font: { size: 11 } },
        },
    };

    // ════════════════════════════════════════════════════════════
    // 1. TIMELINE — línea de eventos por día
    // ════════════════════════════════════════════════════════════
    var elLine = document.getElementById('chartTimeline');
    if (elLine) {
        new Chart(elLine, {
            type: 'line',
            data: {
                labels: timeline.labels,
                datasets: [
                    {
                        label: 'Mostradas',
                        data: timeline.mostrada,
                        borderColor: C.mostrada,
                        backgroundColor: 'rgba(134,239,172,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                    },
                    {
                        label: 'Clics',
                        data: timeline.clic,
                        borderColor: C.clic,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                    },
                    {
                        label: 'Agregadas',
                        data: timeline.agregada,
                        borderColor: C.agregada,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                    },
                    {
                        label: 'Compradas',
                        data: timeline.comprada,
                        borderColor: C.comprada,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { color: C.tick, boxWidth: 12, font: { size: 11 } }
                    },
                    tooltip: { mode: 'index' },
                },
                scales: baseScales,
            },
        });
    }

    // ════════════════════════════════════════════════════════════
    // 2. DONUT — conversiones globales
    // ════════════════════════════════════════════════════════════
    var elDonut = document.getElementById('chartDonut');
    if (elDonut) {
        new Chart(elDonut, {
            type: 'doughnut',
            data: {
                labels: ['Mostradas', 'Compradas', 'Clics', 'Agregadas'],
                datasets: [{
                    data: [
                        conversiones.values[0],
                        conversiones.values[1],
                        interacciones.values[0],
                        interacciones.values[1],
                    ],
                    backgroundColor: [C.mostrada, C.comprada, C.clic, C.agregada],
                    borderWidth: 2,
                    borderColor: '#071a10',
                    hoverOffset: 10,
                }],
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: C.tick,
                            boxWidth: 12,
                            padding: 14,
                            font: { size: 11 },
                        },
                    },
                },
            },
        });
    }

    // ════════════════════════════════════════════════════════════
    // 3. BARRAS HORIZONTALES — top productos
    // ════════════════════════════════════════════════════════════
    var elTop = document.getElementById('chartTopProductos');
    if (elTop && topProductos.labels.length > 0) {
        var bgColors = topProductos.values.map(function(_, i) {
            return 'hsla(' + (145 + i * 18) + ', 60%, 52%, 0.82)';
        });

        new Chart(elTop, {
            type: 'bar',
            data: {
                labels: topProductos.labels,
                datasets: [{
                    label: 'Apariciones',
                    data: topProductos.values,
                    backgroundColor: bgColors,
                    borderRadius: 5,
                    borderSkipped: false,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid : { color: C.grid },
                        ticks: { color: C.tick, font: { size: 11 } },
                    },
                    y: {
                        grid : { display: false },
                        ticks: { color: C.tick, font: { size: 11 } },
                    },
                },
            },
        });
    }

});
</script>
@endsection
