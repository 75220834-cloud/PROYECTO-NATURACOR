<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #{{ $venta->numero_boleta }}</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Estilos para pantalla (previsualización) */
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            background: #f5f5f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .ticket {
            width: 280px; /* ~80mm */
            background: white;
            padding: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }

        .ticket-58 { width: 210px; } /* ~58mm */

        .ticket-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .ticket-header .logo { font-size: 18px; font-weight: bold; letter-spacing: 2px; }
        .ticket-header .sub { font-size: 10px; margin-top: 2px; }

        .ticket-info { margin-bottom: 8px; font-size: 11px; }
        .ticket-info div { display: flex; justify-content: space-between; }

        .ticket-divider { border-top: 1px dashed #000; margin: 8px 0; }

        .ticket-items table { width: 100%; font-size: 11px; }
        .ticket-items th { text-align: left; font-size: 10px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .ticket-items td { padding: 3px 0; vertical-align: top; }
        .ticket-items .text-right { text-align: right; }
        .ticket-items .text-center { text-align: center; }

        .ticket-totals { margin-top: 8px; font-size: 11px; }
        .ticket-totals div { display: flex; justify-content: space-between; padding: 2px 0; }
        .ticket-totals .total-line { font-size: 14px; font-weight: bold; border-top: 2px solid #000; padding-top: 4px; margin-top: 4px; }

        .ticket-footer { text-align: center; margin-top: 10px; font-size: 10px; border-top: 1px dashed #000; padding-top: 8px; }

        /* Controles de impresión (solo en pantalla) */
        .print-controls {
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .print-controls button {
            padding: 8px 20px; border-radius: 8px; cursor: pointer;
            font-weight: 600; font-size: 13px; border: none;
        }
        .btn-print { background: #16a34a; color: white; }
        .btn-print:hover { background: #15803d; }
        .btn-back { background: #e5e7eb; color: #374151; }
        .btn-back:hover { background: #d1d5db; }
        .btn-size { padding: 6px 14px; border-radius: 6px; border: 2px solid #d1d5db; background: white; cursor: pointer; font-size: 12px; }
        .btn-size.active { border-color: #16a34a; background: #dcfce7; color: #166534; }

        /* Estilos de impresión */
        @media print {
            body { background: white; padding: 0; }
            .print-controls { display: none !important; }
            .ticket {
                width: 100% !important;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            @page {
                margin: 2mm;
                size: auto;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Ticket</button>
        <button class="btn-back" onclick="window.close()">← Cerrar</button>
        <span style="margin-left:12px; font-size:12px; color:#6b7280;">Tamaño:</span>
        <button class="btn-size active" onclick="setSize('80')">80mm</button>
        <button class="btn-size" onclick="setSize('58')">58mm</button>
    </div>

    <div class="ticket" id="ticket">
        {{-- Header --}}
        <div class="ticket-header">
            <div class="logo">🌿 NATURACOR</div>
            <div class="sub">Productos Naturales</div>
            @if($venta->sucursal)
            <div class="sub">{{ $venta->sucursal->nombre }}</div>
            @if($venta->sucursal->direccion)
            <div class="sub">{{ $venta->sucursal->direccion }}</div>
            @endif
            @endif
        </div>

        {{-- Info boleta --}}
        <div class="ticket-info">
            <div><span>Boleta:</span><span>{{ $venta->numero_boleta }}</span></div>
            <div><span>Fecha:</span><span>{{ $venta->created_at->format('d/m/Y H:i') }}</span></div>
            <div><span>Pago:</span><span>{{ strtoupper($venta->metodo_pago) }}</span></div>
            @if($venta->empleado)
            <div><span>Vendedor:</span><span>{{ $venta->empleado->name }}</span></div>
            @endif
        </div>

        {{-- Cliente --}}
        @if($venta->cliente)
        <div class="ticket-divider"></div>
        <div style="font-size:11px;">
            <strong>Cliente:</strong> {{ $venta->cliente->nombre }} {{ $venta->cliente->apellido }}<br>
            @if($venta->cliente->dni)DNI: {{ $venta->cliente->dni }}@endif
        </div>
        @endif

        <div class="ticket-divider"></div>

        {{-- Detalle de productos --}}
        <div class="ticket-items">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cant</th>
                        <th class="text-right">P.U.</th>
                        <th class="text-right">Subt.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $det)
                    <tr>
                        <td>{{ $det->nombre_producto }}</td>
                        <td class="text-center">{{ $det->cantidad }}</td>
                        <td class="text-right">{{ number_format($det->precio_unitario, 2) }}</td>
                        <td class="text-right">{{ number_format($det->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                    @foreach($venta->cordialVentas as $cord)
                    <tr>
                        <td>{{ \App\Models\CordialVenta::$labels[$cord->tipo] ?? $cord->tipo }}@if($cord->es_invitado) (INV)@endif</td>
                        <td class="text-center">{{ $cord->cantidad }}</td>
                        <td class="text-right">{{ number_format($cord->precio, 2) }}</td>
                        <td class="text-right">{{ number_format($cord->precio * $cord->cantidad, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ticket-divider"></div>

        {{-- Totales --}}
        <div class="ticket-totals">
            <div><span>Subtotal:</span><span>S/ {{ number_format($venta->subtotal, 2) }}</span></div>
            <div><span>IGV (18%):</span><span>S/ {{ number_format($venta->igv, 2) }}</span></div>
            @if($venta->descuento_total > 0)
            <div><span>Descuento:</span><span>-S/ {{ number_format($venta->descuento_total, 2) }}</span></div>
            @endif
            <div class="total-line">
                <span>TOTAL:</span>
                <span>S/ {{ number_format($venta->total, 2) }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="ticket-footer">
            ¡Gracias por su preferencia! 🌱<br>
            NATURACOR © 2026<br>
            <span style="font-size:9px;">{{ $venta->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>

    <script>
    function setSize(size) {
        const ticket = document.getElementById('ticket');
        document.querySelectorAll('.btn-size').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        if (size === '58') {
            ticket.classList.add('ticket-58');
        } else {
            ticket.classList.remove('ticket-58');
        }
    }
    </script>
</body>
</html>
