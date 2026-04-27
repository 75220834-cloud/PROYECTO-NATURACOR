<?php

namespace App\Jobs\Recommendation;

use App\Services\Recommendation\CoocurrenciaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Bloque 3 — Job nocturno que recomputa la matriz item-item de
 * co-ocurrencia (producto_coocurrencias) usada por el componente
 * colaborativo del motor híbrido.
 *
 * Cómputo costoso: O(canastas × productos²) sobre la ventana configurada.
 * Por eso DEBE ejecutarse offline, no en línea con el request del POS.
 *
 * Reusa exactamente la misma lógica del Artisan command
 * `recomendaciones:cooccurrencia`, garantizando que job y comando manual
 * den resultados idénticos. Acepta overrides de parámetros para escenarios
 * de testing o tuning sin tocar `.env`.
 */
class ReconstruirCoocurrenciaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public ?int $diasVentana = null,
        public ?int $minCoCount = null,
        public ?string $metrica = null,
    ) {
    }

    public function handle(CoocurrenciaService $service): void
    {
        $inicio = microtime(true);

        $resumen = $service->recomputar(
            $this->diasVentana,
            $this->minCoCount,
            $this->metrica,
        );

        Log::info('ReconstruirCoocurrenciaJob completado', $resumen + [
            'duracion_seg' => round(microtime(true) - $inicio, 3),
        ]);
    }
}
