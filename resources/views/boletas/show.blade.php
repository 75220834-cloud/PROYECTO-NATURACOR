@extends('layouts.app')
@section('title', "Boleta #{$venta->numero_boleta}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ url()->previous() }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">🧾 Boleta de Venta</h4>
        <small class="text-muted"># {{ $venta->numero_boleta }}</small>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('boletas.pdf', $venta) }}" class="btn btn-outline-danger btn-sm px-3" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('boletas.ticket', $venta) }}" class="btn btn-dark btn-sm px-3" target="_blank"
            title="Imprimir en impresora térmica 80mm">
            <i class="bi bi-printer-fill me-1"></i>🖨️ Ticket Térmico
        </a>
        @if($venta->cliente?->telefono)
        <a href="{{ route('boletas.whatsapp', $venta) }}" class="btn btn-success btn-sm px-3" target="_blank">
            <i class="bi bi-whatsapp me-1"></i>WhatsApp
        </a>
        @endif
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<!-- Ticket visual -->
<div class="card border-0 shadow-sm rounded-4" style="background:rgba(7,26,16,0.60);backdrop-filter:blur(16px);">
    <div class="card-body p-0">
        <!-- Header verde -->
        <div style="background:linear-gradient(135deg,#16a34a,#22c55e);border-radius:16px 16px 0 0;padding:24px;text-align:center;color:white;">
            <div style="font-size:28px;font-weight:900;letter-spacing:2px;">🌿 NATURACOR</div>
            <div style="font-size:12px;opacity:0.9;margin-top:4px;">Productos Naturales - Sistema de Gestión</div>
            <div style="margin-top:12px;font-size:14px;opacity:0.9;">
                {{ $venta->sucursal?->nombre ?? 'Sede Principal' }}
                @if($venta->sucursal?->direccion)
                    <br>{{ $venta->sucursal->direccion }}
                @endif
            </div>
        </div>

        <div class="px-4 py-3">
            <!-- Info boleta -->
            <div class="d-flex justify-content-between" style="font-size:13px;margin-bottom:12px;">
                <div>
                    <div class="text-muted">N° Boleta</div>
                    <div class="fw-bold">{{ $venta->numero_boleta }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted">Fecha</div>
                    <div class="fw-bold">{{ $venta->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            <!-- Cliente -->
            @if($venta->cliente)
            <div style="background:rgba(40,199,111,0.10);border-radius:10px;padding:12px;margin-bottom:12px;font-size:13px;">
                <i class="bi bi-person text-success me-1"></i>
                <strong>{{ $venta->cliente->nombre }} {{ $venta->cliente->apellido }}</strong>
                @if($venta->cliente->dni) — DNI: {{ $venta->cliente->dni }} @endif
            </div>
            @endif

            <!-- Detalle de productos -->
            <div style="border-top:2px dashed rgba(255,255,255,0.15);border-bottom:2px dashed rgba(255,255,255,0.15);padding:12px 0;margin:12px 0;">
                <table style="width:100%;font-size:13px;">
                    <thead>
                        <tr style="color:#9caea4;font-size:11px;text-transform:uppercase;">
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venta->detalles as $det)
                        <tr>
                            <td class="py-1">
                                {{ $det->nombre_producto }}
                                @if($det->es_gratis) <span class="badge bg-success" style="font-size:9px;">GRATIS</span> @endif
                            </td>
                            <td class="text-center">{{ $det->cantidad }}</td>
                            <td class="text-end">S/ {{ number_format($det->precio_unitario,2) }}</td>
                            <td class="text-end fw-semibold">S/ {{ number_format($det->subtotal,2) }}</td>
                        </tr>
                        @endforeach
                        <!-- Cordial ventas -->
                        @foreach($venta->cordialVentas as $cord)
                        <tr>
                            <td class="py-1 text-muted">
                                🧃 {{ class_exists('\App\Models\CordialVenta') && isset(\App\Models\CordialVenta::$labels[$cord->tipo]) ? \App\Models\CordialVenta::$labels[$cord->tipo] : $cord->tipo }}
                                @if($cord->es_invitado) <span class="badge bg-info" style="font-size:9px;">INVITADO</span> @endif
                            </td>
                            <td class="text-center">{{ $cord->cantidad }}</td>
                            <td class="text-end">S/ {{ number_format($cord->precio,2) }}</td>
                            <td class="text-end">S/ {{ number_format($cord->precio * $cord->cantidad,2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totales -->
            <table style="width:100%;font-size:13px;margin-bottom:12px;">
                <thead class="visually-hidden">
                    <tr><th scope="col">Concepto</th><th scope="col">Monto</th></tr>
                </thead>
                <tbody>
                    <tr><td class="text-muted">Subtotal</td><td class="text-end">S/ {{ number_format($venta->subtotal,2) }}</td></tr>
                    @if($venta->descuento > 0)
                        <tr><td class="text-muted">Descuento</td><td class="text-end text-danger">- S/ {{ number_format($venta->descuento,2) }}</td></tr>
                    @endif
                    <tr><td class="text-muted">IGV (18%)</td><td class="text-end">S/ {{ number_format($venta->igv,2) }}</td></tr>
                    <tr style="border-top:2px solid rgba(255,255,255,0.12);">
                        <td class="pt-2 fw-bold" style="font-size:16px;">TOTAL</td>
                        <td class="pt-2 text-end fw-bold" style="font-size:18px;color:var(--neon);">S/ {{ number_format($venta->total,2) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pago y Vendedor -->
            <div style="background:rgba(40,199,111,0.10);border-radius:10px;padding:12px;font-size:13px;text-align:center;">
                <span class="fw-bold" style="color:var(--neon);">Pago: {{ strtoupper($venta->metodo_pago) }}</span>
                @if($venta->monto_recibido > 0)
                 — Recibido: S/ {{ number_format($venta->monto_recibido,2) }}
                 — Cambio: S/ {{ number_format($venta->monto_recibido - $venta->total, 2) }}
                @endif
            </div>

            <div style="text-align:center;font-size:12px;color:#9caea4;margin-top:16px;padding-bottom:8px;">
                Atendido por: <strong>{{ $venta->empleado?->name ?? '—' }}</strong><br>
                ¡Gracias por su preferencia! 🌱
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
