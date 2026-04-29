<!DOCTYPE html>
<html lang="es">
<title>Boleta NATURACOR</title>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 5mm; color: #1a1a1a; }
.logo-area { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 8px; }
.empresa { font-size: 16px; font-weight: bold; color: #15803d; }
.tagline { font-size: 10px; color: #666; }
.boleta-num { font-size: 13px; font-weight: bold; text-align: center; margin: 6px 0; }
.info-row { display: flex; justify-content: space-between; font-size: 11px; margin: 2px 0; }
.divider { border: none; border-top: 1px dashed #aaa; margin: 8px 0; }
.items-header { display: flex; justify-content: space-between; font-weight: bold; font-size: 11px; background: #f0fdf4; padding: 4px 2px; }
.item-row { display: flex; justify-content: space-between; font-size: 11px; padding: 3px 2px; border-bottom: 1px solid #f0f0f0; }
.item-name { flex: 1; }
.item-qty, .item-price, .item-total { text-align: right; min-width: 45px; }
.totales { margin-top: 8px; }
.total-row { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
.total-final { font-size: 14px; font-weight: bold; color: #15803d; border-top: 2px solid #333; padding-top: 4px; margin-top: 4px; }
.footer { text-align: center; margin-top: 10px; font-size: 10px; color: #666; border-top: 1px dashed #aaa; padding-top: 8px; }
</style>
</head>
<body>
<div class="logo-area">
    <div class="empresa">🌿 NATURACOR</div>
    <div class="tagline">Productos Naturales para tu Salud</div>
    @if($venta->sucursal)
    <div style="font-size:10px; color:#666;">{{ $venta->sucursal->direccion }}</div>
    @endif
</div>
<div class="boleta-num">BOLETA N° {{ $venta->numero_boleta }}</div>
<div class="info-row"><span>Fecha:</span><span>{{ $venta->created_at->format('d/m/Y H:i') }}</span></div>
<div class="info-row"><span>Vendedor:</span><span>{{ $venta->empleado?->name }}</span></div>
@if($venta->cliente)
<div class="info-row"><span>Cliente:</span><span>{{ $venta->cliente->nombreCompleto() }}</span></div>
<div class="info-row"><span>DNI:</span><span>{{ $venta->cliente->dni }}</span></div>
@else
<div class="info-row"><span>Cliente:</span><span>Sin registro</span></div>
@endif
<hr class="divider">
<div class="items-header">
    <span class="item-name">Producto</span>
    <span class="item-qty">Cant.</span>
    <span class="item-price">P/U</span>
    <span class="item-total">Total</span>
</div>
@foreach($venta->detalles as $det)
<div class="item-row">
    <span class="item-name">{{ $det->nombre_producto }}</span>
    <span class="item-qty">{{ $det->cantidad }}</span>
    <span class="item-price">{{ number_format($det->precio_unitario, 2) }}</span>
    <span class="item-total">{{ number_format($det->subtotal, 2) }}</span>
</div>
@endforeach
@if($venta->cordialVentas->count())
<div style="margin-top:4px; font-size:11px; font-weight:bold; color:#9d174d;">── Cordial ──</div>
@foreach($venta->cordialVentas->where('es_invitado', false) as $c)
<div class="item-row" style="color:#9d174d;">
    <span class="item-name">{{ \App\Models\CordialVenta::$labels[$c->tipo] ?? $c->tipo }}</span>
    <span class="item-qty">{{ $c->cantidad }}</span>
    <span class="item-price">{{ number_format($c->precio, 2) }}</span>
    <span class="item-total">{{ number_format($c->precio * $c->cantidad, 2) }}</span>
</div>
@endforeach
@endif
<hr class="divider">
<div class="totales">
    <div class="total-row"><span>Subtotal:</span><span>S/ {{ number_format($venta->subtotal, 2) }}</span></div>
    <div class="total-row"><span>IGV (18%):</span><span>S/ {{ number_format($venta->igv, 2) }}</span></div>
    @if($venta->descuento_total > 0)
    <div class="total-row" style="color:#dc2626;"><span>Descuento:</span><span>-S/ {{ number_format($venta->descuento_total, 2) }}</span></div>
    @endif
    <div class="total-row total-final"><span>TOTAL:</span><span>S/ {{ number_format($venta->total, 2) }}</span></div>
    <div class="total-row" style="margin-top:4px;"><span>Forma de pago:</span><span style="font-weight:600; text-transform:uppercase;">{{ $venta->metodo_pago }}</span></div>
</div>
@if($venta->cliente && $venta->cliente->acumulado_naturales > 0)
<hr class="divider">
<div style="text-align:center; font-size:10px; color:#15803d; padding: 4px 0;">
    💚 Acumulado fidelización: S/ {{ number_format($venta->cliente->acumulado_naturales, 2) }}
</div>
@endif
<div class="footer">
    <div>¡Gracias por su preferencia!</div>
    <div>Cuida tu salud con NATURACOR 🌿</div>
    <div style="margin-top:4px;">Este documento no tiene valor fiscal</div>
</div>
</body>
</html>
