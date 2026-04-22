@extends('layouts.app')
@section('title', 'Punto de Venta')
@section('page-title', '🖥️ Punto de Venta')
@section('styles')
<style>
.pos-container {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    height: calc(100vh - 140px);
}
.pos-left  { overflow-y: auto; }
.pos-right { display: flex; flex-direction: column; }

/* ── TARJETA DE PRODUCTO ─────────────────────────────────── */
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
.product-card .p-nombre {
    font-weight: 600;
    font-size: 13px;
    color: #ffffff;
}
.product-card .p-precio {
    font-size: 16px;
    font-weight: 700;
    color: var(--neon);
}
.product-card .p-stock {
    font-size: 11px;
    color: #9caea4;
}

/* ── BOTONES FRECUENTES ──────────────────────────────────── */
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

/* ── ITEM CARRITO ────────────────────────────────────────── */
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

/* ── BOTONES QTY ─────────────────────────────────────────── */
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

/* ── SEARCH BAR POS ──────────────────────────────────────── */
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

/* ── TOTAL SECTION ───────────────────────────────────────── */
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

@media(max-width: 1024px) {
    .pos-container { grid-template-columns: 1fr; }
    .pos-right { max-height: 60vh; }
}
</style>
@endsection

@section('content')
@if(!$cajaActiva)
<div class="alert alert-warning alert-dismissible mb-4 d-flex align-items-center gap-3" style="border-radius:14px; background:rgba(243,156,18,0.12); border:1px solid rgba(243,156,18,0.35); color:#f39c12;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:22px;"></i>
    <div>
        <strong style="color:#ffffff;">No hay caja abierta.</strong>
        <span style="color:rgba(255,255,255,0.75);"> Para registrar ventas, primero debes abrir la caja.</span>
        <a href="{{ route('caja.index') }}" class="btn btn-warning btn-sm ms-3" style="border-radius:8px;">Abrir caja</a>
    </div>
</div>
@endif

<div class="pos-container">
    <!-- Catálogo izquierdo -->
    <div class="pos-left">
        <!-- Búsqueda + Escáner -->
        <div class="mb-3">
            <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="search-bar flex-grow-1" placeholder="🔍 Buscar producto por nombre...">
                <input type="text" id="barcodeInput" class="search-bar" style="max-width:220px; border-color:rgba(129,140,248,0.40);" placeholder="📷 Escáner código barras" autofocus>
            </div>
            <small class="text-muted" style="font-size:11px;"><i class="bi bi-upc-scan me-1"></i>Escanea un código de barras o escribe el código y presiona Enter</small>
        </div>

        <!-- Botones frecuentes -->
        @if($frecuentes->count())
        <div class="mb-3">
            <div class="text-muted mb-2" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">⚡ Frecuentes</div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($frecuentes as $p)
                <button class="frecuente-btn" onclick="addToCart({{ $p->id }}, '{{ addslashes($p->nombre) }}', {{ $p->precio }}, {{ $p->stock }})">
                    {{ $p->nombre }} — S/{{ number_format($p->precio, 2) }}
                </button>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Catálogo -->
        <div class="text-muted mb-2" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">📦 Productos naturales</div>
        <div class="row g-2" id="catalogGrid">
            @foreach($productos->where('tipo', 'natural') as $p)
            <div class="col-6 col-md-4 col-lg-3 producto-item" data-nombre="{{ strtolower($p->nombre) }}">
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

    <!-- Carrito derecho -->
    <div class="pos-right nc-card d-flex flex-column" style="padding:16px;">
        <!-- Cliente -->
        <div class="mb-3">
            <div class="text-muted mb-1" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">👤 Cliente</div>
            <div class="d-flex gap-2">
                <input type="text" id="clienteDni" class="form-control flex-grow-1" placeholder="DNI del cliente" maxlength="15">
                <button class="btn btn-naturacor-outline btn-sm" onclick="buscarCliente()">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div id="clienteInfo" class="mt-2" style="display:none;">
                <div style="background:rgba(40,199,111,0.10); border:1px solid rgba(40,199,111,0.20); border-radius:8px; padding:8px 12px; font-size:13px;">
                    <span id="clienteNombre" style="font-weight:600; color:var(--neon);"></span>
                    <span id="clienteFidelizacion" class="ms-2" style="color:#9caea4;"></span>
                </div>
            </div>
            <input type="hidden" id="clienteId" value="">
        </div>

        <!-- Items carrito -->
        <div class="flex-grow-1 overflow-auto mb-3" id="cartItems">
            <div class="text-center text-muted py-4" id="cartEmpty">
                <i class="bi bi-cart" style="font-size:36px; opacity:0.3;"></i>
                <p class="mt-2 small">Carrito vacío</p>
            </div>
        </div>

        <!-- Totales -->
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

        <!-- Método de pago -->
        <div class="mb-3">
            <div class="text-muted mb-2" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">💳 Método de pago</div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['efectivo'=>'💵 Efectivo','yape'=>'💜 Yape','plin'=>'🔵 Plin','otro'=>'💳 Otro'] as $k => $label)
                <button class="btn btn-sm metodo-btn {{ $k=='efectivo' ? 'btn-naturacor' : 'btn-naturacor-outline' }}" data-metodo="{{ $k }}" onclick="selectMetodo(this)">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <input type="hidden" id="metodoPago" value="efectivo">
        </div>

        <!-- Confirmar -->
        <button class="btn btn-naturacor w-100 py-3" style="font-size:16px; font-weight:700; border-radius:12px;" onclick="confirmarVenta()" {{ !$cajaActiva ? 'disabled' : '' }}>
            <i class="bi bi-check-circle me-2"></i>CONFIRMAR VENTA
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
let cart = [];

document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.producto-item').forEach(el => {
        el.style.display = el.dataset.nombre.includes(q) ? '' : 'none';
    });
});

function addToCart(id, nombre, precio, stock) {
    const existing = cart.find(i => i.id == id);
    if (existing) {
        if (existing.cantidad >= stock) { showToast('Stock insuficiente', 'warning'); return; }
        existing.cantidad++;
        existing.subtotal = (existing.precio - existing.descuento) * existing.cantidad;
    } else {
        cart.push({ id, nombre, precio, stock, cantidad: 1, descuento: 0, subtotal: precio });
    }
    renderCart();
}

function removeFromCart(id) { cart = cart.filter(i => i.id !== id); renderCart(); }

function changeQty(id, delta) {
    const item = cart.find(i => i.id == id);
    if (!item) return;
    item.cantidad = Math.max(1, Math.min(item.cantidad + delta, item.stock));
    item.subtotal = (item.precio - item.descuento) * item.cantidad;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty     = document.getElementById('cartEmpty');

    Array.from(container.children).forEach(child => {
        if (child.id !== 'cartEmpty') child.remove();
    });

    if (cart.length === 0) {
        empty.style.display = '';
        updateTotals(0);
        return;
    }
    empty.style.display = 'none';

    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="item-name flex-grow-1 me-2">${item.nombre}</div>
                <button onclick="removeFromCart(${item.id})" style="background:none;border:none;color:#e74c3c;padding:0;font-size:16px;cursor:pointer;"><i class="bi bi-x-circle"></i></button>
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

    const total = cart.reduce((s, i) => s + i.subtotal, 0);
    updateTotals(total);
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
        b.classList.remove('btn-naturacor');
        b.classList.add('btn-naturacor-outline');
    });
    btn.classList.remove('btn-naturacor-outline');
    btn.classList.add('btn-naturacor');
    document.getElementById('metodoPago').value = btn.dataset.metodo;
}

function buscarCliente() {
    const dni = document.getElementById('clienteDni').value.trim();
    if (!dni) return;
    fetch(`/api/clientes/dni?dni=${dni}`, { headers: {'X-CSRF-TOKEN': csrfToken} })
        .then(r => r.json()).then(data => {
            if (data.found) {
                document.getElementById('clienteId').value = data.cliente.id;
                document.getElementById('clienteNombre').textContent = data.cliente.nombre + ' ' + (data.cliente.apellido || '');
                const montoNat = parseFloat(data.cliente.acumulado_naturales);
                document.getElementById('clienteFidelizacion').textContent = montoNat > 0 ? `🌿 Acum: S/${montoNat.toFixed(2)}/500` : '';
                document.getElementById('clienteInfo').style.display = '';
            } else {
                showToast('Cliente no encontrado.', 'warning');
                document.getElementById('clienteId').value = '';
                document.getElementById('clienteInfo').style.display = 'none';
            }
        });
}

function confirmarVenta() {
    if (cart.length === 0) { showToast('Agrega productos al carrito', 'warning'); return; }

    const btn      = document.querySelector('[onclick="confirmarVenta()"]');
    const origHtml = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

    const payload = {
        items: cart.map(i => ({ producto_id: i.id, cantidad: i.cantidad, descuento: i.descuento })),
        metodo_pago: document.getElementById('metodoPago').value,
        cliente_id:  document.getElementById('clienteId').value || null,
        _token: csrfToken
    };

    fetch('/ventas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled  = false;
        btn.innerHTML = origHtml;
        if (data.success) {
            cart = []; renderCart();
            document.getElementById('clienteId').value  = '';
            document.getElementById('clienteDni').value = '';
            document.getElementById('clienteInfo').style.display = 'none';
            showToast('¡Venta registrada! Boleta: ' + data.numero_boleta, 'success');
            if (data.premio_generado && data.canjes?.length > 0) {
                setTimeout(() => showPremioAlert(data.canjes.map(c => c.descripcion_premio).join(', ')), 500);
            }
            setTimeout(() => window.open('/boletas/' + data.venta_id, '_blank'), 1200);
        } else {
            showToast(data.message || 'Error al procesar la venta', 'danger');
        }
    })
    .catch(() => {
        btn.disabled  = false;
        btn.innerHTML = origHtml;
        showToast('Error de red. Verifica tu conexión.', 'danger');
    });
}

function showToast(msg, type = 'success') {
    const colors = {
        success: { bg: 'rgba(40,199,111,0.15)',  border: 'rgba(40,199,111,0.35)',  color: '#86efac' },
        warning: { bg: 'rgba(243,156,18,0.15)',  border: 'rgba(243,156,18,0.35)',  color: '#fcd34d' },
        danger:  { bg: 'rgba(231,76,60,0.15)',   border: 'rgba(231,76,60,0.35)',   color: '#fca5a5' },
    };
    const c = colors[type] || colors.success;
    const d = document.createElement('div');
    d.style.cssText = `position:fixed;bottom:20px;right:20px;background:${c.bg};border:1px solid ${c.border};color:${c.color};padding:14px 20px;border-radius:12px;font-weight:500;font-size:14px;z-index:9999;box-shadow:0 4px 24px rgba(0,0,0,0.40);max-width:300px;backdrop-filter:blur(12px);font-family:'Sora',sans-serif;`;
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3500);
}

document.getElementById('clienteDni').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') buscarCliente();
});

// ===== ESCÁNER DE CÓDIGO DE BARRAS =====
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
        e.preventDefault();
        buscarPorBarcode(barcodeBuffer);
        barcodeBuffer = '';
        return;
    }
    if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
        barcodeBuffer += e.key;
        clearTimeout(barcodeTimer);
        barcodeTimer = setTimeout(() => { barcodeBuffer = ''; }, 100);
    }
});

function buscarPorBarcode(codigo) {
    fetch(`/api/productos/barcode?codigo=${encodeURIComponent(codigo)}`, {
        headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.found) {
            const p = data.producto;
            addToCart(p.id, p.nombre, parseFloat(p.precio), p.stock);
            showToast(`✔ ${p.nombre} agregado al carrito`, 'success');
        } else {
            showToast(`⛔ Producto no encontrado: ${codigo}`, 'warning');
        }
        barcodeInput.value = '';
        barcodeInput.focus();
    })
    .catch(() => showToast('Error al buscar producto por código', 'danger'));
}

function showPremioAlert(premios) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.70);z-index:10000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);';
    overlay.innerHTML = `
        <div style="background:rgba(7,26,16,0.96);border:1px solid rgba(40,199,111,0.30);border-radius:20px;padding:32px 40px;text-align:center;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.6),0 0 40px rgba(40,199,111,0.15);">
            <div style="font-size:56px;">🏆</div>
            <h3 style="color:#28c76f;font-weight:700;margin:12px 0 8px;">¡Premio de Fidelización!</h3>
            <p style="color:rgba(255,255,255,0.75);font-size:15px;">El cliente ha alcanzado S/500 en productos</p>
            <div style="background:rgba(40,199,111,0.12);border:1px solid rgba(40,199,111,0.25);border-radius:12px;padding:12px;margin:16px 0;font-weight:600;color:#86efac;font-size:15px;">
                🎁 ${premios}
            </div>
            <button onclick="this.closest('div').parentElement.remove()" style="background:rgba(40,199,111,0.20);color:#28c76f;border:1px solid rgba(40,199,111,0.40);padding:10px 28px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;font-family:'Sora',sans-serif;">Entendido</button>
        </div>`;
    document.body.appendChild(overlay);
    setTimeout(() => { if (overlay.parentElement) overlay.remove(); }, 10000);
}
</script>
@endsection
