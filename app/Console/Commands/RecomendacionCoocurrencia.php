<?php

namespace App\Console\Commands;

use App\Services\Recommendation\CoocurrenciaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Bloque 2 — Reconstruye la matriz de co-ocurrencia producto↔producto.
 *
 * Pensado para correr en background (queue/cron). Es seguro en producción:
 *  - Hace delete + insert en transacción.
 *  - No escribe en tablas que la app lee en caliente para responder al cliente.
 *  - Idempotente: ejecutarlo dos veces seguidas produce el mismo resultado.
 */
class RecomendacionCoocurrencia extends Command
{
    protected $signature = 'recomendaciones:cooccurrencia
        {--dias=    : Días de ventana (default: config recommendaciones.cooccurrencia.dias_ventana)}
        {--min=     : Mínimo de co-ocurrencias para persistir un par (default: config)}
        {--metrica= : "jaccard" | "npmi" (default: config)}
        {--quiet-log : No emitir Log::info al finalizar}';

    protected $description = 'Reconstruye la matriz item-item (Jaccard + NPMI) usada por el motor híbrido.';

    public function handle(CoocurrenciaService $service): int
    {
        $dias = $this->option('dias');
        $min = $this->option('min');
        $metrica = $this->option('metrica');

        $this->info('Recomputando matriz de co-ocurrencia…');

        try {
            $resumen = $service->recomputar(
                $dias !== null ? (int) $dias : null,
                $min !== null ? (int) $min : null,
                $metrica !== null ? (string) $metrica : null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        }

        $this->table(
            ['Métrica', 'Ventana (días)', 'Transacciones', 'Productos', 'Pares calculados', 'Pares persistidos'],
            [[
                $resumen['metrica'],
                $resumen['dias_ventana'],
                $resumen['transacciones'],
                $resumen['productos'],
                $resumen['pares_calculados'],
                $resumen['pares_persistidos'],
            ]]
        );

        if (! $this->option('quiet-log')) {
            Log::info('recomendaciones:cooccurrencia ejecutado', $resumen);
        }

        return self::SUCCESS;
    }
}
