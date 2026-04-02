@extends('layouts.app')
@section('title', 'Detalle de Sesión')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('caja.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">💰 Sesión de Caja</h4>
        <small class="text-muted">{{ $cajaSesion->apertura_at?->format('d/m/Y H:i') }}</small>
    </div>
    <span class="badge ms-2 {{ $cajaSesion->estado === 'abierta' ? 'bg-success' : 'bg-secondary' }}">
        {{ ucfirst($cajaSesion->estado) }}
    </span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                <h6 class="fw-bold">📋 Resumen</h6>
            </div>
            <div class="card-body px-4">
                <div class="row g-3">
                    <div class="col-4 text-center">
                        <div style="font-size:20px;font-weight:800;color:#22c55e;">S/ {{ number_format($cajaSesion->monto_inicial,2) }}</div>
                        <small class="text-muted">Monto inicial</small>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:20px;font-weight:800;color:#22c55e;">S/ {{ number_format($cajaSesion->total_esperado,2) }}</div>
                        <small class="text-muted">Total esperado</small>
                    </div>
                    <div class="col-4 text-center">
                        @if($cajaSesion->monto_real_cierre !== null)
                        <div style="font-size:20px;font-weight:800;color:{{ $cajaSesion->diferencia >= 0 ? '#22c55e' : '#dc2626' }};">
                            S/ {{ number_format($cajaSesion->diferencia,2) }}
                        </div>
                        <small class="text-muted">Diferencia</small>
                        @else
                        <div style="font-size:20px;font-weight:800;color:#9ca3af;">—</div>
                        <small class="text-muted">Aún no cerrada</small>
                        @endif
                    </div>
                </div>
                <hr>
                <div class="row g-2">
                    <div class="col-3 text-center p-2" style="background:#f0fdf4;border-radius:10px;">
                        <div class="fw-bold text-success">S/ {{ number_format($cajaSesion->total_efectivo,2) }}</div>
                        <small class="text-muted">Efectivo</small>
                    </div>
                    <div class="col-3 text-center p-2" style="background:#f0fdf4;border-radius:10px;">
                        <div class="fw-bold text-success">S/ {{ number_format($cajaSesion->total_yape,2) }}</div>
                        <small class="text-muted">Yape</small>
                    </div>
                    <div class="col-3 text-center p-2" style="background:#f0fdf4;border-radius:10px;">
                        <div class="fw-bold text-success">S/ {{ number_format($cajaSesion->total_plin,2) }}</div>
                        <small class="text-muted">Plin</small>
                    </div>
                    <div class="col-3 text-center p-2" style="background:#f0fdf4;border-radius:10px;">
                        <div class="fw-bold text-success">S/ {{ number_format($cajaSesion->total_otros,2) }}</div>
                        <small class="text-muted">Otros</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="fw-bold mb-3">👤 Información</div>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Empleado</td><td class="fw-semibold">{{ $cajaSesion->empleado?->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Apertura</td><td>{{ $cajaSesion->apertura_at?->format('H:i d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Cierre</td><td>{{ $cajaSesion->cierre_at?->format('H:i d/m/Y') ?? 'Abierta' }}</td></tr>
                    <tr><td class="text-muted">Ventas</td><td>{{ $cajaSesion->ventas->count() }}</td></tr>
                    @if($cajaSesion->notas_cierre)
                    <tr><td class="text-muted">Notas</td><td>{{ $cajaSesion->notas_cierre }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Movimientos -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 px-4 py-3">
        <h6 class="fw-bold mb-0">💸 Movimientos de Caja</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                        <th class="px-4 py-3">Tipo</th><th>Descripción</th><th>Método</th><th>Hora</th><th class="text-end px-4">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cajaSesion->movimientos as $mov)
                    <tr>
                        <td class="px-4">
                            @if($mov->tipo === 'ingreso')
                                <span class="badge" style="background:#dcfce7;color:#15803d;">↑ Ingreso</span>
                            @else
                                <span class="badge" style="background:#fef2f2;color:#dc2626;">↓ Egreso</span>
                            @endif
                        </td>
                        <td>{{ $mov->descripcion }}</td>
                        <td>{{ $mov->metodo_pago }}</td>
                        <td style="font-size:12px;">{{ $mov->created_at->format('H:i') }}</td>
                        <td class="text-end px-4 fw-bold {{ $mov->tipo === 'ingreso' ? 'text-success' : 'text-danger' }}">
                            {{ $mov->tipo === 'ingreso' ? '+' : '-' }} S/ {{ number_format($mov->monto,2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-3 text-muted">Sin movimientos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ventas de la sesión -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 px-4 py-3">
        <h6 class="fw-bold mb-0">🧾 Ventas de esta sesión</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                        <th class="px-4 py-3">Boleta</th><th>Cliente</th><th>Pago</th><th class="text-end px-4">Total</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cajaSesion->ventas as $venta)
                    <tr>
                        <td class="px-4"><code style="font-size:12px;">{{ $venta->numero_boleta ?? 'N/A' }}</code></td>
                        <td>{{ $venta->cliente?->nombre ?? 'General' }}</td>
                        <td>{{ $venta->metodo_pago }}</td>
                        <td class="text-end px-4 fw-bold text-success">S/ {{ number_format($venta->total,2) }}</td>
                        <td><a href="{{ route('boletas.show', $venta) }}" class="btn btn-light btn-sm"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-3 text-muted">Sin ventas en esta sesión</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
