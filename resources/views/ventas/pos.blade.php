@extends('layouts.app')
@section('title', 'Punto de Venta')
@section('page-title', '🛒 Punto de Venta')
@section('styles')
<style>
/* ── LAYOUT PRINCIPAL 50/50 ── */
.pos-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    height: calc(100vh - 140px);
    min-height: 600px;
}
.pos-left  { overflow-y: auto; padding-right: 4px; }
.pos-right {
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

/* ── ZONA INFERIOR DERECHA: SUGERENCIAS + CARRITO 50/50 ── */
.pos-right-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    flex: 1;
    min-height: 0;
}
.pos-reco-col {
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.pos-cart-col {
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* ── TARJETA DE PRODUCTO ── */
.product-card {
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s;
    background: rgba(7,26,16,0.45);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.product-card:hover,
.product-card.selected {
    border-color: rgba(40,199,111,0.50);
    background: rgba(40,199,111,0.10);
    transform: scale(1.02);
    box-shadow: 0 0 16px rgba(40,199,111,0.15);
}
.product-card .p-nombre { font-weight: 600; font-size: 13px; color: #ffffff; }
.product-card .p-precio { font-size: 16px; font-weight: 700; color: var(--neon); }
.product-card .p-stock  { font-size: 11px; color: #9caea4; }

/* ── BOTONES FRECUENTES ── */
.frecuente-btn {
    border: 1px solid rgba(40,199,111,0.35);
    background: rgba(40,199,111,0.08);
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #86efac;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
    font-family: 'Sora', sans-serif;
}
.frecuente-btn:hover {
    border-color: var(--neon);
    background: rgba(40,199,111,0.18);
    color: #ffffff;
    box-shadow: 0 0 12px rgba(40,199,111,0.25);
}

/* ── ITEM CARRITO ── */
.cart-item {
    background: rgba(40,199,111,0.07);
    border: 1px solid rgba(40,199,111,0.15);
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 8px;
}
.cart-item .item-name  { font-size: 13px; font-weight: 600; color: #ffffff; }
.cart-item .item-price { font-size: 12px; color: var(--neon); }
.cart-item .item-total { font-size: 13px; font-weight: 700; color: #ffffff; }

/* ── BOTONES QTY ── */
.qty-btn {
    width: 28px; height: 28px;
    border-radius: 8px; border: none;
    font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.qty-btn.minus { background: rgba(231,76,60,0.20); color: #e74c3c; }
.qty-btn.minus:hover { background: rgba(231,76,60,0.35); }
.qty-btn.plus  { background: rgba(40,199,111,0.20); color: #28c76f; }
.qty-btn.plus:hover  { background: rgba(40,199,111,0.35); }
.qty-display { font-size: 15px; font-weight: 700; min-width: 28px; text-align: center; color: #ffffff; }

/* ── SEARCH BAR ── */
.search-bar {
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.10);
    padding: 10px 16px;
    font-size: 14px;
    width: 100%;
    background: rgba(0,0,0,0.25);
    color: #ffffff;
    font-family: 'Sora', sans-serif;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-bar::placeholder { color: #9caea4; }
.search-bar:focus {
    outline: none;
    border-color: #28c76f;
    box-shadow: 0 0 0 3px rgba(40,199,111,0.12), 0 0 10px rgba(40,199,111,0.20);
    background: rgba(0,0,0,0.35);
}

/* ── TOTAL SECTION ── */
.total-section {
    background: rgba(7,26,16,0.70);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(40,199,111,0.18);
    border-radius: 14px;
    padding: 16px;
}
.total-label { font-size: 12px; color: rgba(255,255,255,0.55); }
.total-value { font-size: 24px; font-weight: 700; color: var(--neon); }

/* ── AUTOCOMPLETADO CLIENTE ── */
.cliente-autocomplete-wrapper { position: relative; }
.cliente-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0; right: 0;
    background: rgba(7,26,16,0.97);
    border: 1px solid rgba(40,199,111,0.30);
    border-radius: 12px;
    z-index: 1000;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.50);
    backdrop-filter: blur(16px);
    display: none;
}
.cliente-dropdown.show { display: block; }
.cliente-option {
    padding: 10px 14px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.cliente-option:last-child { border-bottom: none; }
.cliente-option:hover { background: rgba(40,199,111,0.12); }
.cliente-option .opt-nombre { font-size: 13px; font-weight: 600; color: #ffffff; }
.cliente-option .opt-dni    { font-size: 11px; color: #9caea4; }
.cliente-option .opt-acum   { font-size: 11px; color: #86efac; }

/* ── PANEL SUGERENCIAS IA ── */
.reco-panel {
    background: rgba(129,140,248,0.07);
    border: 1px solid rgba(129,140,248,0.22);
    border-radius: 12px;
    padding: 12px;
}
.reco-item {
    background: rgba(129,140,248,0.08);
    border: 1px solid rgba(129,140,248,0.18);
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 10px;
}
.reco-item:last-child { margin-bottom: 0; }
.reco-item:hover {
    border-color: rgba(129,140,248,0.50);
    background: rgba(129,140,248,0.15);
    transform: translateX(2px);
}
.reco-item .reco-nombre  { font-size: 13px; font-weight: 600; color: #ffffff; line-height: 1.3; }
.reco-item .reco-razon   { font-size: 12px; color: rgba(255,255,255,0.80); margin-top: 3px; }
.reco-item .reco-precio  { font-size: 14px; font-weight: 700; color: #a5b4fc; white-space: nowrap; }
.reco-item .reco-score   { font-size: 11px; color: rgba(165,180,252,0.90); }
.reco-badge {
    display: inline-block;
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 20px;
    font-weight: 700;
    margin-top: 4px;
}
.reco-badge-salud     { background: rgba(56,189,248,0.25); color: #7dd3fc; border: 1px solid rgba(56,189,248,0.50); }
.reco-badge-tendencia { background: rgba(134,239,172,0.25); color: #86efac; border: 1px solid rgba(134,239,172,0.50); }
.reco-badge-crossell  { background: rgba(251,191,36,0.20); color: #fcd34d; border: 1px solid rgba(251,191,36,0.55); }
.reco-add-btn {
    width: 32px; height: 32px; min-width: 32px;
    border-radius: 8px;
    border: 1px solid rgba(129,140,248,0.35);
    background: rgba(129,140,248,0.12);
    color: #818cf8;
    font-size: 18px;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    flex-shrink: 0;
}
.reco-add-btn:hover {
    background: rgba(129,140,248,0.30);
    border-color: rgba(129,140,248,0.60);
    color: #ffffff;
}

/* ── MODAL PADECIMIENTOS ── */
.padecimiento-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.70);
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Sora', sans-serif;
}
.padecimiento-chip:hover {
    border-color: rgba(56,189,248,0.45);
    background: rgba(56,189,248,0.10);
    color: #38bdf8;
}
.padecimiento-chip.selected {
    border-color: rgba(56,189,248,0.60);
    background: rgba(56,189,248,0.18);
    color: #38bdf8;
    font-weight: 600;
}
.modal-naturacor .modal-content {
    background: rgba(7,26,16,0.97);
    border: 1px solid rgba(40,199,111,0.25);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    color: #ffffff;
}
.modal-naturacor .modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 20px 24px 16px;
}
.modal-naturacor .modal-footer {
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 16px 24px 20px;
}

@media(max-width: 1100px) {
    .pos-container { grid-template-columns: 1fr; }
    .pos-right { max-height: none; }
    .pos-right-body { grid-template-columns: 1fr; }
}
@media(max-width: 768px) {
    .pos-right-body { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
@if(!$cajaActiva)
<div class="alert alert-warning alert-dismissible mb-4 d-flex align-items-center gap-3"
     style="border-radius:14px; background:rgba(243,156,18,0.12); border:1px solid rgba(243,156,18,0.35); color:#f39c12;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:22px;"></i>
    <div>
        <strong style="color:#ffffff;">No hay caja abierta.</strong>
        <span style="color:rgba(255,255,255,0.75);"> Para registrar ventas, primero debes abrir la caja.</span>
        <a href="{{ route('caja.index') }}" class="btn btn-warning btn-sm ms-3" style="border-radius:8px;">Abrir caja</a>
    </div>
</div>
@endif

<div class="pos-container">

    {{-- ══════════════════════════════════════════════
         LADO IZQUIERDO — Catálogo de productos
    ══════════════════════════════════════════════ --}}
    <div class="pos-left">

        <!-- Búsqueda + Escáner -->
        <div class="mb-3">
            <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="search-bar flex-grow-1"
                       placeholder="🔍 Buscar producto por nombre...">
                <input type="text" id="barcodeInput" class="search-bar"
                       style="max-width:200px; border-color:rgba(129,140,248,0.40);"
                       placeholder="📷 Código barras" autofocus>
            </div>
            <small class="text-muted" style="font-size:11px;">
                <i class="bi bi-upc-scan me-1"></i>Escanea un código o escribe y presiona Enter
            </small>
        </div>

        <!-- Botones frecuentes -->
        @if($frecuentes->count())
        <div class="mb-3">
            <div class="text-muted mb-2" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">⚡ Frecuentes</div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($frecuentes as $p)
                <button class="frecuente-btn"
                        onclick="addToCart({{ $p->id }}, '{{ addslashes($p->nombre) }}', {{ $p->precio }}, {{ $p->stock }})">
                    {{ $p->nombre }} — S/{{ number_format($p->precio, 2) }}
                </button>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Catálogo -->
        <div class="text-muted mb-2" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">
            📋 Productos naturales
        </div>
        <div class="row g-2" id="catalogGrid">
            @foreach($productos->where('tipo', 'natural') as $p)
            <div class="col-6 col-md-4 col-xl-3 producto-item" data-nombre="{{ strtolower($p->nombre) }}">
                <div class="product-card {{ $p->stock == 0 ? 'opacity-50' : '' }}"
                    onclick="{{ $p->stock > 0 ? "addToCart({$p->id}, '".addslashes($p->nombre)."', {$p->precio}, {$p->stock})" : '' }}">
                    <div class="p-nombre">{{ $p->nombre }}</div>
                    <div class="p-precio mt-1">S/ {{ number_format($p->precio, 2) }}</div>
                    <div class="p-stock mt-1">
                        @if($p->stock == 0)
                            <span style="color:#e74c3c;">Sin stock</span>
                        @elseif($p->tieneStockBajo())
                            <span class="badge-stock-low" style="font-size:10px;">{{ $p->stock }} restantes</span>
                        @else
                            <span style="color:var(--neon);">{{ $p->stock }} en stock</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         LADO DERECHO — Carrito + Cliente + Sugerencias
    ══════════════════════════════════════════════ --}}
    <div class="pos-right nc-card" style="padding:16px; gap:0;">

        <!-- ── CLIENTE CON AUTOCOMPLETADO ── -->
        <div class="mb-3">
            <div class="text-muted mb-1"
                 style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">
                👤 Cliente
            </div>
            <div class="cliente-autocomplete-wrapper">
                <div class="d-flex gap-2">
                    <input type="text" id="clienteBusqueda" class="form-control flex-grow-1"
                           placeholder="DNI o nombre del cliente..."
                           autocomplete="off"
                           style="background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.12); color:#fff; border-radius:10px; font-size:13px;">
                    <button class="btn btn-naturacor-outline btn-sm px-3" onclick="limpiarCliente()" title="Limpiar">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div id="clienteDropdown" class="cliente-dropdown"></div>
            </div>
            <div id="clienteInfo" style="display:none; margin-top:8px;">
                <div style="background:rgba(40,199,111,0.10); border:1px solid rgba(40,199,111,0.20); border-radius:8px; padding:8px 12px; font-size:13px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span id="clienteNombre" style="font-weight:600; color:var(--neon);"></span>
                            <span id="clienteDniDisplay" class="ms-2" style="color:#9caea4; font-size:11px;"></span>
                        </div>
                        <span id="clienteFidelizacion" style="color:#9caea4; font-size:11px;"></span>
                    </div>
                    <!-- Badge de padecimientos -->
                    <div id="clientePadecimientos" class="mt-1" style="display:none;">
                        <span style="font-size:10px; color:rgba(56,189,248,0.80);">
                            <i class="bi bi-heart-pulse me-1"></i>
                            <span id="padecimientosTexto"></span>
                        </span>
                    </div>
                </div>
            </div>
            <input type="hidden" id="clienteId" value="">
        </div>

        {{-- ── PANEL PADECIMIENTOS INLINE ── --}}
        <div id="panelPadecimientos" style="display:none;" class="mb-3">
            <div style="background:rgba(56,189,248,0.08); border:1px solid rgba(56,189,248,0.30); border-radius:12px; padding:14px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px; font-weight:700; color:#38bdf8;">
                        <i class="bi bi-heart-pulse me-1"></i>¿Qué padece este cliente?
                    </span>
                    <button type="button" onclick="cerrarPanelPadecimientos()" style="background:none;border:none;color:rgba(255,255,255,0.40);font-size:16px;cursor:pointer;padding:0;line-height:1;">✕</button>
                </div>
                <div id="clienteEnModalNombre" class="mb-2" style="font-size:12px; color:rgba(255,255,255,0.50);"></div>
                <div id="padecimientosChips" class="d-flex flex-wrap gap-2 mb-3"></div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="cerrarPanelPadecimientos()"
                            style="flex:1; padding:7px; border:1px solid rgba(255,255,255,0.15); background:none; color:rgba(255,255,255,0.50); border-radius:8px; font-size:12px; cursor:pointer; font-family:'Sora',sans-serif;">
                        Omitir
                    </button>
                    <button type="button" onclick="guardarPadecimientos()"
                            style="flex:2; padding:7px; background:rgba(40,199,111,0.20); border:1px solid rgba(40,199,111,0.50); color:#86efac; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; font-family:'Sora',sans-serif;">
                        <i class="bi bi-check2 me-1"></i>Guardar y ver sugerencias
                    </button>
                </div>
            </div>
        </div>

        {{-- ── ZONA 2 COLUMNAS: SUGERENCIAS + CARRITO ── --}}
        <div class="pos-right-body">

            {{-- COLUMNA IZQUIERDA: SUGERENCIAS IA --}}
            <div class="pos-reco-col">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px; color:rgba(255,255,255,0.40); margin-bottom:8px;">
                    🤖 Sugerencias IA
                </div>
                <div id="recoPanel" class="reco-panel" style="display:none; flex:1;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.7px; color:#818cf8;">
                            <i class="bi bi-cpu me-1"></i>Sugerencias IA
                        </span>
                        <button type="button" id="recoRefreshBtn"
                                style="display:none; font-size:11px; background:none; border:none; color:rgba(129,140,248,0.60); cursor:pointer; padding:0;">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </button>
                    </div>
                    <div id="recoLoading" style="display:none; text-align:center; padding:12px 0; color:rgba(255,255,255,0.40); font-size:12px;">
                        <div class="spinner-border spinner-border-sm me-2" style="color:#818cf8;"></div>
                        Analizando perfil de salud...
                    </div>
                    <div id="recoList"></div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: CARRITO --}}
            <div class="pos-cart-col">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px; color:rgba(255,255,255,0.40); margin-bottom:8px;">
                    🛒 Carrito
                </div>
                <div class="flex-grow-1 overflow-auto" id="cartItems" style="min-height:80px;">
                    <div class="text-center text-muted py-4" id="cartEmpty">
                        <i class="bi bi-cart" style="font-size:36px; opacity:0.3;"></i>
                        <p class="mt-2 small">Carrito vacío</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── TOTALES ── -->
        <div class="total-section mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="total-label">Subtotal</span>
                <span style="color:#ffffff; font-weight:600; font-size:14px;">S/ <span id="subtotalVal">0.00</span></span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="total-label">IGV Incluido (18%)</span>
                <span style="color:rgba(255,255,255,0.55); font-size:14px;">S/ <span id="igvVal">0.00</span></span>
            </div>
            <hr style="border-color:rgba(255,255,255,0.10); margin:8px 0;">
            <div class="d-flex justify-content-between">
                <span class="total-label" style="font-size:14px; font-weight:600; color:rgba(255,255,255,0.80);">TOTAL</span>
                <span class="total-value">S/ <span id="totalVal">0.00</span></span>
            </div>
        </div>

        <!-- ── MÉTODO DE PAGO ── -->
        <div class="mb-3">
            <div class="text-muted mb-2" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">
                💳 Método de pago
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['efectivo'=>'💵 Efectivo','yape'=>'💜 Yape','plin'=>'🔵 Plin','otro'=>'💳 Otro'] as $k => $label)
                <button class="btn btn-sm metodo-btn {{ $k=='efectivo' ? 'btn-naturacor' : 'btn-naturacor-outline' }}"
                        data-metodo="{{ $k }}" onclick="selectMetodo(this)">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <input type="hidden" id="metodoPago" value="efectivo">
        </div>

        <!-- ── CONFIRMAR ── -->
        <button class="btn btn-naturacor w-100 py-3"
                style="font-size:16px; font-weight:700; border-radius:12px;"
                onclick="confirmarVenta()" {{ !$cajaActiva ? 'disabled' : '' }}>
            <i class="bi bi-check-circle me-2"></i>CONFIRMAR VENTA
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     PANEL INLINE — Registro de padecimientos
     (sin modal Bootstrap, no bloquea el POS)
══════════════════════════════════════════════ --}}

@endsection

@section('scripts')
<script>
// csrfToken ya está declarado en layouts/app.blade.php
let cart = [];
let lastRecoSesion     = null;
let lastRecoClienteId  = null;
let cargandoReco       = false;
let recoAbortController = null;
let autocompleteTimer  = null;
let clienteSeleccionado = null; // objeto completo del cliente
let recoCartTimer      = null;  // debounce del refetch al cambiar carrito (Bloque 2 Fase B)

// ──────────────────────────────────────────
// BÚSQUEDA EN CATÁLOGO
// ──────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.producto-item').forEach(el => {
        el.style.display = el.dataset.nombre.includes(q) ? '' : 'none';
    });
});

// ──────────────────────────────────────────
// AUTOCOMPLETADO CLIENTE
// ──────────────────────────────────────────
const inputBusqueda = document.getElementById('clienteBusqueda');
const dropdown      = document.getElementById('clienteDropdown');

inputBusqueda.addEventListener('input', function() {
    const q = this.value.trim();
    clearTimeout(autocompleteTimer);
    if (q.length < 2) { dropdown.classList.remove('show'); dropdown.innerHTML = ''; return; }
    autocompleteTimer = setTimeout(() => fetchAutocompletar(q), 250);
});

inputBusqueda.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { dropdown.classList.remove('show'); }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.cliente-autocomplete-wrapper')) {
        dropdown.classList.remove('show');
    }
});

function fetchAutocompletar(q) {
    fetch(`/api/clientes/autocompletar?q=${encodeURIComponent(q)}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(lista => {
        dropdown.innerHTML = '';
        if (!lista.length) {
            dropdown.innerHTML = '<div style="padding:12px 14px; font-size:12px; color:rgba(255,255,255,0.35);">Sin resultados</div>';
            dropdown.classList.add('show');
            return;
        }
        lista.forEach(c => {
            const opt = document.createElement('div');
            opt.className = 'cliente-option';
            const acumText = c.acumulado > 0 ? `🌿 S/${c.acumulado.toFixed(2)}/500` : '';
            opt.innerHTML = `
                <div>
                    <div class="opt-nombre">${escapeHtml(c.nombre)}</div>
                    <div class="opt-dni">DNI: ${escapeHtml(c.dni)}</div>
                </div>
                <div class="opt-acum">${acumText}</div>`;
            opt.addEventListener('click', () => seleccionarCliente(c));
            dropdown.appendChild(opt);
        });
        dropdown.classList.add('show');
    })
    .catch(() => { dropdown.classList.remove('show'); });
}

function seleccionarCliente(c) {
    clienteSeleccionado = c;
    document.getElementById('clienteId').value = c.id;
    inputBusqueda.value = `${c.nombre} (${c.dni})`;
    dropdown.classList.remove('show');
    dropdown.innerHTML = '';

    document.getElementById('clienteNombre').textContent = c.nombre;
    document.getElementById('clienteDniDisplay').textContent = `DNI: ${c.dni}`;
    document.getElementById('clienteFidelizacion').textContent =
        c.acumulado > 0 ? `🌿 Acum: S/${c.acumulado.toFixed(2)}/500` : '';
    document.getElementById('clienteInfo').style.display = '';

    // Cargar padecimientos del cliente y luego recomendaciones
    cargarPadecimientosCliente(c.id);
}

function limpiarCliente() {
    clienteSeleccionado = null;
    document.getElementById('clienteId').value = '';
    inputBusqueda.value = '';
    document.getElementById('clienteInfo').style.display = 'none';
    document.getElementById('clientePadecimientos').style.display = 'none';
    limpiarRecomendacionesPos();
    dropdown.classList.remove('show');
}

// ──────────────────────────────────────────
// PADECIMIENTOS — carga y modal
// ──────────────────────────────────────────
let padecimientosCliente = []; // ids seleccionados
let enfermedadesDisponibles = []; // lista del recetario

function cargarPadecimientosCliente(clienteId) {
    fetch(`/api/clientes/${clienteId}/padecimientos`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.ok ? r.json() : { padecimientos: [], enfermedades: [] })
    .then(data => {
        enfermedadesDisponibles = data.enfermedades || [];
        padecimientosCliente    = data.padecimientos || [];
        mostrarBadgePadecimientos();

        if (padecimientosCliente.length === 0 && enfermedadesDisponibles.length > 0) {
            // Pequeño delay para que el dropdown cierre primero
            setTimeout(() => abrirModalPadecimientos(), 200);
        } else {
            cargarRecomendacionesPos(clienteId, false);
        }
    })
    .catch(() => {
        cargarRecomendacionesPos(clienteId, false);
    });
}

function mostrarBadgePadecimientos() {
    const cont = document.getElementById('clientePadecimientos');
    const txt  = document.getElementById('padecimientosTexto');
    if (padecimientosCliente.length === 0) { cont.style.display = 'none'; return; }
    const nombres = padecimientosCliente.map(p => p.nombre || p).join(', ');
    txt.textContent = nombres;
    cont.style.display = '';
}

function abrirModalPadecimientos() {
    const chips = document.getElementById('padecimientosChips');
    chips.innerHTML = '';
    enfermedadesDisponibles.forEach(enf => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'padecimiento-chip';
        chip.dataset.id = enf.id;
        chip.textContent = enf.nombre;
        // Marcar los que ya tiene
        if (padecimientosCliente.some(p => p.id === enf.id)) {
            chip.classList.add('selected');
        }
        chip.addEventListener('click', function() { this.classList.toggle('selected'); });
        chips.appendChild(chip);
    });
    document.getElementById('clienteEnModalNombre').textContent =
        `Selecciona los padecimientos de ${clienteSeleccionado?.nombre || 'este cliente'}`;
    document.getElementById('panelPadecimientos').style.display = '';
}

function cerrarPanelPadecimientos() {
    document.getElementById('panelPadecimientos').style.display = 'none';
    const clienteId = document.getElementById('clienteId').value;
    if (clienteId) cargarRecomendacionesPos(clienteId, false);
}

function guardarPadecimientos() {
    const clienteId = document.getElementById('clienteId').value;
    if (!clienteId) return;
    const seleccionados = Array.from(
        document.querySelectorAll('.padecimiento-chip.selected')
    ).map(c => parseInt(c.dataset.id));

    document.getElementById('panelPadecimientos').style.display = 'none';

    fetch(`/api/clientes/${clienteId}/padecimientos`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ enfermedad_ids: seleccionados })
    })
    .then(r => r.json())
    .then(data => {
        padecimientosCliente = data.padecimientos || [];
        mostrarBadgePadecimientos();
        cargarRecomendacionesPos(clienteId, true);
        showToast('Perfil de salud guardado ✓', 'success');
    })
    .catch(() => {
        cargarRecomendacionesPos(clienteId, false);
    });
}

// ──────────────────────────────────────────
// RECOMENDACIONES IA
// ──────────────────────────────────────────
function limpiarRecomendacionesPos() {
    lastRecoSesion = null; lastRecoClienteId = null;
    const panel = document.getElementById('recoPanel');
    if (panel) panel.style.display = 'none';
    document.getElementById('recoList').innerHTML = '';
    document.getElementById('recoLoading').style.display = 'none';
    document.getElementById('recoRefreshBtn').style.display = 'none';
}

function registrarRecoEvento(productoId, accion) {
    if (!lastRecoSesion || !lastRecoClienteId) return;
    fetch('{{ route('api.recomendaciones.evento') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            reco_sesion_id: lastRecoSesion,
            cliente_id: lastRecoClienteId,
            producto_id: productoId,
            accion
        })
    }).catch(() => {});
}

function cargarRecomendacionesPos(clienteId, forzar) {
    const panel   = document.getElementById('recoPanel');
    const loading = document.getElementById('recoLoading');
    const list    = document.getElementById('recoList');
    const btnRef  = document.getElementById('recoRefreshBtn');
    if (!clienteId) { limpiarRecomendacionesPos(); return; }
    if (cargandoReco && !forzar) return;
    if (recoAbortController) recoAbortController.abort();
    recoAbortController = new AbortController();
    cargandoReco = true;

    panel.style.display = '';
    loading.style.display = '';
    list.innerHTML = '';
    btnRef.style.display = 'none';

    let url = `/api/recomendaciones/${clienteId}?limite=5`;
    if (forzar) url += '&refresh=1';
    // [Bloque 2 Fase B] enviar carrito como CSV para activar el componente colaborativo
    if (Array.isArray(cart) && cart.length > 0) {
        const ids = cart.map(i => i.id).filter(Boolean).join(',');
        if (ids) url += `&producto_ids=${encodeURIComponent(ids)}`;
    }

    fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        credentials: 'same-origin',
        signal: recoAbortController.signal
    })
    .then(r => { if (!r.ok) throw new Error('HTTP'); return r.json(); })
    .then(data => {
        loading.style.display = 'none';
        btnRef.style.display = '';
        lastRecoClienteId = String(clienteId);
        lastRecoSesion = data.meta?.reco_sesion_id ?? null;

        if (!data.items || data.items.length === 0) {
            lastRecoSesion = null;
            list.innerHTML = `
                <div style="text-align:center; padding:16px 8px; color:rgba(255,255,255,0.35); font-size:12px;">
                    <i class="bi bi-info-circle d-block mb-1" style="font-size:20px;"></i>
                    Sin sugerencias aún. Registra los padecimientos del cliente para personalizar.
                </div>`;
            return;
        }

        const frag = document.createDocumentFragment();
        data.items.forEach(it => {
            const p = it.producto;
            const scoreNum = it.score ? Math.round(it.score * 100) : null;

            // [Bloque 2 Fase B] Determinar la "fuente dominante" del item:
            //  1) Cross-sell (carrito) — el componente con peso semántico mayor cuando aplica.
            //  2) Perfil de salud.
            //  3) Tendencia local (default).
            const compCooc   = Number(it.componente_coocurrencia || 0);
            const compPerfil = Number(it.componente_perfil || 0);
            const esCrossSell = compCooc > 0 ||
                (it.razones && it.razones.some(r => r.includes('🛒') || r.toLowerCase().includes('llevaron')));
            const esSalud = !esCrossSell && (compPerfil > 0 || (it.razones && it.razones.some(r =>
                r.toLowerCase().includes('salud') ||
                r.toLowerCase().includes('padec') ||
                r.toLowerCase().includes('enferm') ||
                r.toLowerCase().includes('perfil')
            )));

            let badgeLabel, badgeClass;
            if (esCrossSell) {
                badgeLabel = '🛒 Cliente que llevó X también llevó esto';
                badgeClass = 'reco-badge-crossell';
            } else if (esSalud) {
                badgeLabel = '🩺 Perfil de salud';
                badgeClass = 'reco-badge-salud';
            } else {
                badgeLabel = '📈 Tendencia local';
                badgeClass = 'reco-badge-tendencia';
            }

            const wrap = document.createElement('div');
            wrap.className = 'reco-item';
            wrap.innerHTML = `
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="reco-nombre">${escapeHtml(p.nombre)}</div>
                    <div class="reco-razon">${escapeHtml((it.razones || ['Recomendado para este cliente'])[0])}</div>
                    <div class="mt-1">
                        <span class="reco-badge ${badgeClass}">${badgeLabel}</span>
                        ${scoreNum ? `<span class="reco-badge ms-1" style="background:rgba(255,255,255,0.10); color:rgba(255,255,255,0.80); border:1px solid rgba(255,255,255,0.20); font-weight:700;">${scoreNum}% afinidad</span>` : ''}
                    </div>
                </div>
                <div class="text-end" style="min-width:60px;">
                    <div class="reco-precio">S/${Number(p.precio).toFixed(2)}</div>
                </div>`;

            const addBtn = document.createElement('button');
            addBtn.className = 'reco-add-btn';
            addBtn.innerHTML = '+';
            addBtn.title = 'Agregar al carrito';
            addBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                registrarRecoEvento(p.id, 'clic');
                registrarRecoEvento(p.id, 'agregada');
                addToCart(p.id, p.nombre, parseFloat(p.precio), p.stock);
                showToast('Agregado: ' + p.nombre, 'success');
            });
            wrap.appendChild(addBtn);

            wrap.addEventListener('click', () => {
                registrarRecoEvento(p.id, 'clic');
                registrarRecoEvento(p.id, 'agregada');
                addToCart(p.id, p.nombre, parseFloat(p.precio), p.stock);
                showToast('Agregado: ' + p.nombre, 'success');
            });

            frag.appendChild(wrap);
        });
        list.appendChild(frag);
    })
    .catch(err => {
        if (err?.name === 'AbortError') return;
        loading.style.display = 'none';
        btnRef.style.display = '';
        list.innerHTML = '<p class="small text-danger mb-0 text-center py-2">No se pudieron cargar las sugerencias.</p>';
    })
    .finally(() => { cargandoReco = false; });
}

document.getElementById('recoRefreshBtn')?.addEventListener('click', function() {
    const id = document.getElementById('clienteId').value;
    if (id) cargarRecomendacionesPos(id, true);
});

// ──────────────────────────────────────────
// CARRITO
// ──────────────────────────────────────────
function addToCart(id, nombre, precio, stock) {
    const existing = cart.find(i => i.id == id);
    const itemNuevo = !existing;
    if (existing) {
        if (existing.cantidad >= stock) { showToast('Stock insuficiente', 'warning'); return; }
        existing.cantidad++;
        existing.subtotal = (existing.precio - existing.descuento) * existing.cantidad;
    } else {
        cart.push({ id, nombre, precio, stock, cantidad: 1, descuento: 0, subtotal: precio });
    }
    renderCart();
    // Solo refetch al CAMBIAR la composición del carrito (no al cambiar cantidades del mismo item)
    if (itemNuevo) refetchRecomendacionesPorCarrito();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
    refetchRecomendacionesPorCarrito();
}

function changeQty(id, delta) {
    const item = cart.find(i => i.id == id);
    if (!item) return;
    item.cantidad = Math.max(1, Math.min(item.cantidad + delta, item.stock));
    item.subtotal = (item.precio - item.descuento) * item.cantidad;
    renderCart();
    // No refetch: la cantidad no afecta al componente colaborativo
}

/**
 * [Bloque 2 Fase B] Re-pide recomendaciones al cambiar la composición del carrito.
 * Debounce 350 ms para evitar 5 requests si el usuario agrega rápido. Solo dispara
 * si hay cliente seleccionado (sin cliente, no hay recomendaciones).
 */
function refetchRecomendacionesPorCarrito() {
    const clienteId = document.getElementById('clienteId')?.value;
    if (!clienteId) return;
    if (recoCartTimer) clearTimeout(recoCartTimer);
    recoCartTimer = setTimeout(() => {
        cargarRecomendacionesPos(clienteId, false);
    }, 350);
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty     = document.getElementById('cartEmpty');
    Array.from(container.children).forEach(child => { if (child.id !== 'cartEmpty') child.remove(); });

    if (cart.length === 0) { empty.style.display = ''; updateTotals(0); return; }
    empty.style.display = 'none';

    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="item-name flex-grow-1 me-2">${escapeHtml(item.nombre)}</div>
                <button onclick="removeFromCart(${item.id})" style="background:none;border:none;color:#e74c3c;padding:0;font-size:16px;cursor:pointer;">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
                <div class="d-flex align-items-center gap-2">
                    <button class="qty-btn minus" onclick="changeQty(${item.id}, -1)">−</button>
                    <span class="qty-display">${item.cantidad}</span>
                    <button class="qty-btn plus" onclick="changeQty(${item.id}, +1)">+</button>
                </div>
                <div class="text-end">
                    <div class="item-price">S/ ${item.precio.toFixed(2)}</div>
                    <div class="item-total">= S/ ${item.subtotal.toFixed(2)}</div>
                </div>
            </div>`;
        container.appendChild(div);
    });

    updateTotals(cart.reduce((s, i) => s + i.subtotal, 0));
}

function updateTotals(total) {
    const igv  = total * 18 / 118;
    const base = total - igv;
    document.getElementById('subtotalVal').textContent = base.toFixed(2);
    document.getElementById('igvVal').textContent      = igv.toFixed(2);
    document.getElementById('totalVal').textContent    = total.toFixed(2);
}

function selectMetodo(btn) {
    document.querySelectorAll('.metodo-btn').forEach(b => {
        b.classList.remove('btn-naturacor'); b.classList.add('btn-naturacor-outline');
    });
    btn.classList.remove('btn-naturacor-outline');
    btn.classList.add('btn-naturacor');
    document.getElementById('metodoPago').value = btn.dataset.metodo;
}

// ──────────────────────────────────────────
// CONFIRMAR VENTA
// ──────────────────────────────────────────
function confirmarVenta() {
    if (cart.length === 0) { showToast('Agrega productos al carrito', 'warning'); return; }
    const btn      = document.querySelector('[onclick="confirmarVenta()"]');
    const origHtml = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

    fetch('/ventas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
            items: cart.map(i => ({ producto_id: i.id, cantidad: i.cantidad, descuento: i.descuento })),
            metodo_pago: document.getElementById('metodoPago').value,
            cliente_id:  document.getElementById('clienteId').value || null,
            _token: csrfToken
        })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.innerHTML = origHtml;
        if (data.success) {
            cart = []; renderCart(); limpiarCliente();
            showToast('¡Venta registrada! Boleta: ' + data.numero_boleta, 'success');
            if (data.premio_generado && data.canjes?.length > 0) {
                setTimeout(() => showPremioAlert(data.canjes.map(c => c.descripcion_premio).join(', ')), 500);
            }
            setTimeout(() => window.open('/boletas/' + data.venta_id, '_blank'), 1200);
        } else {
            showToast(data.message || 'Error al procesar la venta', 'danger');
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = origHtml; showToast('Error de red.', 'danger'); });
}

// ──────────────────────────────────────────
// ESCÁNER CÓDIGO DE BARRAS
// ──────────────────────────────────────────
let barcodeBuffer = '';
let barcodeTimer  = null;
const barcodeInput = document.getElementById('barcodeInput');

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const code = this.value.trim();
        if (code.length >= 3) buscarPorBarcode(code);
        this.value = '';
    }
});

document.addEventListener('keydown', function(e) {
    const active = document.activeElement;
    if (active && active.tagName === 'INPUT' && active.id !== 'barcodeInput') return;
    if (e.key === 'Enter' && barcodeBuffer.length >= 3) {
        e.preventDefault(); buscarPorBarcode(barcodeBuffer); barcodeBuffer = ''; return;
    }
    if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
        barcodeBuffer += e.key;
        clearTimeout(barcodeTimer);
        barcodeTimer = setTimeout(() => { barcodeBuffer = ''; }, 100);
    }
});

function buscarPorBarcode(codigo) {
    fetch(`/api/productos/barcode?codigo=${encodeURIComponent(codigo)}`, {
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.found) {
            const p = data.producto;
            addToCart(p.id, p.nombre, parseFloat(p.precio), p.stock);
            showToast(`✓ ${p.nombre} agregado`, 'success');
        } else {
            showToast(`⚠ Producto no encontrado: ${codigo}`, 'warning');
        }
        barcodeInput.value = ''; barcodeInput.focus();
    })
    .catch(() => showToast('Error al buscar producto por código', 'danger'));
}

// ──────────────────────────────────────────
// UTILIDADES
// ──────────────────────────────────────────
function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

function showToast(msg, type = 'success') {
    const colors = {
        success: { bg: 'rgba(40,199,111,0.15)',  border: 'rgba(40,199,111,0.35)',  color: '#86efac' },
        warning: { bg: 'rgba(243,156,18,0.15)',  border: 'rgba(243,156,18,0.35)',  color: '#fcd34d' },
        danger:  { bg: 'rgba(231,76,60,0.15)',   border: 'rgba(231,76,60,0.35)',   color: '#fca5a5' },
    };
    const c = colors[type] || colors.success;
    const d = document.createElement('div');
    d.style.cssText = `position:fixed;bottom:20px;right:20px;background:${c.bg};border:1px solid ${c.border};color:${c.color};padding:14px 20px;border-radius:12px;font-weight:500;font-size:14px;z-index:9999;box-shadow:0 4px 24px rgba(0,0,0,0.40);max-width:320px;backdrop-filter:blur(12px);font-family:'Sora',sans-serif;`;
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3500);
}

function showPremioAlert(premios) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.70);z-index:10000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);';
    overlay.innerHTML = `
        <div style="background:rgba(7,26,16,0.96);border:1px solid rgba(40,199,111,0.30);border-radius:20px;padding:32px 40px;text-align:center;max-width:400px;">
            <div style="font-size:56px;">🎁</div>
            <h3 style="color:#28c76f;font-weight:700;margin:12px 0 8px;">¡Premio de Fidelización!</h3>
            <p style="color:rgba(255,255,255,0.75);font-size:15px;">El cliente ha alcanzado S/500 en productos</p>
            <div style="background:rgba(40,199,111,0.12);border:1px solid rgba(40,199,111,0.25);border-radius:12px;padding:12px;margin:16px 0;font-weight:600;color:#86efac;font-size:15px;">
                🎉 ${premios}
            </div>
            <button onclick="this.closest('div').parentElement.remove()"
                    style="background:rgba(40,199,111,0.20);color:#28c76f;border:1px solid rgba(40,199,111,0.40);padding:10px 28px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;font-family:'Sora',sans-serif;">
                Entendido
            </button>
        </div>`;
    document.body.appendChild(overlay);
    setTimeout(() => { if (overlay.parentElement) overlay.remove(); }, 10000);
}
</script>
@endsection
