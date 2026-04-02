@extends('layouts.app')
@section('title', 'Punto de Venta')
@section('page-title', '🛒 Punto de Venta')
@section('styles')
<style>
.pos-container { display: grid; grid-template-columns: 1fr 380px; gap: 20px; height: calc(100vh - 140px); }
.pos-left { overflow-y: auto; }
.pos-right { display: flex; flex-direction: column; }
.product-card { border: 2px solid #e8f5e8; border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.2s; background: white; }
.product-card:hover, .product-card.selected { border-color: var(--nc-green-400); background: var(--nc-green-50); transform: scale(1.01); }
.product-card .p-nombre { font-weight: 600; font-size: 13px; color: #1a2e1a; }
.product-card .p-precio { font-size: 16px; font-weight: 700; color: var(--nc-green-700); }
.product-card .p-stock { font-size: 11px; color: #6b7280; }
.cart-item { background: var(--nc-green-50); border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; }
.cart-item .item-name { font-size: 13px; font-weight: 600; color: #1a2e1a; }
.cart-item .item-price { font-size: 12px; color: var(--nc-green-700); }
.qty-btn { width: 28px; height: 28px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn.minus { background: #ffe4e6; color: #dc2626; }
.qty-btn.plus { background: #dcfce7; color: #16a34a; }
.qty-display { font-size: 15px; font-weight: 700; min-width: 28px; text-align: center; }
.search-bar { border-radius: 12px; border: 2px solid #d1fae5; padding: 10px 16px; font-size: 14px; width: 100%; }
.search-bar:focus { outline: none; border-color: var(--nc-green-400); box-shadow: 0 0 0 3px rgba(74,222,128,0.15); }
.frecuente-btn { border: 2px solid var(--nc-green-200); background: white; border-radius: 10px; padding: 8px 14px; font-size: 13px; font-weight: 600; color: var(--nc-green-700); cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.frecuente-btn:hover { border-color: var(--nc-green-400); background: var(--nc-green-50); }
.total-section { background: var(--nc-sidebar-bg); border-radius: 16px; padding: 16px; color: white; }
.total-label { font-size: 12px; color: rgba(255,255,255,0.6); }
.total-value { font-size: 22px; font-weight: 700; color: var(--nc-green-400); }
@media(max-width: 1024px) { .pos-container { grid-template-columns: 1fr; } .pos-right { max-height: 60vh; } }
</style>
@endsection

@section('content')
@if(!$cajaActiva)
<div class="alert alert-warning border-warning mb-4 d-flex align-items-center gap-3" style="border-radius:14px; border:2px solid #fde68a; background:#fef9c3;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:24px; color:#d97706;"></i>
    <div>
        <strong>No hay caja abierta.</strong> Para registrar ventas, primero debes abrir la caja.
        <a href="{{ route('caja.index') }}" class="btn btn-sm btn-warning ms-3" style="border-radius:8px;">Abrir caja</a>
    </div>
</div>
@endif

<div class="pos-container">
    <!-- Catálogo izquierdo -->
    <div class="pos-left">
        <!-- Búsqueda -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Buscar producto por nombre...">
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

                <!-- Cordial especial -->
                <button class="frecuente-btn" style="background: #fdf2f8; border-color: #f9a8d4; color: #9d174d;" data-bs-toggle="modal" data-bs-target="#cordialModal">
                    🍹 Venta Cordial
                </button>
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
                            <span style="color:#dc2626;">Sin stock</span>
                        @elseif($p->tieneStockBajo())
                            <span class="badge-stock-low px-2 rounded-pill" style="font-size:10px;">{{ $p->stock }} restantes</span>
                        @else
                            <span style="color: var(--nc-green-600);">{{ $p->stock }} en stock</span>
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
                <input type="text" id="clienteDni" class="nc-input flex-grow-1" placeholder="DNI del cliente" maxlength="15"
                    style="border-radius:10px; border:1.5px solid #d1fae5; font-size:13px; padding:8px 12px;">
                <button class="btn btn-sm btn-naturacor-outline" onclick="buscarCliente()">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div id="clienteInfo" class="mt-2" style="display:none;">
                <div style="background: var(--nc-green-50); border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                    <span id="clienteNombre" style="font-weight:600; color: var(--nc-green-700);"></span>
                    <span id="clienteFidelizacion" class="ms-2"></span>
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
                <span style="color:white; font-weight:600; font-size:14px;">S/ <span id="subtotalVal">0.00</span></span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="total-label">IGV incluido (18%)</span>
                <span style="color:rgba(255,255,255,0.7); font-size:14px;">S/ <span id="igvVal">0.00</span></span>
            </div>
            <hr style="border-color: rgba(255,255,255,0.15); margin: 8px 0;">
            <div class="d-flex justify-content-between">
                <span class="total-label" style="font-size:14px;">TOTAL</span>
                <span class="total-value">S/ <span id="totalVal">0.00</span></span>
            </div>
        </div>

        <!-- Método de pago -->
        <div class="mb-3">
            <div class="text-muted mb-2" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.7px;">💳 Método de pago</div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['efectivo'=>'💵 Efectivo','yape'=>'🟣 Yape','plin'=>'🔵 Plin','otro'=>'💳 Otro'] as $k => $label)
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

<!-- Modal Cordial -->
<div class="modal fade" id="cordialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #fdf2f8, #fce7f3); border-radius:20px 20px 0 0; border-bottom: 1px solid #f9a8d4;">
                <h5 class="modal-title" style="color: #9d174d;">🍹 Venta de Cordial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:13px;">Tipo de venta</label>
                    <select id="cordialTipo" class="form-select nc-input" onchange="updateCordialPrecio()">
                        @foreach(\App\Models\CordialVenta::$labels as $k => $label)
                        <option value="{{ $k }}" data-precio="{{ \App\Models\CordialVenta::$precios[$k] }}">{{ $label }} — S/{{ \App\Models\CordialVenta::$precios[$k] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:13px;">Cantidad</label>
                    <input type="number" id="cordialCantidad" class="nc-input form-control" value="1" min="1">
                </div>
                <div id="invitadoSection" style="display:none;">
                    <div class="alert" style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; font-size: 13px;">
                        <strong>⚠️ Invitado:</strong> Registrar responsable y motivo.
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-600" style="font-size:13px;">Motivo</label>
                        <input type="text" id="cordialMotivo" class="nc-input form-control" placeholder="Ej: degustación, cortesía...">
                    </div>
                </div>
                <div class="text-center mt-3">
                    <span class="kpi-value">S/ <span id="cordialPrecioDisplay">3.00</span></span>
                    <div class="text-muted" style="font-size:12px;">Total a cobrar</div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f9a8d4;">
                <button class="btn btn-naturacor w-100 py-2" onclick="addCordialToCart()" data-bs-dismiss="modal" style="border-radius:10px; font-weight:600;">
                    Agregar al carrito
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let cart = [];
let cordialItems = [];

// Buscar producto por texto
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.producto-item').forEach(el => {
        el.style.display = el.dataset.nombre.includes(q) ? '' : 'none';
    });
});

function addToCart(id, nombre, precio, stock) {
    const existing = cart.find(i => i.id == id && !i.esCordial);
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
    const empty = document.getElementById('cartEmpty');

    // Eliminar solo los items (no el div de vacío)
    Array.from(container.children).forEach(child => {
        if (child.id !== 'cartEmpty') child.remove();
    });

    if (cart.length === 0 && cordialItems.length === 0) {
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
                <button onclick="removeFromCart(${item.id})" style="background:none;border:none;color:#dc2626;padding:0;font-size:16px;cursor:pointer;"><i class="bi bi-x-circle"></i></button>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
                <div class="d-flex align-items-center gap-2">
                    <button class="qty-btn minus" onclick="changeQty(${item.id}, -1)">−</button>
                    <span class="qty-display">${item.cantidad}</span>
                    <button class="qty-btn plus" onclick="changeQty(${item.id}, +1)">+</button>
                </div>
                <div class="text-end">
                    <div class="item-price">S/ ${item.precio.toFixed(2)}</div>
                    <div style="font-size:13px; font-weight:700; color:#1a2e1a;">= S/ ${item.subtotal.toFixed(2)}</div>
                </div>
            </div>`;
        container.appendChild(div);
    });

    cordialItems.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.style.background = '#fdf2f8';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="item-name" style="color:#9d174d;">🍹 ${item.label}</div>
                <button onclick="removeCordial(${idx})" style="background:none;border:none;color:#dc2626;padding:0;font-size:16px;cursor:pointer;"><i class="bi bi-x-circle"></i></button>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <span style="font-size:12px; color:#6b7280;">x${item.cantidad}</span>
                <span style="font-size:13px; font-weight:700; color:#9d174d;">S/ ${(item.precio * item.cantidad).toFixed(2)}</span>
            </div>`;
        container.appendChild(div);
    });

    const total = cart.reduce((s, i) => s + i.subtotal, 0) + cordialItems.reduce((s, i) => s + i.precio * i.cantidad, 0);
    updateTotals(total);
}


function updateTotals(total) {
    // En Perú los precios ya INCLUYEN IGV → se extrae (no se suma)
    const igv = total * 18 / 118;
    const base = total - igv;
    document.getElementById('subtotalVal').textContent = base.toFixed(2);
    document.getElementById('igvVal').textContent = igv.toFixed(2);
    document.getElementById('totalVal').textContent = total.toFixed(2);
}

function selectMetodo(btn) {
    document.querySelectorAll('.metodo-btn').forEach(b => { b.classList.remove('btn-naturacor'); b.classList.add('btn-naturacor-outline'); });
    btn.classList.remove('btn-naturacor-outline'); btn.classList.add('btn-naturacor');
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
                const monto = parseFloat(data.cliente.acumulado_naturales);
                document.getElementById('clienteFidelizacion').textContent = monto > 0 ? `💚 Acumulado: S/${monto.toFixed(2)}` : '';
                document.getElementById('clienteInfo').style.display = '';
            } else {
                showToast('Cliente no encontrado. Puedes registrarlo en la sección de clientes.', 'warning');
                document.getElementById('clienteId').value = '';
                document.getElementById('clienteInfo').style.display = 'none';
            }
        });
}

function updateCordialPrecio() {
    const sel = document.getElementById('cordialTipo');
    const precio = parseFloat(sel.options[sel.selectedIndex].dataset.precio);
    const cant = parseInt(document.getElementById('cordialCantidad').value) || 1;
    document.getElementById('cordialPrecioDisplay').textContent = (precio * cant).toFixed(2);
    document.getElementById('invitadoSection').style.display = sel.value === 'invitado' ? '' : 'none';
}

function addCordialToCart() {
    const sel = document.getElementById('cordialTipo');
    const tipo = sel.value;
    const precio = parseFloat(sel.options[sel.selectedIndex].dataset.precio);
    const cantidad = parseInt(document.getElementById('cordialCantidad').value) || 1;
    const motivo = document.getElementById('cordialMotivo').value;
    const label = @json(\App\Models\CordialVenta::$labels)[tipo];
    cordialItems.push({ tipo, precio, cantidad, motivo, label });
    renderCart();
}

function removeCordial(idx) { cordialItems.splice(idx, 1); renderCart(); }

function confirmarVenta() {
    if (cart.length === 0 && cordialItems.length === 0) { showToast('Agrega productos al carrito', 'warning'); return; }

    const btn = document.querySelector('[onclick="confirmarVenta()"]');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

    const payload = {
        items: cart.map(i => ({ producto_id: i.id, cantidad: i.cantidad, descuento: i.descuento })),
        metodo_pago: document.getElementById('metodoPago').value,
        cliente_id: document.getElementById('clienteId').value || null,
        cordial: cordialItems.map(c => ({ tipo: c.tipo, cantidad: c.cantidad, motivo_invitado: c.motivo })),
        _token: csrfToken
    };

    fetch('/ventas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.innerHTML = origHtml;
        if (data.success) {
            cart = []; cordialItems = []; renderCart();
            document.getElementById('clienteId').value = '';
            document.getElementById('clienteDni').value = '';
            document.getElementById('clienteInfo').style.display = 'none';
            showToast('¡Venta registrada! Boleta: ' + data.numero_boleta, 'success');
            setTimeout(() => window.open('/boletas/' + data.venta_id, '_blank'), 800);
        } else {
            showToast(data.message || 'Error al procesar la venta', 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false; btn.innerHTML = origHtml;
        showToast('Error de red. Verifica tu conexión.', 'danger');
        console.error('confirmarVenta error:', err);
    });
}


function showToast(msg, type='success') {
    const d = document.createElement('div');
    const colors = { success: '#dcfce7', warning: '#fef3c7', danger: '#ffe4e6' };
    d.style.cssText = `position:fixed;bottom:20px;right:20px;background:${colors[type]};padding:14px 20px;border-radius:12px;font-weight:500;font-size:14px;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,0.15);max-width:300px;`;
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3500);
}

document.getElementById('cordialCantidad').addEventListener('input', updateCordialPrecio);
document.getElementById('clienteDni').addEventListener('keydown', function(e) { if(e.key === 'Enter') buscarCliente(); });
</script>
@endsection
