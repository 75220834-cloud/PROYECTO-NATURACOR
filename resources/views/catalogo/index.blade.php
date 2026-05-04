@extends('layouts.catalogo')
@section('title', 'Catálogo de Productos Naturales')
@section('content')

{{-- ── HERO COMPACTO ──────────────────────────── --}}
<section class="hero fade-up" style="padding:50px 20px 30px;">
    <h1>🌿 NATURACOR</h1>
    <p style="margin-bottom:18px;">Productos naturales y cordiales artesanales para tu bienestar</p>
    <div class="badge-location">
        <i class="bi bi-geo-alt-fill"></i> Jauja, Junín — Perú
    </div>
</section>

{{-- ── CATÁLOGO PRINCIPAL ─────────────────────── --}}
<section class="container py-4" id="productos">
    <div class="text-center fade-up mb-4">
        <div class="section-title">Nuestros Productos: Excelencia de Jauja para tu Bienestar</div>
    </div>

    <div class="row g-4">
        {{-- ═══ SIDEBAR FILTROS ═══ --}}
        <div class="col-lg-3 col-md-4">
            <div class="filter-sidebar reveal">
                <form method="GET" action="{{ route('catalogo') }}" id="catalogoForm">
                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-search"></i> Buscar</div>
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Nombre del producto..." class="filter-input">
                    </div>

                    {{-- Sucursal --}}
                    @if($sucursales->count() > 1)
                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-shop"></i> Sucursal</div>
                        <label class="filter-check">
                            <input type="radio" name="sucursal" value="" {{ !$sucursalId ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>Todas</span>
                        </label>
                        @foreach($sucursales as $suc)
                        <label class="filter-check">
                            <input type="radio" name="sucursal" value="{{ $suc->id }}" {{ (int)$sucursalId === $suc->id ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>{{ $suc->nombre }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endif

                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-tag"></i> Categorías</div>
                        <label class="filter-check">
                            <input type="radio" name="tipo" value="" {{ !$tipo ? 'checked' : '' }} onchange="this.form.submit()"><span>Todos</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="tipo" value="natural" {{ $tipo === 'natural' ? 'checked' : '' }} onchange="this.form.submit()"><span>🌱 Naturales</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="tipo" value="cordial" {{ $tipo === 'cordial' ? 'checked' : '' }} onchange="this.form.submit()"><span>🧃 Cordiales</span>
                        </label>
                    </div>

                    @if($beneficios->count() > 0)
                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-heart-pulse"></i> Beneficios</div>
                        <label class="filter-check">
                            <input type="radio" name="beneficio" value="" {{ !$beneficio ? 'checked' : '' }} onchange="this.form.submit()"><span>Todos</span>
                        </label>
                        @foreach($beneficios as $b)
                        <label class="filter-check">
                            <input type="radio" name="beneficio" value="{{ $b->id }}" {{ (int)$beneficio === $b->id ? 'checked' : '' }} onchange="this.form.submit()"><span>{{ $b->nombre }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endif

                    <button type="submit" class="filter-btn"><i class="bi bi-funnel"></i> Filtrar</button>
                    @if($search || $tipo || $beneficio || $sucursalId)
                    <a href="{{ route('catalogo') }}" class="filter-clear"><i class="bi bi-x-circle"></i> Limpiar filtros</a>
                    @endif
                </form>

                {{-- QR Yape --}}
                <div style="margin-top:22px; padding-top:18px; border-top:1px solid rgba(191,255,0,0.15); text-align:center;">
                    <div class="filter-title" style="justify-content:center;"><i class="bi bi-qr-code"></i> Paga con Yape</div>
                    <img src="{{ asset('img/yape_qr.png') }}" alt="QR Yape NATURACOR" style="width:100%;max-width:180px;border-radius:12px;border:2px solid rgba(128,0,255,0.30);margin:8px auto;">
                    <p style="font-size:11px;color:var(--text-sec);margin-top:6px;">Escanea para pagar con Yape</p>
                </div>
            </div>
        </div>

        {{-- ═══ GRID DE PRODUCTOS ═══ --}}
        <div class="col-lg-9 col-md-8">
            @if($search || $tipo || $beneficio || $sucursalId)
            <div class="filter-active-bar reveal">
                <span>Mostrando <strong>{{ $productos->count() }}</strong> resultado(s)</span>
                @if($search) <span class="filter-tag">🔍 "{{ $search }}"</span> @endif
                @if($tipo) <span class="filter-tag">📦 {{ ucfirst($tipo) }}</span> @endif
                @if($beneficio)
                    @php $bNombre = $beneficios->firstWhere('id', (int)$beneficio)?->nombre @endphp
                    @if($bNombre) <span class="filter-tag">💊 {{ $bNombre }}</span> @endif
                @endif
                @if($sucursalId)
                    @php $sNombre = $sucursales->firstWhere('id', (int)$sucursalId)?->nombre @endphp
                    @if($sNombre) <span class="filter-tag">🏪 {{ $sNombre }}</span> @endif
                @endif
            </div>
            @endif

            @if($productos->count() > 0)
            <div class="row g-4">
                @foreach($productos as $producto)
                <div class="col-12 col-md-6 col-lg-4 reveal">
                    <div class="product-card-premium">
                        <div class="product-img-premium">
                            @if($url = producto_image_url($producto))
                                <img src="{{ $url }}" alt="{{ $producto->nombre }}" loading="lazy">
                            @else
                                <span class="emoji-placeholder" style="font-size:72px;">🌿</span>
                            @endif
                            <span class="badge-tipo {{ $producto->tipo }}">{{ ucfirst($producto->tipo) }}</span>
                        </div>
                        <div class="product-body-premium">
                            <div class="product-name-premium">{{ $producto->nombre }}</div>
                            {{-- Estrellas --}}
                            @php
                                $avgStars = $producto->valoracionesAprobadas->avg('estrellas') ?? 0;
                                $countVal = $producto->valoracionesAprobadas->count();
                            @endphp
                            <div style="margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                @for($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= round($avgStars) ? 'bi-star-fill' : 'bi-star' }}" style="color:#fbbf24;font-size:14px;"></i>
                                @endfor
                                <span style="font-size:11px;color:var(--text-sec);">({{ $countVal }})</span>
                                <button type="button" onclick="openReview({{ $producto->id }}, '{{ addslashes($producto->nombre) }}')" style="background:none;border:none;color:var(--bio-cyan);font-size:11px;cursor:pointer;padding:0;margin-left:auto;" title="Dejar reseña">
                                    <i class="bi bi-pencil-square"></i> Reseñar
                                </button>
                            </div>
                            <div class="product-desc-premium">{{ Str::limit($producto->descripcion ?: 'Producto natural de alta calidad, seleccionado para tu bienestar.', 80) }}</div>
                            @if($producto->enfermedades->count() > 0)
                            <div class="product-benefits">
                                @foreach($producto->enfermedades->take(3) as $enf)
                                <span class="benefit-tag">{{ $enf->nombre }}</span>
                                @endforeach
                            </div>
                            @endif
                            <div class="product-price-premium">
                                S/ {{ number_format($producto->precio, 2) }}
                                <small>Incluido</small>
                            </div>
                            <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🌿 Me interesa: *' . $producto->nombre . '* (S/' . number_format($producto->precio, 2) . '). ¿Disponible?') }}"
                               target="_blank" class="btn-whatsapp-premium">
                                <i class="bi bi-whatsapp"></i> Consultar
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5 reveal" style="color:var(--text-sec);">
                <i class="bi bi-box-seam" style="font-size:56px;opacity:0.15;"></i>
                <p class="mt-3" style="font-size:15px;">No se encontraron productos con los filtros seleccionados.</p>
                <a href="{{ route('catalogo') }}" class="btn-whatsapp-premium" style="display:inline-flex;width:auto;padding:10px 24px;">
                    <i class="bi bi-arrow-counterclockwise"></i> Ver todos
                </a>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ── CORDIALES ARTESANALES ─────────────────── --}}
<section class="container py-5" id="cordiales">
    <div class="text-center reveal">
        <div class="section-title">🥤 Cordiales Artesanales</div>
        <div class="section-subtitle">Bebidas naturales preparadas en el momento — consumo en tienda o para llevar</div>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8 reveal">
            <div class="cordial-table-wrap">
                <table class="cordial-table">
                    <thead><tr><th style="text-align:left;">Producto</th><th>En tienda</th><th>Para llevar</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Cordial Especial</strong></td><td>S/ 5.00</td><td>S/ 5.00</td></tr>
                        <tr><td><strong>Cordial Normal</strong></td><td>S/ 3.00</td><td>S/ 3.00</td></tr>
                        <tr><td><strong>Litro Especial</strong></td><td colspan="2">S/ 40.00</td></tr>
                        <tr><td><strong>Medio Litro Especial</strong></td><td colspan="2">S/ 20.00</td></tr>
                        <tr><td><strong>Litro Puro</strong></td><td colspan="2">S/ 80.00</td></tr>
                        <tr><td><strong>Medio Litro Puro</strong></td><td colspan="2">S/ 40.00</td></tr>
                    </tbody>
                </table>
                <div class="text-center mt-3">
                    <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🧃 Me interesa pedir cordiales artesanales. ¿Cuáles tienen?') }}" target="_blank" class="btn-whatsapp-premium" style="display:inline-flex;width:auto;padding:10px 24px;">
                        <i class="bi bi-whatsapp"></i> Pedir Cordiales
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 reveal">
            <div class="info-glass" style="height:100%;">
                <div style="font-size:36px;text-align:center;margin-bottom:14px;">🏍️</div>
                <h5 style="font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:10px;color:var(--text-prim);text-align:center;">Entrega local</h5>
                <p style="font-size:13px;color:var(--text-sec);line-height:1.8;margin:0;text-align:center;">
                    Pedido mínimo: <strong style="color:var(--bio-neon);">S/ 30.00</strong><br>
                    Envío <strong style="color:var(--text-prim);">GRATIS</strong> — pagamos la mototaxi 🎉<br>
                    <span style="font-size:11px;color:rgba(191,255,128,0.25);margin-top:8px;display:block;">Solo dentro de la ciudad de Jauja</span>
                </p>
                <hr style="border-color:rgba(191,255,0,0.15);margin:16px 0;">
                <div style="text-align:center;">
                    <img src="{{ asset('img/yape_qr.png') }}" alt="QR Yape" style="width:120px;border-radius:10px;border:2px solid rgba(128,0,255,0.25);">
                    <p style="font-size:11px;color:rgba(128,0,255,0.70);margin-top:6px;font-weight:600;">Paga con Yape</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── PROGRAMA DE FIDELIZACIÓN ──────────────── --}}
<section class="container py-5" id="fidelizacion">
    <div class="reveal">
        <div class="loyalty-banner">
            <h3>🏆 Programa de Fidelización {{ date('Y') }}</h3>
            <p>
                Acumula <span class="highlight">S/ {{ config('naturacor.fidelizacion_monto', 500) }}</span> en compras y recibe
                <span class="highlight">1 Botella de Litro Especial GRATIS</span> (valor S/ 40).
                <br><br>¡Pregúntanos por tu acumulado con tu DNI!
            </p>
            <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🏆 Quiero consultar mi acumulado del programa de fidelización. Mi DNI es: ') }}" target="_blank" class="btn-loyalty">
                <i class="bi bi-whatsapp"></i> Consultar mi acumulado
            </a>
        </div>
    </div>
</section>

{{-- ── CONTACTO CON GOOGLE MAPS ──────────────── --}}
<section class="container py-5" id="contacto">
    <div class="text-center reveal mb-4">
        <div class="section-title">📍 Encuéntranos</div>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7 reveal">
            <div style="border-radius:18px;overflow:hidden;border:2px solid rgba(191,255,0,0.20);box-shadow:0 8px 32px rgba(0,0,0,0.3);">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.5!2d-75.4967!3d-11.7753!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x910e960cae15c0f1%3A0xa459cda3e5ae1ab!2sPlaza%20de%20Armas%20de%20Jauja!5e0!3m2!1ses!2spe!4v1680000000000!5m2!1ses!2spe"
                        width="100%" height="350" style="border:0;display:block;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-12 reveal">
                    <div class="info-glass text-center">
                        <i class="bi bi-clock" style="font-size:28px;color:var(--bio-neon);"></i>
                        <h5 class="mt-2 mb-1" style="font-family:'Playfair Display',serif;font-size:16px;font-weight:700;">Horario</h5>
                        <p style="font-size:13px;color:var(--text-sec);margin:0;">Lunes a Domingo<br><strong style="color:var(--text-prim);">9:00 AM — 9:00 PM</strong></p>
                    </div>
                </div>
                <div class="col-12 reveal">
                    <div class="info-glass text-center">
                        <i class="bi bi-geo-alt" style="font-size:28px;color:var(--bio-neon);"></i>
                        <h5 class="mt-2 mb-1" style="font-family:'Playfair Display',serif;font-size:16px;font-weight:700;">Ubicación</h5>
                        <p style="font-size:13px;color:var(--text-sec);margin:0;">Plaza de Armas — Jauja, Junín<br><strong style="color:var(--text-prim);">Perú</strong></p>
                    </div>
                </div>
                <div class="col-12 reveal">
                    <div class="info-glass text-center">
                        <i class="bi bi-whatsapp" style="font-size:28px;color:#25d366;"></i>
                        <h5 class="mt-2 mb-1" style="font-family:'Playfair Display',serif;font-size:16px;font-weight:700;">WhatsApp</h5>
                        <p style="font-size:13px;color:var(--text-sec);margin:0;">
                            <a href="https://wa.me/51{{ $whatsapp }}" target="_blank" style="color:#25d366;font-weight:600;text-decoration:none;">+51 {{ $whatsapp }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── MODAL VALORACIÓN ──────────────────────── --}}
<div id="reviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(6px);" onclick="if(event.target===this)closeReview()">
    <div style="background:rgba(10,41,20,0.97);border:1px solid rgba(191,255,0,0.30);border-radius:20px;padding:32px;width:100%;max-width:420px;margin:20px;">
        <h5 style="font-family:'Playfair Display',serif;font-weight:700;color:var(--bio-neon);margin-bottom:6px;">⭐ Dejar Valoración</h5>
        <p id="reviewProduct" style="font-size:13px;color:var(--text-sec);margin-bottom:18px;"></p>
        <form method="POST" action="{{ route('catalogo.valorar') }}">
            @csrf
            <input type="hidden" name="producto_id" id="reviewProductId">
            <div style="margin-bottom:16px;">
                <label style="font-size:12px;font-weight:600;color:var(--text-prim);display:block;margin-bottom:6px;">Tu nombre</label>
                <input type="text" name="nombre_cliente" required maxlength="100" class="filter-input" placeholder="Ej: María López">
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:12px;font-weight:600;color:var(--text-prim);display:block;margin-bottom:6px;">Calificación</label>
                <div id="starPicker" style="display:flex;gap:4px;font-size:28px;cursor:pointer;">
                    <span onclick="setStar(1)" data-star="1">☆</span>
                    <span onclick="setStar(2)" data-star="2">☆</span>
                    <span onclick="setStar(3)" data-star="3">☆</span>
                    <span onclick="setStar(4)" data-star="4">☆</span>
                    <span onclick="setStar(5)" data-star="5">☆</span>
                </div>
                <input type="hidden" name="estrellas" id="starValue" value="5" required>
            </div>
            <div style="margin-bottom:18px;">
                <label style="font-size:12px;font-weight:600;color:var(--text-prim);display:block;margin-bottom:6px;">Comentario (opcional)</label>
                <textarea name="comentario" rows="3" maxlength="500" class="filter-input" placeholder="¿Qué te pareció el producto?" style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeReview()" style="flex:1;padding:10px;border-radius:10px;border:1px solid rgba(191,255,0,0.25);background:transparent;color:var(--text-sec);font-family:'Montserrat',sans-serif;font-size:13px;cursor:pointer;">Cancelar</button>
                <button type="submit" class="filter-btn" style="flex:1;"><i class="bi bi-star-fill"></i> Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReview(id, name) {
    document.getElementById('reviewProductId').value = id;
    document.getElementById('reviewProduct').textContent = 'Producto: ' + name;
    document.getElementById('reviewModal').style.display = 'flex';
    setStar(5);
}
function closeReview() { document.getElementById('reviewModal').style.display = 'none'; }
function setStar(n) {
    document.getElementById('starValue').value = n;
    document.querySelectorAll('#starPicker span').forEach((s, i) => {
        s.textContent = i < n ? '★' : '☆';
        s.style.color = i < n ? '#fbbf24' : 'rgba(255,255,255,0.25)';
    });
}
</script>
@endsection
