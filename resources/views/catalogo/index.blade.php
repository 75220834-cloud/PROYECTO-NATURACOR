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
                {{-- Buscador --}}
                <form method="GET" action="{{ route('catalogo') }}" id="catalogoForm">
                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-search"></i> Buscar</div>
                        <input type="text" name="search" value="{{ $search ?? '' }}"
                               placeholder="Nombre del producto..."
                               class="filter-input">
                    </div>

                    {{-- Tipo --}}
                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-tag"></i> Categorías</div>
                        <label class="filter-check">
                            <input type="radio" name="tipo" value="" {{ !$tipo ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>Todos</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="tipo" value="natural" {{ $tipo === 'natural' ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>🌱 Naturales</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="tipo" value="cordial" {{ $tipo === 'cordial' ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>🧃 Cordiales</span>
                        </label>
                    </div>

                    {{-- Beneficios de salud --}}
                    @if($beneficios->count() > 0)
                    <div class="filter-section">
                        <div class="filter-title"><i class="bi bi-heart-pulse"></i> Beneficios</div>
                        <label class="filter-check">
                            <input type="radio" name="beneficio" value="" {{ !$beneficio ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>Todos</span>
                        </label>
                        @foreach($beneficios as $b)
                        <label class="filter-check">
                            <input type="radio" name="beneficio" value="{{ $b->id }}" {{ (int)$beneficio === $b->id ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>{{ $b->nombre }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endif

                    <button type="submit" class="filter-btn">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>

                    @if($search || $tipo || $beneficio)
                    <a href="{{ route('catalogo') }}" class="filter-clear">
                        <i class="bi bi-x-circle"></i> Limpiar filtros
                    </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- ═══ GRID DE PRODUCTOS ═══ --}}
        <div class="col-lg-9 col-md-8">

            @if($search || $tipo || $beneficio)
            <div class="filter-active-bar reveal">
                <span>Mostrando <strong>{{ $productos->count() }}</strong> resultado(s)</span>
                @if($search) <span class="filter-tag">🔍 "{{ $search }}"</span> @endif
                @if($tipo) <span class="filter-tag">📦 {{ ucfirst($tipo) }}</span> @endif
                @if($beneficio)
                    @php $bNombre = $beneficios->firstWhere('id', (int)$beneficio)?->nombre @endphp
                    @if($bNombre) <span class="filter-tag">💊 {{ $bNombre }}</span> @endif
                @endif
            </div>
            @endif

            @if($productos->count() > 0)
            <div class="row g-3">
                @foreach($productos as $producto)
                <div class="col-6 col-md-4 reveal">
                    <div class="product-card">
                        <div class="product-img">
                            @if($url = producto_image_url($producto))
                                <img src="{{ $url }}" alt="{{ $producto->nombre }}" loading="lazy">
                            @else
                                <span class="emoji-placeholder">🌿</span>
                            @endif
                            <span class="badge-tipo {{ $producto->tipo }}">{{ ucfirst($producto->tipo) }}</span>
                        </div>
                        <div class="product-body">
                            <div class="product-name">{{ $producto->nombre }}</div>
                            <div class="product-desc">{{ $producto->descripcion ?: 'Producto natural de alta calidad.' }}</div>
                            @if($producto->enfermedades->count() > 0)
                            <div class="product-benefits">
                                @foreach($producto->enfermedades->take(2) as $enf)
                                <span class="benefit-tag">{{ $enf->nombre }}</span>
                                @endforeach
                            </div>
                            @endif
                            <div class="product-price">
                                S/ {{ number_format($producto->precio, 2) }}
                                <small>Incluido</small>
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
            <div class="text-center py-5 reveal" style="color:var(--text-sec);">
                <i class="bi bi-box-seam" style="font-size:56px;opacity:0.15;"></i>
                <p class="mt-3" style="font-size:15px;">No se encontraron productos con los filtros seleccionados.</p>
                <a href="{{ route('catalogo') }}" class="btn-whatsapp" style="display:inline-flex;width:auto;padding:10px 24px;margin-top:12px;">
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
        {{-- Tabla de precios --}}
        <div class="col-lg-8 reveal">
            <div class="cordial-table-wrap">
                <table class="cordial-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Producto</th>
                            <th>En tienda</th>
                            <th>Para llevar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Cordial Especial</strong></td>
                            <td>S/ 5.00</td>
                            <td>S/ 5.00</td>
                        </tr>
                        <tr>
                            <td><strong>Cordial Normal</strong></td>
                            <td>S/ 3.00</td>
                            <td>S/ 3.00</td>
                        </tr>
                        <tr>
                            <td><strong>Litro Especial</strong></td>
                            <td colspan="2">S/ 40.00</td>
                        </tr>
                        <tr>
                            <td><strong>Medio Litro Especial</strong></td>
                            <td colspan="2">S/ 20.00</td>
                        </tr>
                        <tr>
                            <td><strong>Litro Puro</strong></td>
                            <td colspan="2">S/ 80.00</td>
                        </tr>
                        <tr>
                            <td><strong>Medio Litro Puro</strong></td>
                            <td colspan="2">S/ 40.00</td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-center mt-3">
                    <a href="https://wa.me/51{{ $whatsapp }}?text={{ urlencode('¡Hola! 🧃 Me interesa pedir cordiales artesanales. ¿Cuáles tienen disponibles?') }}"
                       target="_blank" class="btn-whatsapp" style="display:inline-flex;width:auto;padding:10px 24px;">
                        <i class="bi bi-whatsapp"></i> Pedir Cordiales
                    </a>
                </div>
            </div>
        </div>

        {{-- Info entrega --}}
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
                <div style="font-size:36px;text-align:center;margin-bottom:14px;">📦</div>
                <h5 style="font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:10px;color:var(--text-prim);text-align:center;">Envíos a todo el Perú</h5>
                <p style="font-size:13px;color:var(--text-sec);line-height:1.8;margin:0;text-align:center;">
                    Enviamos por la agencia de tu preferencia<br>
                    <span style="font-size:11px;color:rgba(191,255,128,0.25);margin-top:8px;display:block;">Costo del envío a cargo del cliente</span>
                </p>
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

{{-- ── HORARIO Y CONTACTO ────────────────────── --}}
<section class="container py-5" id="contacto">
    <div class="row g-4 justify-content-center">
        <div class="col-md-4 reveal">
            <div class="info-glass text-center">
                <i class="bi bi-clock" style="font-size:30px;color:var(--bio-neon);"></i>
                <h5 class="mt-3 mb-2" style="font-family:'Playfair Display',serif;font-size:17px;font-weight:700;">Horario</h5>
                <p style="font-size:13px;color:var(--text-sec);margin:0;line-height:1.7;">
                    Lunes a Domingo<br>
                    <strong style="color:var(--text-prim);">9:00 AM — 9:00 PM</strong>
                </p>
            </div>
        </div>
        <div class="col-md-4 reveal">
            <div class="info-glass text-center">
                <i class="bi bi-geo-alt" style="font-size:30px;color:var(--bio-neon);"></i>
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
