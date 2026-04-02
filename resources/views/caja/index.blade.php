@extends('layouts.app')
@section('title', 'Caja')
@section('page-title', '💰 Control de Caja')
@section('content')

@if(!$sesionActiva)
<!-- Abrir caja -->
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="nc-card text-center py-4">
            <div style="width:80px; height:80px; border-radius:50%; background: var(--nc-green-50); display:flex; align-items:center; justify-content:center; margin: 0 auto 16px; border: 3px solid var(--nc-green-200);">
                <i class="bi bi-lock" style="font-size:36px; color: var(--nc-green-600);"></i>
            </div>
            <h5 class="fw-700" style="color:#1a2e1a;">Caja cerrada</h5>
            <p class="text-muted" style="font-size:13px;">Para registrar ventas, abre la caja con el monto inicial.</p>
            <form method="POST" action="{{ route('caja.abrir') }}" class="mt-4">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label fw-600" style="font-size:13px;">Monto inicial (efectivo)</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius:10px 0 0 10px; border: 1.5px solid #d1fae5; background: var(--nc-green-50); font-weight:600; color: var(--nc-green-700);">S/</span>
                        <input type="number" name="monto_inicial" step="0.01" min="0" class="form-control nc-input" placeholder="0.00" required style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                <button type="submit" class="btn btn-naturacor w-100 py-3" style="font-size:15px; font-weight:700; border-radius:12px;">
                    <i class="bi bi-unlock me-2"></i>Abrir Caja
                </button>
            </form>
        </div>
    </div>
</div>

@else
<!-- Caja abierta -->
<div class="row g-4">
    <!-- Resumen caja -->
    <div class="col-12 col-lg-4">
        <div class="nc-card" style="background: var(--nc-sidebar-bg); color: white;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span style="font-size:14px; font-weight:600; color: var(--nc-green-400);">📊 Resumen de caja</span>
                <span style="background: rgba(74,222,128,0.2); color: var(--nc-green-400); font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px;">ABIERTA</span>
            </div>
            <div class="mb-3">
                <div style="font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Apertura</div>
                <div style="font-size:13px; color:rgba(255,255,255,0.8);">{{ $sesionActiva->apertura_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="mb-3">
                <div style="font-size:11px; color:rgba(255,255,255,0.5);">Monto inicial</div>
                <div style="font-size:20px; font-weight:700; color:white;">S/ {{ number_format($sesionActiva->monto_inicial, 2) }}</div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.15);">
            <div class="row g-2">
                @foreach(['Efectivo' => $sesionActiva->total_efectivo, 'Yape' => $sesionActiva->total_yape, 'Plin' => $sesionActiva->total_plin, 'Otros' => $sesionActiva->total_otros] as $label => $val)
                <div class="col-6">
                    <div style="background: rgba(255,255,255,0.08); border-radius: 10px; padding: 10px;">
                        <div style="font-size:10px; color:rgba(255,255,255,0.5); text-transform:uppercase;">{{ $label }}</div>
                        <div style="font-size:16px; font-weight:700; color:white;">S/ {{ number_format($val, 2) }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            <hr style="border-color: rgba(255,255,255,0.15);">
            <div class="d-flex justify-content-between">
                <span style="font-size:13px; color:rgba(255,255,255,0.7);">Total esperado</span>
                <span style="font-size:22px; font-weight:700; color: var(--nc-green-400);">S/ {{ number_format($sesionActiva->total_esperado, 2) }}</span>
            </div>
        </div>

        <!-- Cerrar caja -->
        <div class="nc-card mt-4" style="border: 2px solid #fecdd3;">
            <div class="nc-card-header" style="color: #9f1239;">
                <span><i class="bi bi-lock me-2"></i>Cerrar caja</span>
            </div>
            <form method="POST" action="{{ route('caja.cerrar') }}" onsubmit="return confirm('¿Cerrar la caja? Esta acción no se puede deshacer.')">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:13px;">Monto real en caja</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius:10px 0 0 10px; background: #ffe4e6; border:1.5px solid #fecdd3; color:#e11d48; font-weight:700;">S/</span>
                        <input type="number" name="monto_real" step="0.01" min="0" id="montoReal"
                            class="form-control" placeholder="0.00" required
                            style="border-radius: 0 10px 10px 0; border: 1.5px solid #fecdd3;"
                            oninput="calcDiferencia()">
                    </div>
                    <div id="diferenciaInfo" class="mt-2 p-2 rounded" style="background: #f8fafc; font-size:13px; display:none;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:13px;">Notas de cierre</label>
                    <textarea name="notas" class="form-control nc-input" rows="2" placeholder="Observaciones..."></textarea>
                </div>
                <button type="submit" class="btn w-100 py-2" style="background: #ffe4e6; color: #e11d48; border: 1.5px solid #fecdd3; border-radius:10px; font-weight:700;">
                    <i class="bi bi-lock-fill me-2"></i>Cerrar Caja
                </button>
            </form>
        </div>
    </div>

    <!-- Movimientos -->
    <div class="col-12 col-lg-8">
        <!-- Registrar movimiento -->
        <div class="nc-card mb-4">
            <div class="nc-card-header">
                <span><i class="bi bi-plus-circle me-2 text-success"></i>Registrar movimiento</span>
            </div>
            <form method="POST" action="{{ route('caja.movimiento') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label fw-600" style="font-size:12px;">Tipo</label>
                    <select name="tipo" class="nc-input form-select" required>
                        <option value="ingreso">Ingreso</option>
                        <option value="egreso">Egreso</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-600" style="font-size:12px;">Monto</label>
                    <input type="number" name="monto" step="0.01" min="0.01" class="nc-input form-control" placeholder="0.00" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-600" style="font-size:12px;">Método</label>
                    <select name="metodo_pago" class="nc-input form-select">
                        <option value="efectivo">Efectivo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-600" style="font-size:12px;">Descripción</label>
                    <input type="text" name="descripcion" class="nc-input form-control" placeholder="Ej: Almuerzo, gastos..." required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-naturacor">
                        <i class="bi bi-plus me-2"></i>Registrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Historial de movimientos -->
        <div class="nc-card">
            <div class="nc-card-header">
                <span><i class="bi bi-clock-history me-2"></i>Movimientos del turno</span>
            </div>
            <div class="table-responsive">
                <table class="table nc-table mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Método</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sesionActiva->movimientos->sortByDesc('created_at') as $mov)
                        <tr>
                            <td style="font-size:12px; color:#6b7280;">{{ $mov->created_at->format('H:i') }}</td>
                            <td>
                                @if($mov->tipo === 'ingreso')
                                    <span style="color: var(--nc-green-600); font-weight:600; font-size:12px;">↑ Ingreso</span>
                                @else
                                    <span style="color: #dc2626; font-weight:600; font-size:12px;">↓ Egreso</span>
                                @endif
                            </td>
                            <td style="font-size:13px;">{{ $mov->descripcion }}</td>
                            <td><span style="font-size:11px; text-transform:capitalize;">{{ $mov->metodo_pago }}</span></td>
                            <td style="font-weight:700; {{ $mov->tipo==='ingreso' ? 'color:var(--nc-green-700)' : 'color:#dc2626' }}">
                                {{ $mov->tipo === 'ingreso' ? '+' : '-' }}S/ {{ number_format($mov->monto, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:13px;">Sin movimientos aún</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Historial de sesiones -->
@if($sesionesAnteriores->count())
<div class="nc-card mt-4">
    <div class="nc-card-header">
        <span><i class="bi bi-archive me-2"></i>Sesiones anteriores</span>
    </div>
    <div class="table-responsive">
        <table class="table nc-table mb-0">
            <thead>
                <tr><th>Fecha</th><th>Apertura</th><th>Cierre</th><th>Esperado</th><th>Real</th><th>Diferencia</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($sesionesAnteriores as $ses)
                <tr>
                    <td style="font-size:12px;">{{ $ses->apertura_at->format('d/m/Y') }}</td>
                    <td>{{ $ses->apertura_at->format('H:i') }}</td>
                    <td>{{ $ses->cierre_at?->format('H:i') ?? '—' }}</td>
                    <td>S/ {{ number_format($ses->total_esperado, 2) }}</td>
                    <td>S/ {{ number_format($ses->monto_real_cierre ?? 0, 2) }}</td>
                    <td style="font-weight:700; {{ $ses->diferencia >= 0 ? 'color:var(--nc-green-700)' : 'color:#dc2626' }}">
                        {{ $ses->diferencia >= 0 ? '+' : '' }}S/ {{ number_format($ses->diferencia, 2) }}
                    </td>
                    <td><a href="{{ route('caja.show', $ses) }}" class="btn btn-sm btn-naturacor-outline">Ver</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
const totalEsperado = {{ $sesionActiva?->total_esperado ?? 0 }};
function calcDiferencia() {
    const real = parseFloat(document.getElementById('montoReal')?.value || 0);
    const diff = real - totalEsperado;
    const div = document.getElementById('diferenciaInfo');
    div.style.display = '';
    div.style.background = diff >= 0 ? '#dcfce7' : '#ffe4e6';
    div.style.color = diff >= 0 ? '#15803d' : '#dc2626';
    div.innerHTML = `<strong>${diff >= 0 ? '✓ Sobrante' : '⚠️ Faltante'}:</strong> S/ ${Math.abs(diff).toFixed(2)}`;
}
</script>
@endsection
