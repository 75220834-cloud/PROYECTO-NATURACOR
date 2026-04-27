@extends('layouts.catalogo')
@section('title', 'Catálogo de Productos')
@section('content')

{{-- ── HERO ─────────────────────────────────────── --}}
<section class="hero fade-up">
    <h1>🌿 NATURACOR</h1>
    <p>Productos naturales y cordiales artesanales para tu bienestar. Haz tu pedido directamente por WhatsApp.</p>
    <div class="badge-location">
        <i class="bi bi-geo-alt-fill"></i> Jauja, Junín — Perú
    </div>
</section>

{{-- ── PRODUCTOS NATURALES ──────────────────────── --}}
<section class="container py-5" id="productos">
    <div class="text-center fade-up">
        <div class="section-title">🌱 Nuestros Productos</div>
        <div class="section-subtitle">Productos naturales seleccionados para tu salud y bienestar</div>
    </div>

    @if($productos->where('tipo', '!=', 'cordial')->count() > 0)
    <div class="row g-4">
        @foreach($productos->where('tipo', '!=', 'cordial') as $producto)
        <div class="col-6 col-md-4 col-lg-3 reveal">
            <div class="product-card">
                <div class="product-img">
                    @if($url = producto_image_url($producto))
                        <img src="{{ $url }}" alt="{{ $producto->nombre }}" loading="lazy">
                    @else
                        <span class="emoji-placeholder">🌿</span>
                    @endif
                    <span class="badge-tipo natural">Natural</span>
                </div>
                <div class="product-body">
                    <div class="product-name">{{ $producto->nombre }}</div>
                    <div class="product-desc">{{ $producto->descripcion ?: 'Producto natural de alta calidad.' }}</div>
                    <div class="product-price">
                        S/ {{ number_format($producto->precio, 2) }}
                        <small>IGV incluido</small>
                    </div>
                    <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🌿 Me interesa el producto: *' . $producto->nombre . '* (S/' . number_format($producto->precio, 2) . '). ¿Tienen disponible?') }}"
                       target="_blank" class="btn-whatsapp">
                        <i class="bi bi-whatsapp"></i> Consultar
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-5" style="color:var(--text-sec);">
        <i class="bi bi-box-seam" style="font-size:44px;opacity:0.2;"></i>
        <p class="mt-3" style="font-size:14px;">Estamos actualizando nuestro catálogo. ¡Contáctanos por WhatsApp!</p>
    </div>
    @endif
</section>

{{-- ── CORDIALES ────────────────────────────────── --}}
<section class="container py-5" id="cordiales">
    <div class="text-center reveal">
        <div class="section-title">🥤 Cordiales Artesanales</div>
        <div class="section-subtitle">Bebidas naturales preparadas en el momento — Para consumir en tienda o para llevar</div>
    </div>

    <div class="row g-3 justify-content-center">
        @foreach($cordiales as $cordial)
        <div class="col-md-6 col-lg-5 reveal">
            <div class="cordial-card">
                <div class="cordial-info">
                    <div class="cordial-name">🧃 {{ $cordial['label'] }}</div>
                </div>
                <div class="cordial-price">S/ {{ number_format($cordial['precio'], 2) }}</div>
                <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🧃 Me interesa el cordial: *' . $cordial['label'] . '* (S/' . number_format($cordial['precio'], 2) . '). ¿Tienen disponible?') }}"
                   target="_blank" class="cordial-btn">
                    <i class="bi bi-whatsapp"></i> Pedir
                </a>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- ── ENVÍOS / DELIVERY ───────────────────────── --}}
<section class="container py-5" id="envios">
    <div class="text-center reveal">
        <div class="section-title">🚚 Envíos y Entregas</div>
        <div class="section-subtitle">Opciones de entrega para tu comodidad</div>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-md-6 reveal">
            <div class="info-glass text-center">
                <div style="font-size:36px;margin-bottom:14px;">🏍️</div>
                <h5 style="font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:10px;color:var(--text-prim);">Delivery en Jauja</h5>
                <p style="font-size:13px;color:var(--text-sec);line-height:1.8;margin:0;">
                    Pedido mínimo: <strong style="color:var(--bio-lime);">S/ 30.00</strong><br>
                    Envío <strong style="color:var(--text-prim);">GRATIS</strong> — nosotros pagamos la mototaxi 🎉<br>
                    <span style="font-size:11px;color:rgba(191,255,128,0.25);margin-top:8px;display:block;">Solo dentro de la ciudad de Jauja</span>
                </p>
            </div>
        </div>
        <div class="col-md-6 reveal">
            <div class="info-glass text-center">
                <div style="font-size:36px;margin-bottom:14px;">📦</div>
                <h5 style="font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:10px;color:var(--text-prim);">Envíos a todo el Perú</h5>
                <p style="font-size:13px;color:var(--text-sec);line-height:1.8;margin:0;">
                    Enviamos por la <strong style="color:var(--text-prim);">agencia de tu preferencia</strong><br>
                    Contáctanos para coordinar tu pedido y el envío<br>
                    <span style="font-size:11px;color:rgba(191,255,128,0.25);margin-top:8px;display:block;">El costo del envío corre por cuenta del cliente</span>
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ── PROGRAMA DE FIDELIZACIÓN ─────────────────── --}}
<section class="container py-5" id="fidelizacion">
    <div class="reveal">
        <div class="loyalty-banner">
            <h3>🏆 Programa de Fidelización {{ date('Y') }}</h3>
            <p>
                Acumula <span class="highlight">S/ {{ config('naturacor.fidelizacion_monto', 500) }}</span> en compras de
                productos y cordiales y recibe
                <span class="highlight">1 Botella de Litro Especial GRATIS</span> (valor S/ 40).
                <br><br>
                ¡Pregúntanos por tu acumulado con tu DNI!
            </p>
            <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🏆 Quiero consultar mi acumulado del programa de fidelización. Mi DNI es: ') }}"
               target="_blank" class="btn-loyalty">
                <i class="bi bi-whatsapp"></i> Consultar mi acumulado
            </a>
        </div>
    </div>
</section>

{{-- ── HORARIO Y CONTACTO ──────────────────────── --}}
<section class="container py-5" id="contacto">
    <div class="row g-4 justify-content-center">
        <div class="col-md-4 reveal">
            <div class="info-glass text-center">
                <i class="bi bi-clock" style="font-size:30px;color:var(--bio-lime);"></i>
                <h5 class="mt-3 mb-2" style="font-family:'Playfair Display',serif;font-size:17px;font-weight:700;">Horario</h5>
                <p style="font-size:13px;color:var(--text-sec);margin:0;line-height:1.7;">
                    Lunes a Domingo<br>
                    <strong style="color:var(--text-prim);">9:00 AM — 9:00 PM</strong>
                </p>
            </div>
        </div>
        <div class="col-md-4 reveal">
            <div class="info-glass text-center">
                <i class="bi bi-geo-alt" style="font-size:30px;color:var(--bio-lime);"></i>
                <h5 class="mt-3 mb-2" style="font-family:'Playfair Display',serif;font-size:17px;font-weight:700;">Ubicación</h5>
                <p style="font-size:13px;color:var(--text-sec);margin:0;line-height:1.7;">
                    Jauja, Junín<br>
                    <strong style="color:var(--text-prim);">Perú</strong>
                </p>
            </div>
        </div>
        <div class="col-md-4 reveal">
            <div class="info-glass text-center">
                <i class="bi bi-whatsapp" style="font-size:30px;color:#25d366;"></i>
                <h5 class="mt-3 mb-2" style="font-family:'Playfair Display',serif;font-size:17px;font-weight:700;">WhatsApp</h5>
                <p style="font-size:13px;color:var(--text-sec);margin:0;line-height:1.7;">
                    <a href="https://wa.me/51{{ $whatsapp }}" target="_blank" style="color:#25d366;font-weight:600;text-decoration:none;">
                        +51 {{ $whatsapp }}
                    </a><br>
                    Escríbenos para hacer tu pedido
                </p>
            </div>
        </div>
    </div>
</section>

@endsection
