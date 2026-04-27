<?php

namespace App\Services\Fidelizacion;

use App\Models\Cliente;
use App\Models\FidelizacionCanje;
use App\Models\Venta;

class FidelizacionService
{
    /**
     * Suma el monto al acumulado permanente del cliente y crea
     * los canjes faltantes según floor(acumulado / umbral).
     *
     * @return list<array{tipo_regla:string,descripcion_premio:string}>
     */
    public function registrarAcumuladoYGenerarCanjes(
        Venta $venta,
        ?Cliente $cliente,
        float $montoAplicable
    ): array {
        if (! $cliente || $montoAplicable <= 0) {
            return [];
        }

        $cliente->increment('acumulado_naturales', $montoAplicable);
        $cliente->refresh();

        $umbral = max(1, (int) config('naturacor.fidelizacion_monto', 500));
        $premiosTeoricos = (int) floor(((float) $cliente->acumulado_naturales) / $umbral);
        $premiosEmitidos = (int) FidelizacionCanje::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo_regla', FidelizacionCanje::REGLA_NATURALES)
            ->count();

        $faltantes = max(0, $premiosTeoricos - $premiosEmitidos);
        if ($faltantes === 0) {
            return [];
        }

        $emitidos = [];
        for ($i = 0; $i < $faltantes; $i++) {
            $indicePremio = $premiosEmitidos + $i + 1;
            $hito = $indicePremio * $umbral;

            $canje = FidelizacionCanje::create([
                'cliente_id' => $cliente->id,
                'venta_id' => $venta->id,
                'tipo_regla' => FidelizacionCanje::REGLA_NATURALES,
                'valor_premio' => 0,
                'descripcion' => "Premio #{$indicePremio} por acumulado histórico >= S/{$hito}",
                'descripcion_premio' => '1 Botella de Litro Especial gratis (S/40)',
                'entregado' => false,
            ]);

            $emitidos[] = [
                'tipo_regla' => $canje->tipo_regla,
                'descripcion_premio' => $canje->descripcion_premio,
            ];
        }

        return $emitidos;
    }
}
