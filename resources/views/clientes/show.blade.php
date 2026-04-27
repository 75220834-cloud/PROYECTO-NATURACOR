@extends('layouts.app')
@section('title', "Cliente: {$cliente->nombre}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('clientes.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">👤 {{ $cliente->nombre }} {{ $cliente->apellido }}</h4>
        <small class="text-muted">DNI: {{ $cliente->dni }}</small>
    </div>
    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-outline-success btn-sm ms-auto">
        <i class="bi bi-pencil me-1"></i> Editar
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#bbf7d0,#86efac);display:flex;align-items:center;justify-content:center;font-weight:700;color:#15803d;font-size:28px;margin:0 auto 12px;">
                {{ strtoupper(substr($cliente->nombre,0,1)) }}
            </div>
            <h5 class="fw-bold mb-1">{{ $cliente->nombre }} {{ $cliente->apellido }}</h5>
            <p class="text-muted mb-0" style="font-size:13px;">{{ $cliente->telefono ?? 'Sin teléfono' }}</p>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div style="font-size:26px;font-weight:800;color:#22c55e;">{{ $cliente->ventas->where('estado','completada')->count() }}</div>
                    <small class="text-muted">Compras totales</small>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div style="font-size:26px;font-weight:800;color:#22c55e;">S/ {{ number_format($totalCompras, 2) }}</div>
                    <small class="text-muted">Total gastado</small>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 p-3 mt-3">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;letter-spacing:0.5px;margin-bottom:8px;">Información de contacto</div>
            <table class="table table-sm mb-0">
                <tr><td class="text-muted" style="font-size:13px;">DNI</td><td><code>{{ $cliente->dni }}</code></td></tr>
                <tr><td class="text-muted" style="font-size:13px;">Teléfono</td><td>{{ $cliente->telefono ?? '—' }}</td></tr>
                <tr><td class="text-muted" style="font-size:13px;">Email</td><td>{{ $cliente->email ?? '—' }}</td></tr>
                <tr><td class="text-muted" style="font-size:13px;">Cliente desde</td><td>{{ $cliente->created_at->format('d/m/Y') }}</td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Perfil de Salud -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header border-0 px-4 py-3 d-flex justify-content-between align-items-center"
         style="background:rgba(56,189,248,0.08);">
        <h6 class="fw-bold mb-0" style="color:#38bdf8;">
            <i class="bi bi-heart-pulse me-2"></i>🏥 Perfil de Salud
        </h6>
        <button class="btn btn-sm" style="border:1px solid rgba(56,189,248,0.40);color:#38bdf8;border-radius:8px;font-size:12px;"
                onclick="toggleEditorPadecimientos()">
            <i class="bi bi-pencil me-1"></i>Editar padecimientos
        </button>
    </div>
    <div class="card-body px-4 py-3">

        {{-- Padecimientos actuales --}}
        <div id="padecimientosActuales">
            @php
                $padecimientos = $cliente->padecimientos()->with('enfermedad')->get();
            @endphp
            @if($padecimientos->isEmpty())
                <p class="text-muted mb-0" style="font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>Sin padecimientos registrados aún.
                </p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach($padecimientos as $pad)
                    <span class="badge" style="background:rgba(56,189,248,0.15);color:#38bdf8;border:1px solid rgba(56,189,248,0.35);font-size:12px;padding:6px 12px;border-radius:20px;">
                        <i class="bi bi-heart-pulse me-1"></i>{{ $pad->enfermedad->nombre }}
                    </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Editor de padecimientos (oculto por defecto) --}}
        <div id="editorPadecimientos" style="display:none;" class="mt-3">
            <hr style="border-color:rgba(56,189,248,0.20);">
            <p style="font-size:12px;color:rgba(255,255,255,0.50);">
                Selecciona los padecimientos del cliente:
            </p>
            @php
                $todasEnfermedades = \App\Models\Enfermedad::where('activa', true)->orderBy('nombre')->get();
                $padecimientosIds  = $cliente->padecimientos()->pluck('enfermedad_id')->toArray();
            @endphp
            <div class="d-flex flex-wrap gap-2 mb-3" id="chipsEnfermedades">
                @foreach($todasEnfermedades as $enf)
                <button type="button"
                        class="chip-enfermedad"
                        data-id="{{ $enf->id }}"
                        data-seleccionado="{{ in_array($enf->id, $padecimientosIds) ? '1' : '0' }}"
                        onclick="toggleChip(this)"
                        style="
                            display:inline-flex;align-items:center;gap:6px;
                            padding:7px 14px;border-radius:20px;cursor:pointer;
                            font-size:12px;font-weight:500;font-family:inherit;
                            transition:all 0.15s;
                            {{ in_array($enf->id, $padecimientosIds)
                                ? 'background:rgba(56,189,248,0.18);border:1px solid rgba(56,189,248,0.60);color:#38bdf8;'
                                : 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.60);' }}
                        ">
                    {{ $enf->nombre }}
                </button>
                @endforeach
            </div>
            <div class="d-flex gap-2">
                <button type="button" onclick="toggleEditorPadecimientos()"
                        class="btn btn-sm btn-light px-4">
                    Cancelar
                </button>
                <button type="button" onclick="guardarPadecimientosCliente({{ $cliente->id }})"
                        class="btn btn-sm px-4"
                        style="background:rgba(56,189,248,0.20);border:1px solid rgba(56,189,248,0.50);color:#38bdf8;border-radius:8px;">
                    <i class="bi bi-check2 me-1"></i>Guardar
                </button>
            </div>
            <div id="padecimientoMsg" class="mt-2" style="font-size:12px;display:none;"></div>
        </div>

    </div>
</div>

@section('scripts')
<script>
function toggleEditorPadecimientos() {
    const editor = document.getElementById('editorPadecimientos');
    editor.style.display = editor.style.display === 'none' ? '' : 'none';
}

function toggleChip(btn) {
    const activo = btn.dataset.seleccionado === '1';
    if (activo) {
        btn.dataset.seleccionado = '0';
        btn.style.background = 'rgba(255,255,255,0.05)';
        btn.style.border = '1px solid rgba(255,255,255,0.12)';
        btn.style.color = 'rgba(255,255,255,0.60)';
    } else {
        btn.dataset.seleccionado = '1';
        btn.style.background = 'rgba(56,189,248,0.18)';
        btn.style.border = '1px solid rgba(56,189,248,0.60)';
        btn.style.color = '#38bdf8';
    }
}

function guardarPadecimientosCliente(clienteId) {
    const seleccionados = Array.from(
        document.querySelectorAll('.chip-enfermedad')
    ).filter(b => b.dataset.seleccionado === '1')
     .map(b => parseInt(b.dataset.id));

    const msg = document.getElementById('padecimientoMsg');
    msg.style.display = '';
    msg.style.color = '#9caea4';
    msg.textContent = 'Guardando...';

    fetch(`/api/clientes/${clienteId}/padecimientos`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ enfermedad_ids: seleccionados })
    })
    .then(r => r.json())
    .then(data => {
        msg.style.color = '#38bdf8';
        msg.textContent = '✓ Perfil de salud guardado correctamente.';
        // Recargar los badges actuales
        const cont = document.getElementById('padecimientosActuales');
        const pads = data.padecimientos || [];
        if (pads.length === 0) {
            cont.innerHTML = '<p class="text-muted mb-0" style="font-size:13px;"><i class="bi bi-info-circle me-1"></i>Sin padecimientos registrados aún.</p>';
        } else {
            cont.innerHTML = '<div class="d-flex flex-wrap gap-2">' +
                pads.map(p => `<span class="badge" style="background:rgba(56,189,248,0.15);color:#38bdf8;border:1px solid rgba(56,189,248,0.35);font-size:12px;padding:6px 12px;border-radius:20px;"><i class="bi bi-heart-pulse me-1"></i>${p.nombre}</span>`).join('') +
            '</div>';
        }
        setTimeout(() => {
            document.getElementById('editorPadecimientos').style.display = 'none';
            msg.style.display = 'none';
        }, 1500);
    })
    .catch(() => {
        msg.style.color = '#fca5a5';
        msg.textContent = '✗ Error al guardar. Intenta de nuevo.';
    });
}
</script>
@endsection

<!-- Historial de compras -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 px-4 py-3">
        <h6 class="fw-bold mb-0">🧾 Historial de Compras</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                        <th class="px-4 py-3">Boleta</th>
                        <th>Fecha</th>
                        <th>Método Pago</th>
                        <th class="text-end px-4">Total</th>
                        <th class="text-center">Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cliente->ventas->sortByDesc('created_at')->take(20) as $venta)
                    <tr>
                        <td class="px-4"><code style="font-size:12px;">{{ $venta->numero_boleta ?? 'N/A' }}</code></td>
                        <td style="font-size:13px;">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge" style="background:#f0fdf4;color:#15803d;font-size:11px;">{{ $venta->metodo_pago }}</span></td>
                        <td class="text-end px-4 fw-semibold" style="color:#16a34a;">S/ {{ number_format($venta->total,2) }}</td>
                        <td class="text-center">
                            @if($venta->estado === 'completada')
                                <span class="badge" style="background:#dcfce7;color:#15803d;">Completada</span>
                            @elseif($venta->estado === 'anulada')
                                <span class="badge" style="background:#fef2f2;color:#dc2626;">Anulada</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('boletas.show', $venta) }}" class="btn btn-light btn-sm"><i class="bi bi-receipt"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Sin compras registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
