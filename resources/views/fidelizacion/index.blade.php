@extends('layouts.app')
@section('title', 'Fidelización')
@section('page-title', '⭐ Programa de Fidelización')

@section('styles')
<style>
.fideliz-progress {
    height: 8px; border-radius: 4px; background: #e5e7eb; overflow: hidden;
}
.fideliz-progress-bar {
    height: 100%; border-radius: 4px; transition: width 0.6s ease;
}
.fideliz-progress-bar.green { background: linear-gradient(90deg, #4ade80, #16a34a); }
.fideliz-progress-bar.amber { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.fideliz-progress-bar.full { background: linear-gradient(90deg, #22c55e, #15803d); }
.status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-progreso { background: #fef3c7; color: #92400e; }
.status-listo { background: #dcfce7; color: #166534; }
.status-entregado { background: #dbeafe; color: #1e40af; }
.tab-btn {
    padding: 8px 20px; border-radius: 10px; font-size: 13px; font-weight: 600;
    border: 2px solid transparent; cursor: pointer; transition: all 0.2s;
    background: white; color: #6b7280;
}
.tab-btn:hover { border-color: var(--nc-green-200); color: var(--nc-green-700); }
.tab-btn.active { background: var(--nc-green-50); border-color: var(--nc-green-400); color: var(--nc-green-700); }
.fideliz-alert {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    border: 2px solid var(--nc-green-300); border-radius: 14px;
    padding: 16px 20px; margin-bottom: 20px;
}
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="kpi-icon kpi-icon-blue"><i class="bi bi-people"></i></div>
            </div>
            <div class="kpi-value">{{ $totalClientes }}</div>
            <div class="kpi-label">Total Clientes</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="kpi-icon kpi-icon-amber"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="kpi-value">{{ $totalEnProgreso }}</div>
            <div class="kpi-label">En Progreso</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card" style="border: 2px solid var(--nc-green-300);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="kpi-icon kpi-icon-green"><i class="bi bi-gift"></i></div>
            </div>
            <div class="kpi-value" style="color: var(--nc-green-700);">{{ $totalListos }}</div>
            <div class="kpi-label">Listos para Premio</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="kpi-icon" style="background: #dbeafe; color: #2563eb;"><i class="bi bi-check2-all"></i></div>
            </div>
            <div class="kpi-value">{{ $totalEntregados }}</div>
            <div class="kpi-label">Entregados</div>
        </div>
    </div>
</div>

{{-- Alert si hay premios listos --}}
@if($totalListos > 0)
<div class="fideliz-alert d-flex align-items-center gap-3">
    <div style="font-size: 28px;">🎉</div>
    <div>
        <strong style="color: var(--nc-green-800);">¡{{ $totalListos }} cliente(s) listo(s) para recibir premio!</strong>
        <div style="font-size: 13px; color: var(--nc-green-700);">Botella 2L de Bebida Nopal gratis por alcanzar S/{{ number_format($umbral, 0) }} en productos</div>
    </div>
</div>
@endif

{{-- Buscador --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                class="form-control form-control-sm rounded-3" placeholder="🔍 Buscar por DNI o nombre..." style="max-width:360px;">
            <button class="btn btn-success btn-sm px-3">Buscar</button>
            @if(request()->anyFilled(['search']))
                <a href="{{ route('fidelizacion.index') }}" class="btn btn-outline-secondary btn-sm">✕ Limpiar</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabs --}}
<div class="d-flex gap-2 mb-4 flex-wrap">
    <button class="tab-btn active" onclick="showTab('progreso')">🟡 En Progreso ({{ $totalEnProgreso }})</button>
    <button class="tab-btn" onclick="showTab('pendientes')">🟢 Listos para Entregar ({{ $canjesPendientes->count() }})</button>
    <button class="tab-btn" onclick="showTab('entregados')">✅ Entregados ({{ $totalEntregados }})</button>
</div>

{{-- Tab: En Progreso --}}
<div id="tab-progreso" class="tab-content">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr style="background:#f0fdf4; font-size:12px; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">
                            <th class="px-4 py-3">Cliente</th>
                            <th>DNI</th>
                            <th class="text-center">Compras</th>
                            <th class="text-end">Acumulado</th>
                            <th style="min-width:160px;">Progreso</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enProgreso as $cliente)
                        @php $pct = min(100, round(((float)$cliente->acumulado_naturales / $umbral) * 100)); @endphp
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:center;justify-content:center;font-weight:700;color:#92400e;font-size:14px;">
                                        {{ strtoupper(substr($cliente->nombre,0,1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:14px;">{{ $cliente->nombre }} {{ $cliente->apellido }}</div>
                                        @if($cliente->telefono)<small class="text-muted">{{ $cliente->telefono }}</small>@endif
                                    </div>
                                </div>
                            </td>
                            <td><code style="font-size:13px;">{{ $cliente->dni }}</code></td>
                            <td class="text-center">
                                <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;">{{ $cliente->ventas_count }} ventas</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold" style="color:var(--nc-green-700);">S/ {{ number_format($cliente->acumulado_naturales, 2) }}</div>
                                <small class="text-muted">de S/{{ number_format($umbral, 0) }}</small>
                            </td>
                            <td>
                                <div class="fideliz-progress">
                                    <div class="fideliz-progress-bar {{ $pct >= 80 ? 'green' : 'amber' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <small class="text-muted" style="font-size:11px;">{{ $pct }}% completado</small>
                            </td>
                            <td class="text-center">
                                <span class="status-badge status-progreso">🟡 En progreso</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-hourglass" style="font-size:36px; opacity:0.3;"></i>
                                <div class="mt-2">No hay clientes en progreso</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tab: Listos para Entregar --}}
<div id="tab-pendientes" class="tab-content" style="display:none;">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr style="background:#f0fdf4; font-size:12px; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">
                            <th class="px-4 py-3">Cliente</th>
                            <th>DNI</th>
                            <th>Premio</th>
                            <th>Fecha</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($canjesPendientes as $canje)
                        <tr>
                            <td class="px-4">
                                <div class="fw-semibold" style="font-size:14px;">{{ $canje->cliente->nombreCompleto() }}</div>
                            </td>
                            <td><code style="font-size:13px;">{{ $canje->cliente->dni }}</code></td>
                            <td>
                                <span class="badge bg-success fs-6 px-3 py-2">🎁 {{ $canje->descripcion_premio }}</span>
                            </td>
                            <td>
                                <div>{{ $canje->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $canje->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="text-center">
                                <span class="status-badge status-listo">🟢 Listo para entregar</span>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('fidelizacion.entregar', $canje) }}" method="POST"
                                      onsubmit="return confirm('¿Confirmas que el premio fue entregado a {{ addslashes($canje->cliente->nombreCompleto()) }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm px-3">
                                        <i class="bi bi-check2-circle me-1"></i>Marcar Entregado
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-gift" style="font-size:36px; opacity:0.3;"></i>
                                <div class="mt-2">No hay premios pendientes de entrega</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tab: Entregados --}}
<div id="tab-entregados" class="tab-content" style="display:none;">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr style="background:#f0fdf4; font-size:12px; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">
                            <th class="px-4 py-3">Cliente</th>
                            <th>DNI</th>
                            <th>Premio</th>
                            <th>Fecha Generado</th>
                            <th>Fecha Entrega</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($canjesEntregados as $canje)
                        <tr>
                            <td class="px-4">
                                <div class="fw-semibold" style="font-size:14px;">{{ $canje->cliente->nombreCompleto() }}</div>
                            </td>
                            <td><code style="font-size:13px;">{{ $canje->cliente->dni }}</code></td>
                            <td>
                                <span class="badge bg-secondary px-3 py-2">✅ {{ $canje->descripcion_premio }}</span>
                            </td>
                            <td>{{ $canje->created_at->format('d/m/Y') }}</td>
                            <td>{{ $canje->entregado_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-center">
                                <span class="status-badge status-entregado">✅ Entregado</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all" style="font-size:36px; opacity:0.3;"></i>
                                <div class="mt-2">Aún no se han entregado premios</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = '';
    event.target.classList.add('active');
}
</script>
@endsection
