@extends('layouts.app')
@section('title', 'Asistente IA — NATURACOR')
@section('styles')
<style>
.ia-response { white-space: pre-wrap; font-size: 14px; line-height: 1.8; font-family: inherit; }
.kpi-ia { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; text-align: center; }
.kpi-ia .value { font-size: 22px; font-weight: 700; color: #15803d; }
.kpi-ia .label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-top: 4px; }
</style>
@endsection
@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0" style="color:#1a2e1a">🤖 Asistente de Negocio IA</h4>
    <small class="text-muted">Análisis inteligente de tu negocio en tiempo real</small>
</div>

<!-- KPIs de hoy -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-ia">
            <div class="value">{{ $analisis['ventas_hoy']['count'] }}</div>
            <div class="label">Ventas hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-ia">
            <div class="value">S/ {{ number_format($analisis['ventas_hoy']['total'], 0) }}</div>
            <div class="label">Ingresos hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-ia">
            <div class="value">{{ $analisis['ventas_semana']['count'] }}</div>
            <div class="label">Ventas 7 días</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-ia" style="{{ $analisis['stock_critico']->count() > 0 ? 'background:linear-gradient(135deg,#fff1f2,#ffe4e6);border-color:#fca5a5;' : '' }}">
            <div class="value" style="{{ $analisis['stock_critico']->count() > 0 ? 'color:#dc2626;' : '' }}">{{ $analisis['stock_critico']->count() }}</div>
            <div class="label">Stock crítico</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Panel Chat -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#4ade80,#16a34a);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🤖</div>
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="color:#1a2e1a;">Asistente NATURACOR</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" id="statusBadge" style="background:{{ $modoOnline ? '#dcfce7' : '#fef3c7' }};color:{{ $modoOnline ? '#15803d' : '#92400e' }};font-size:11px;">
                                {{ $modoOnline ? '🟢 Con IA Online' : '🟡 Análisis Local (Inteligente)' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Historial de chat -->
                <div id="chatHistory" style="min-height:200px; max-height:400px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; background:#f9fafb;">
                    <div class="text-muted" style="font-size:13px;">
                        👋 Hola! Soy el asistente de NATURACOR. Puedo analizar tus ventas, stock, clientes y darte recomendaciones. ¿Qué deseas consultar?
                    </div>
                </div>

                <!-- Sugerencias rápidas -->
                <div class="mb-3 d-flex gap-2 flex-wrap">
                    <button class="btn btn-light btn-sm rounded-pill" onclick="preguntar(this.textContent)" style="font-size:12px;">📈 Ventas de hoy</button>
                    <button class="btn btn-light btn-sm rounded-pill" onclick="preguntar(this.textContent)" style="font-size:12px;">📦 Stock bajo</button>
                    <button class="btn btn-light btn-sm rounded-pill" onclick="preguntar(this.textContent)" style="font-size:12px;">🏆 Más vendidos</button>
                    <button class="btn btn-light btn-sm rounded-pill" onclick="preguntar(this.textContent)" style="font-size:12px;">👥 Clientes frecuentes</button>
                    <button class="btn btn-light btn-sm rounded-pill" onclick="preguntar(this.textContent)" style="font-size:12px;">💰 Ingresos del mes</button>
                </div>

                <!-- Input -->
                <div class="d-flex gap-2">
                    <input type="text" id="consultaIA" class="form-control rounded-pill"
                        placeholder="Escribe tu consulta..." style="font-size:14px;">
                    <button id="btnEnviar" class="btn btn-success rounded-pill px-4" onclick="enviarConsulta()" style="font-size:14px;">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Top ventas -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-trophy me-2 text-warning"></i>Top productos (7 días)</h6>
                @forelse($analisis['top_productos'] as $nombre => $total)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:#f0f0f0!important;">
                    <span style="font-size:13px;font-weight:500;">{{ $nombre }}</span>
                    <span class="badge" style="background:#dcfce7;color:#15803d;">{{ $total }} uds</span>
                </div>
                @empty
                <div class="text-muted text-center py-3" style="font-size:13px;">Sin ventas esta semana</div>
                @endforelse
            </div>
        </div>

        <!-- Stock crítico -->
        @if($analisis['stock_critico']->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-diamond me-2 text-danger"></i>Reponer urgente</h6>
                @foreach($analisis['stock_critico'] as $p)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:#f0f0f0!important;">
                    <span style="font-size:13px;font-weight:500;">{{ $p->nombre }}</span>
                    <div class="text-end">
                        <span class="badge" style="{{ $p->stock == 0 ? 'background:#fef2f2;color:#dc2626;' : 'background:#fef3c7;color:#92400e;' }}">{{ $p->stock == 0 ? 'AGOTADO' : "Stock: {$p->stock}" }}</span>
                        <div style="font-size:10px;color:#9ca3af;">mín: {{ $p->stock_minimo }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center">
                <div style="font-size:32px;">✅</div>
                <div class="fw-semibold mt-2" style="font-size:14px;color:#15803d;">Stock OK</div>
                <div class="text-muted" style="font-size:12px;">Todos los productos tienen stock suficiente</div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
@section('scripts')
<script>
function preguntar(texto) {
    document.getElementById('consultaIA').value = texto;
    enviarConsulta();
}

function enviarConsulta() {
    const input = document.getElementById('consultaIA');
    const consulta = input.value.trim();
    if (!consulta) return;

    const chat = document.getElementById('chatHistory');
    const btn = document.getElementById('btnEnviar');

    // Agregar de usuario
    const userMsg = document.createElement('div');
    userMsg.style.cssText = 'text-align:right; margin-bottom:12px;';
    userMsg.innerHTML = `<span style="background:#dcfce7;color:#15803d;padding:8px 14px;border-radius:18px 18px 4px 18px;font-size:13px;display:inline-block;max-width:80%;">${consulta}</span>`;
    chat.appendChild(userMsg);

    // Indicador de carga
    const loading = document.createElement('div');
    loading.id = 'iaLoading';
    loading.style.marginBottom = '12px';
    loading.innerHTML = `<span style="background:#f3f4f6;padding:8px 14px;border-radius:18px 18px 18px 4px;font-size:13px;display:inline-block;">
        <span class="spinner-border spinner-border-sm me-2" style="width:12px;height:12px;"></span>Analizando...
    </span>`;
    chat.appendChild(loading);
    chat.scrollTop = chat.scrollHeight;

    input.value = '';
    btn.disabled = true;

    fetch('/ia/analizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ consulta })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('iaLoading')?.remove();

        const iaMsg = document.createElement('div');
        iaMsg.style.cssText = 'margin-bottom:16px;';

        let contenido = data.resultado || 'No se pudo obtener una respuesta.';
        // Convertir **texto** a negrita
        contenido = contenido.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Convertir saltos de línea
        contenido = contenido.replace(/\n/g, '<br>');

        const modeColor = data.modo === 'online' ? '#eff6ff' : '#f0fdf4';
        const modeText = data.modo === 'online' ? '🟢 IA Online' : '🟡 Análisis Local';

        iaMsg.innerHTML = `
            <div style="background:${modeColor};padding:12px 16px;border-radius:18px 18px 18px 4px;font-size:13px;line-height:1.7;max-width:95%;">
                ${contenido}
                <div style="margin-top:8px;"><span style="font-size:10px;color:#9ca3af;">${modeText}</span></div>
            </div>`;
        chat.appendChild(iaMsg);
        chat.scrollTop = chat.scrollHeight;
    })
    .catch(() => {
        document.getElementById('iaLoading')?.remove();
        const err = document.createElement('div');
        err.innerHTML = `<span style="background:#fef2f2;color:#dc2626;padding:8px 14px;border-radius:12px;font-size:13px;">❌ Error de conexión</span>`;
        chat.appendChild(err);
    })
    .finally(() => { btn.disabled = false; });
}

document.getElementById('consultaIA').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') enviarConsulta();
});
</script>
@endsection
