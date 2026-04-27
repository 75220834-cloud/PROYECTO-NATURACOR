@extends('layouts.app')

@section('title', 'Mapa de calor de enfermedades')
@section('page-title', '🗺️ Mapa de calor de enfermedades')

@section('styles')
<style>
    .nc-heatmap-table { border-collapse: separate; border-spacing: 1px; }
    .nc-heatmap-table th,
    .nc-heatmap-table td { padding: 6px 10px; font-size: 12px; }
    .nc-heatmap-table th.col-suc { writing-mode: vertical-rl; transform: rotate(180deg); height: 110px; vertical-align: bottom; white-space: nowrap; }
    .nc-heatmap-table th.fila-enf { text-align: left; min-width: 200px; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #e5e7eb; }
    .nc-heatmap-table td.celda { text-align: center; min-width: 48px; color: #0f172a; font-weight: 600; }
    .nc-heatmap-table td.celda.cero { color: #6b7280; font-weight: 400; }
    .nc-heatmap-table tr.total-row td,
    .nc-heatmap-table tr.total-row th { font-weight: 700; color: #f8fafc; background: #1e293b; }
    .nc-heatmap-legend { display: inline-flex; align-items: center; gap: 6px; }
    .nc-heatmap-legend .swatch { width: 20px; height: 12px; border-radius: 2px; }
    .nc-pill-cat { display: inline-block; font-size: 10px; padding: 1px 6px; border-radius: 6px; background: #334155; color: #cbd5e1; margin-left: 6px; }
</style>
@endsection

@section('content')
<div class="py-3">

    {{-- ── CABECERA + LINK ── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted small mb-0">
                Matriz <strong>Enfermedades × Sucursales</strong> con conteo de
                <strong>clientes únicos</strong>. Cada celda cuenta una vez por cliente
                aunque tenga el padecimiento declarado y observado simultáneamente.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('metricas.recomendaciones') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Métricas del recomendador
            </a>
            <a href="{{ route('metricas.heatmap_enfermedades.csv', request()->query()) }}"
               class="btn btn-sm btn-naturacor-outline">
                <i class="bi bi-download"></i> Exportar CSV
            </a>
        </div>
    </div>

    {{-- ── FILTROS ── --}}
    <div class="nc-card p-4 mb-4">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">Fuente de evidencia</label>
                <select name="fuente" class="form-select form-select-sm" style="min-width:160px;">
                    <option value="combinada" @selected($filtros['fuente'] === 'combinada')>Combinada (declarada ∪ observada)</option>
                    <option value="declarada" @selected($filtros['fuente'] === 'declarada')>Solo declarada (padecimientos)</option>
                    <option value="observada" @selected($filtros['fuente'] === 'observada')>Solo observada (afinidad ≥ umbral)</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">Orden de filas</label>
                <select name="orden" class="form-select form-select-sm" style="min-width:140px;">
                    <option value="total" @selected($filtros['orden'] === 'total')>Total descendente</option>
                    <option value="alfabetico" @selected($filtros['orden'] === 'alfabetico')>Alfabético</option>
                    <option value="cluster" @selected($filtros['orden'] === 'cluster')>Cluster (similitud)</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">Días</label>
                <input type="number" name="dias" value="{{ $filtros['dias'] }}" min="1" max="3650"
                       class="form-control form-control-sm" style="width:100px;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-naturacor btn-sm">Aplicar</button>
            </div>
        </form>
    </div>

    {{-- ── KPIs RESUMEN ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Clientes únicos</div>
                <div class="fs-3 fw-bold text-white">{{ number_format($matriz['meta']['total_clientes_unicos']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Enfermedades activas</div>
                <div class="fs-3 fw-bold" style="color:#86efac;">{{ count($matriz['enfermedades']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Sucursales activas</div>
                <div class="fs-3 fw-bold" style="color:#fcd34d;">{{ count($matriz['sucursales']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nc-card p-3 text-center">
                <div class="text-muted small mb-1">Pico por celda</div>
                <div class="fs-3 fw-bold" style="color:#38bdf8;">{{ $matriz['max_celda'] }}</div>
            </div>
        </div>
    </div>

    {{-- ── HEATMAP ── --}}
    @if(empty($matriz['enfermedades']) || empty($matriz['sucursales']))
        <div class="nc-card p-4 text-center text-muted">
            <i class="bi bi-grid-3x3-gap" style="font-size: 36px; opacity: 0.4;"></i>
            <p class="mb-0 mt-2">Sin enfermedades o sucursales activas para construir la matriz.</p>
        </div>
    @else
        <div class="nc-card p-3 mb-4 overflow-auto">
            <table class="nc-heatmap-table">
                <thead>
                    <tr>
                        <th class="fila-enf" style="background:#0f172a;">Enfermedad</th>
                        @foreach($matriz['sucursales'] as $s)
                            <th class="col-suc" style="background:#0f172a; color:#cbd5e1;">{{ $s['nombre'] }}</th>
                        @endforeach
                        <th class="col-suc" style="background:#0f172a; color:#94a3b8;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $idIndex = collect($matriz['enfermedades'])->keyBy('id'); @endphp
                    @foreach($matriz['orden_filas'] as $eId)
                        @php $enf = $idIndex->get($eId); @endphp
                        @if(!$enf) @continue @endif
                        <tr>
                            <th class="fila-enf">
                                {{ $enf['nombre'] }}
                                @if($enf['categoria'])
                                    <span class="nc-pill-cat">{{ $enf['categoria'] }}</span>
                                @endif
                            </th>
                            @foreach($matriz['sucursales'] as $s)
                                @php
                                    $val = (int) ($matriz['celdas'][$eId][$s['id']] ?? 0);
                                    $intensity = $matriz['max_celda'] > 0 ? $val / $matriz['max_celda'] : 0;
                                    // Degradado: verde claro (frío) → ámbar → rojo (caliente)
                                    if ($intensity == 0)        { $bg = 'transparent'; }
                                    elseif ($intensity < 0.25)  { $bg = 'rgba(190,242,100,'.(0.45 + $intensity).')'; }
                                    elseif ($intensity < 0.50)  { $bg = 'rgba(252,211,77,'.(0.55 + $intensity).')'; }
                                    elseif ($intensity < 0.75)  { $bg = 'rgba(251,146,60,'.(0.65 + $intensity).')'; }
                                    else                        { $bg = 'rgba(220,38,38,'.(0.75 + $intensity * 0.25).')'; }
                                @endphp
                                <td class="celda {{ $val === 0 ? 'cero' : '' }}"
                                    style="background:{{ $bg }};"
                                    title="{{ $enf['nombre'] }} × {{ $s['nombre'] }}: {{ $val }} clientes únicos">
                                    {{ $val > 0 ? $val : '·' }}
                                </td>
                            @endforeach
                            <td class="celda" style="background:#1e293b; color:#f8fafc;">
                                {{ (int) ($matriz['fila_total'][$eId] ?? 0) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <th class="fila-enf">Total por sucursal</th>
                        @foreach($matriz['sucursales'] as $s)
                            <td class="celda">{{ (int) ($matriz['col_total'][$s['id']] ?? 0) }}</td>
                        @endforeach
                        <td class="celda">—</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Leyenda de degradado --}}
        <div class="nc-card p-3 mb-4 d-flex align-items-center gap-4 flex-wrap">
            <span class="text-muted small">Intensidad:</span>
            <span class="nc-heatmap-legend"><span class="swatch" style="background:rgba(190,242,100,0.6);"></span> baja</span>
            <span class="nc-heatmap-legend"><span class="swatch" style="background:rgba(252,211,77,0.7);"></span> media</span>
            <span class="nc-heatmap-legend"><span class="swatch" style="background:rgba(251,146,60,0.85);"></span> alta</span>
            <span class="nc-heatmap-legend"><span class="swatch" style="background:rgba(220,38,38,0.95);"></span> pico</span>
            <span class="text-muted small ms-auto">
                Periodo: {{ $matriz['meta']['desde'] }} → {{ $matriz['meta']['hasta'] }} ·
                fuente: <strong>{{ $matriz['meta']['fuente'] }}</strong> ·
                umbral score observado: {{ number_format($matriz['meta']['umbral_score'], 2) }}
            </span>
        </div>

        {{-- ── INSIGHT DE NEGOCIO: TOP por sucursal ── --}}
        @if(!empty($matriz['top_por_sucursal']))
            <div class="row g-3 mb-4">
                @foreach($matriz['sucursales'] as $s)
                    @php $top = $matriz['top_por_sucursal'][$s['id']] ?? []; @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="nc-card p-3 h-100">
                            <h6 class="text-white mb-2">
                                <i class="bi bi-shop me-1 text-success"></i>{{ $s['nombre'] }}
                            </h6>
                            @if(empty($top))
                                <p class="text-muted small mb-0">Sin clientes con padecimientos registrados en el periodo.</p>
                            @else
                                <ol class="small text-muted mb-0 ps-3">
                                    @foreach($top as $row)
                                        <li class="mb-1">
                                            <span class="text-white">{{ $row['nombre'] }}</span>
                                            — <span class="text-warning">{{ $row['total'] }} clientes</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <div class="nc-card p-3 mt-4 small text-muted">
        <strong class="text-white">Notas metodológicas (para tesis):</strong>
        <ul class="mb-0 mt-2">
            <li>Cada celda cuenta clientes únicos, no ventas, evitando inflado por compras repetidas.</li>
            <li>La sucursal del cliente se infiere desde sus ventas (un cliente puede aparecer en más de una sucursal).</li>
            <li>El clustering aglomerativo single-linkage usa distancia coseno entre filas; complejidad O(n³).</li>
            <li>El modo "observada" requiere score ≥ {{ number_format($matriz['meta']['umbral_score'], 2) }} en <code>cliente_perfil_afinidad</code>.</li>
            <li>El modo "combinada" deduplica: un cliente con padecimiento declarado + observado cuenta una sola vez.</li>
        </ul>
    </div>

</div>
@endsection
