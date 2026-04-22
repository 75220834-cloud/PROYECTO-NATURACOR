@extends('layouts.app')
@section('title', 'Asistente IA — NATURACOR')
@section('page-title', 'Asistente IA')
@section('styles')
<style>
/* ── KPI IA ─────────────────────────────────────────────────── */
.kpi-ia {
    background: rgba(7, 26, 16, 0.50);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(40,199,111,0.18);
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-ia:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.40), 0 0 16px rgba(40,199,111,0.10);
}
.kpi-ia .value { font-size: 26px; font-weight: 700; color: #ffffff; }
.kpi-ia .label { font-size: 11px; color: #9caea4; text-transform: uppercase; font-weight: 600; margin-top: 4px; letter-spacing: 0.8px; }
.kpi-ia.critico { border-color: rgba(231,76,60,0.35); }
.kpi-ia.critico .value { color: #e74c3c; }

/* ── CHAT BOX ───────────────────────────────────────────────── */
#chatHistory {
    min-height: 200px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    background: rgba(0,0,0,0.20);
}
.msg-user {
    text-align: right;
    margin-bottom: 12px;
}
.msg-user span {
    background: rgba(40,199,111,0.20);
    color: #86efac;
    padding: 8px 14px;
    border-radius: 18px 18px 4px 18px;
    font-size: 13px;
    display: inline-block;
    max-width: 80%;
    border: 1px solid rgba(40,199,111,0.25);
}
.msg-ia {
    margin-bottom: 16px;
}
.msg-ia .bubble {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.09);
    padding: 12px 16px;
    border-radius: 18px 18px 18px 4px;
    font-size: 13px;
    line-height: 1.7;
    max-width: 95%;
    color: rgba(255,255,255,0.88);
    backdrop-filter: blur(8px);
}
.msg-ia .bubble.online  { border-color: rgba(52,152,219,0.25);  background: rgba(52,152,219,0.07); }
.msg-ia .bubble.local   { border-color: rgba(40,199,111,0.25);  background: rgba(40,199,111,0.07); }
.msg-loading span {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.08);
    padding: 8px 14px;
    border-radius: 18px 18px 18px 4px;
    font-size: 13px;
    display: inline-block;
    color: #9caea4;
}
</style>
@endsection

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0">🤖 Asistente de Negocio IA</h4>
    <small class="text-muted">Análisis inteligente de tu negocio en tiempo real</small>
</div>

<!-- KPIs -->
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
        <div class="kpi-ia {{ $analisis['stock_critico']->count() > 0 ? 'critico' : '' }}">
            <div class="value">{{ $analisis['stock_critico']->count() }}</div>
            <div class="label">Stock crítico</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Panel Chat -->
    <div class="col-lg-8">
        <div class="nc-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#28c76f,#0e4b2a);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;box-shadow:0 0 16px rgba(40,199,111,0.30);">🤖</div>
                <div class="flex-grow-1">
                    <div class="fw-bold" style="color:#ffffff;">Asistente NATURACOR</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" id="statusBadge" style="background:{{ $modoOnline ? 'rgba(40,199,111,0.18)' : 'rgba(243,156,18,0.18)' }};color:{{ $modoOnline ? '#28c76f' : '#f39c12' }};border:1px solid {{ $modoOnline ? 'rgba(40,199,111,0.30)' : 'rgba(243,156,18,0.30)' }};font-size:11px;border-radius:20px;padding:3px 10px;">
                            {{ $modoOnline ? '🟢 Con IA Online' : '🟡 Análisis Local (Inteligente)' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Historial chat -->
            <div id="chatHistory">
                <div class="text-muted" style="font-size:13px;">
                    👋 Hola! Soy el asistente de NATURACOR. Puedo analizar tus ventas, stock, clientes y darte recomendaciones. ¿Qué deseas consultar?
                </div>
            </div>

            <!-- Sugerencias rápidas -->
            <div class="mb-3 d-flex gap-2 flex-wrap">
                @foreach(['📊 Ventas de hoy','📦 Stock bajo','🏆 Más vendidos','👥 Clientes frecuentes','💰 Ingresos del mes'] as $sug)
                <button class="btn btn-secondary btn-sm rounded-pill" onclick="preguntar('{{ $sug }}')" style="font-size:12px;">{{ $sug }}</button>
                @endforeach
            </div>

            <!-- Input -->
            <div class="d-flex gap-2">
                <input type="text" id="consultaIA" class="form-control"
                    placeholder="Escribe tu consulta..." style="font-size:14px; border-radius:30px;">
                <button id="btnEnviar" class="btn btn-naturacor px-4" onclick="enviarConsulta()" style="border-radius:30px;">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Top ventas -->
        <div class="nc-card mb-3">
            <div class="nc-card-header">
                <span><i class="bi bi-trophy me-2" style="color:#f39c12;"></i>Top productos (7 días)</span>
            </div>
            @forelse($analisis['top_productos'] as $nombre => $total)
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                <span style="font-size:13px; font-weight:500; color:rgba(255,255,255,0.85);">{{ $nombre }}</span>
                <span class="badge-stock-ok">{{ $total }} uds</span>
            </div>
            @empty
            <div class="text-muted text-center py-3" style="font-size:13px;">Sin ventas esta semana</div>
            @endforelse
        </div>

        <!-- Stock crítico / Stock OK -->
        @if($analisis['stock_critico']->isNotEmpty())
        <div class="nc-card">
            <div class="nc-card-header">
                <span style="color:#e74c3c;"><i class="bi bi-exclamation-diamond me-2"></i>Reponer urgente</span>
            </div>
            @foreach($analisis['stock_critico'] as $p)
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                <span style="font-size:13px; font-weight:500; color:rgba(255,255,255,0.85);">{{ $p->nombre }}</span>
                <div class="text-end">
                    @if($p->stock == 0)
                        <span class="badge-stock-zero">AGOTADO</span>
                    @else
                        <span class="badge-stock-low">Stock: {{ $p->stock }}</span>
                    @endif
                    <div style="font-size:10px; color:#9caea4;">mín: {{ $p->stock_minimo }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="nc-card text-center py-4">
            <div style="font-size:36px; color:var(--neon);">✅</div>
            <div class="fw-semibold mt-2" style="font-size:14px; color:var(--neon);">Stock OK</div>
            <div class="text-muted" style="font-size:12px;">Todos los productos tienen stock suficiente</div>
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
    const btn  = document.getElementById('btnEnviar');

    // Mensaje usuario
    const userMsg = document.createElement('div');
    userMsg.className = 'msg-user';
    userMsg.innerHTML = `<span>${consulta}</span>`;
    chat.appendChild(userMsg);

    // Loading
    const loading = document.createElement('div');
    loading.id = 'iaLoading';
    loading.className = 'msg-loading mb-3';
    loading.innerHTML = `<span><span class="spinner-border spinner-border-sm me-2" style="width:12px;height:12px;color:var(--neon);"></span>Analizando...</span>`;
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
        iaMsg.className = 'msg-ia';

        let contenido = data.resultado || 'No se pudo obtener una respuesta.';
        contenido = contenido.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        contenido = contenido.replace(/\n/g, '<br>');

        const modeClass = data.modo === 'online' ? 'online' : 'local';
        const modeText  = data.modo === 'online' ? '🟢 IA Online' : '🟡 Análisis Local';

        iaMsg.innerHTML = `
            <div class="bubble ${modeClass}">
                ${contenido}
                <div style="margin-top:8px;">
                    <span style="font-size:10px; color:#9caea4;">${modeText}</span>
                </div>
            </div>`;
        chat.appendChild(iaMsg);
        chat.scrollTop = chat.scrollHeight;
    })
    .catch(() => {
        document.getElementById('iaLoading')?.remove();
        const err = document.createElement('div');
        err.className = 'msg-ia';
        err.innerHTML = `<div class="bubble" style="border-color:rgba(231,76,60,0.30);background:rgba(231,76,60,0.08);color:#fca5a5;">⛔ Error de conexión</div>`;
        chat.appendChild(err);
    })
    .finally(() => { btn.disabled = false; });
}

document.getElementById('consultaIA').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') enviarConsulta();
});
</script>
@endsection
