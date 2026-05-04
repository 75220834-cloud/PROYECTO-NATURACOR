@extends('layouts.app')
@section('title', 'Auditoría')
@section('page-title', 'Registro de Actividad')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1" style="font-weight:700;">Log de Auditoría</h5>
        <span class="text-muted" style="font-size:13px;">{{ $logs->total() }} registros encontrados</span>
    </div>
</div>

{{-- Filtros --}}
<div class="nc-card mb-4">
    <form method="GET" class="row g-3 align-items-end p-3">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Usuario</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach($usuarios as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Acción</label>
            <input type="text" name="accion" class="form-control form-control-sm" value="{{ request('accion') }}" placeholder="crear, editar, eliminar...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Fecha</label>
            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ request('fecha') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-naturacor btn-sm flex-grow-1"><i class="bi bi-search me-1"></i>Filtrar</button>
            <a href="{{ route('logs.index') }}" class="btn btn-naturacor-outline btn-sm"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<div class="nc-card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width:160px;">Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Tabla</th>
                    <th style="width:80px;">Reg. ID</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size:12px;white-space:nowrap;">
                        <i class="bi bi-clock me-1" style="opacity:0.4;"></i>
                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td>
                        <span class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                  style="width:28px;height:28px;background:rgba(40,199,111,0.15);color:#28c76f;font-size:12px;font-weight:700;">
                                {{ strtoupper(substr($log->usuario?->name ?? '?', 0, 1)) }}
                            </span>
                            <span style="font-size:13px;font-weight:500;">{{ $log->usuario?->name ?? 'Sistema' }}</span>
                        </span>
                    </td>
                    <td>
                        @php
                            $iconMap = [
                                'crear' => ['bi-plus-circle', '#4ade80'],
                                'editar' => ['bi-pencil-square', '#818cf8'],
                                'eliminar' => ['bi-trash', '#ef4444'],
                                'abrir' => ['bi-unlock', '#38bdf8'],
                                'cerrar' => ['bi-lock', '#fbbf24'],
                                'anular' => ['bi-x-octagon', '#ef4444'],
                            ];
                            $accion = strtolower($log->accion);
                            $matched = collect($iconMap)->first(fn($v, $k) => str_contains($accion, $k));
                            $icon = $matched[0] ?? 'bi-activity';
                            $color = $matched[1] ?? '#94a3b8';
                        @endphp
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi {{ $icon }}" style="color:{{ $color }};"></i>
                            <span style="font-size:13px;">{{ $log->accion }}</span>
                        </span>
                    </td>
                    <td><span class="badge bg-dark bg-opacity-25" style="font-size:11px;font-weight:500;">{{ $log->tabla_afectada ?? '-' }}</span></td>
                    <td style="font-size:12px;color:rgba(255,255,255,0.5);">#{{ $log->registro_id ?? '-' }}</td>
                    <td style="font-size:11px;color:rgba(255,255,255,0.35);">{{ $log->ip ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-shield-check" style="font-size:40px;opacity:0.3;"></i>
                        <p class="mt-2">No hay registros de auditoría</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $logs->withQueryString()->links() }}</div>
</div>
@endsection
