<?php

namespace App\Jobs\Recommendation;

use App\Services\Forecasting\DemandaForecastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bloque 5 — Job semanal que recomputa el modelo de pronóstico SES.
 *
 * Pipeline en dos pasos atómicos:
 *   1. materializarHistorico(): refresca `producto_demanda_semana` con las
 *      unidades vendidas reales de la ventana configurada.
 *   2. recomputarPredicciones(): por cada (producto, sucursal) con historia
 *      suficiente, ajusta SES y guarda snapshot en
 *      `producto_prediccion_demanda` para la próxima semana ISO.
 *
 * Idempotente: ejecutar el job N veces deja el sistema en el mismo estado
 * (gracias a los UNIQUE constraints + updateOrCreate). Eso permite
 * reprocesar tras un fallo sin riesgo de duplicación.
 *
 * Programado en `routes/console.php` con `weeklyOn(dia, hora)` para que
 * corra una sola vez por semana (default lunes 03:00). Se puede desactivar
 * vía `REC_JOB_DEMANDA_ENABLED=false` sin tocar código.
 */
class ActualizarDemandaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800; // 30 min: agregaciones grandes pueden tardar

    public function __construct(
        public ?int $semanasVentana = null,
        public ?int $sucursalId = null,
        public ?float $alpha = null,
    ) {}

    public function handle(DemandaForecastService $service): void
    {
        $inicio = microtime(true);

        try {
            $resumenHist = $service->materializarHistorico($this->semanasVentana, $this->sucursalId);
            $resumenPred = $service->recomputarPredicciones($this->sucursalId, $this->alpha);
        } catch (Throwable $e) {
            Log::error('ActualizarDemandaJob falló', [
                'error'           => $e->getMessage(),
                'semanas_ventana' => $this->semanasVentana,
                'sucursal_id'     => $this->sucursalId,
                'alpha'           => $this->alpha,
            ]);
            throw $e;
        }

        Log::info('ActualizarDemandaJob completado', [
            'historico'    => $resumenHist,
            'predicciones' => $resumenPred,
            'duracion_seg' => round(microtime(true) - $inicio, 3),
        ]);
    }
}
