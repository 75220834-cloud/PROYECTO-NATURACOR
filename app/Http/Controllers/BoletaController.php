<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BoletaController extends Controller
{
    public function show(Venta $venta)
    {
        $venta->load(['cliente', 'empleado', 'sucursal', 'detalles.producto', 'cordialVentas']);
        return view('boletas.show', compact('venta'));
    }

    public function pdf(Venta $venta)
    {
        $venta->load(['cliente', 'empleado', 'sucursal', 'detalles.producto', 'cordialVentas']);
        $pdf = Pdf::loadView('boletas.pdf', compact('venta'))
            ->setPaper([0, 0, 226.77, 566.93]); // 80mm ticket
        return $pdf->download("boleta-{$venta->numero_boleta}.pdf");
    }

    public function whatsapp(Venta $venta)
    {
        $venta->load(['cliente', 'empleado', 'sucursal', 'detalles.producto']);
        $texto = $this->generarTextoWhatsapp($venta);
        $telefono = $venta->cliente?->telefono ?? '';
        $url = "https://wa.me/{$telefono}?text=" . urlencode($texto);
        return redirect($url);
    }

    private function generarTextoWhatsapp(Venta $venta): string
    {
        $lineas = ["🌿 *NATURACOR* - Boleta de Venta"];
        $lineas[] = "N° {$venta->numero_boleta}";
        $lineas[] = "Fecha: " . $venta->created_at->format('d/m/Y H:i');
        $lineas[] = "Cliente: " . ($venta->cliente ? $venta->cliente->nombreCompleto() : 'Sin registro');
        $lineas[] = "─────────────────────";
        foreach ($venta->detalles as $det) {
            $lineas[] = "• {$det->nombre_producto} x{$det->cantidad} = S/{$det->subtotal}";
        }
        $lineas[] = "─────────────────────";
        $lineas[] = "Subtotal: S/" . number_format($venta->subtotal, 2);
        $lineas[] = "IGV (18%): S/" . number_format($venta->igv, 2);
        $lineas[] = "*TOTAL: S/" . number_format($venta->total, 2) . "*";
        $lineas[] = "Pago: " . strtoupper($venta->metodo_pago);
        $lineas[] = "Vendedor: " . $venta->empleado->name;
        $lineas[] = "\n¡Gracias por su preferencia! 🌱";
        return implode("\n", $lineas);
    }
}
