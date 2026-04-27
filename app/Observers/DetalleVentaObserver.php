<?php

namespace App\Observers;

use App\Models\DetalleVenta;
use App\Services\Recommendation\MetricsService;

/**
 * Enlaza ventas reales con métricas de recomendación sin tocar VentaController.
 */
class DetalleVentaObserver
{
    public function __construct(
        private readonly MetricsService $metrics
    ) {}

    public function created(DetalleVenta $detalleVenta): void
    {
        $this->metrics->registrarCompradaSiCorresponde($detalleVenta);
    }
}
