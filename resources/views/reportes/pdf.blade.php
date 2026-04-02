<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
h1 { color: #16a34a; font-size: 18px; margin-bottom: 4px; }
.meta { color: #666; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f0fdf4; }
th { padding: 8px; text-align: left; border-bottom: 2px solid #bbf7d0; font-size: 10px; text-transform: uppercase; color: #6b7280; }
td { padding: 6px 8px; border-bottom: 1px solid #f0fdf4; }
tfoot tr { background: #dcfce7; font-weight: bold; }
.kpis { display: flex; gap: 20px; margin-bottom: 16px; }
.kpi { background: #f0fdf4; border-radius: 8px; padding: 12px 20px; text-align: center; }
.kpi .val { font-size: 20px; font-weight: bold; color: #16a34a; }
.kpi .label { font-size: 10px; color: #6b7280; }
</style>
</head>
<body>
<h1>🌿 NATURACOR — Reporte de Ventas</h1>
<div class="meta">Generado el {{ now()->format('d/m/Y H:i') }}</div>

<div class="kpis">
    <div class="kpi"><div class="val">{{ $totales['ventas'] }}</div><div class="label">Ventas</div></div>
    <div class="kpi"><div class="val">S/ {{ number_format($totales['total'],2) }}</div><div class="label">Total</div></div>
    <div class="kpi"><div class="val">S/ {{ number_format($totales['efectivo'],2) }}</div><div class="label">Efectivo</div></div>
    <div class="kpi"><div class="val">S/ {{ number_format($totales['yape'],2) }}</div><div class="label">Yape</div></div>
    <div class="kpi"><div class="val">S/ {{ number_format($totales['plin'],2) }}</div><div class="label">Plin</div></div>
</div>

<table>
    <thead>
        <tr>
            <th>Boleta</th><th>Fecha</th><th>Cliente</th><th>Empleado</th><th>Pago</th><th style="text-align:right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ventas as $venta)
        <tr>
            <td>{{ $venta->numero_boleta ?? 'N/A' }}</td>
            <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $venta->cliente?->nombre ?? 'General' }}</td>
            <td>{{ $venta->empleado?->name ?? '—' }}</td>
            <td>{{ $venta->metodo_pago }}</td>
            <td style="text-align:right;font-weight:bold;color:#16a34a;">S/ {{ number_format($venta->total,2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align:right;">TOTAL GENERAL:</td>
            <td style="text-align:right;">S/ {{ number_format($totales['total'],2) }}</td>
        </tr>
    </tfoot>
</table>
</body>
</html>
