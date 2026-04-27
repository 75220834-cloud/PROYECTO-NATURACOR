<?php

namespace App\Jobs\Recommendation;

use App\Services\Recommendation\PerfilSaludService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bloque 3 — Job nocturno que reconstruye el perfil de afinidad
 * (cliente_perfil_afinidad) de todos los clientes con señal útil.
 *
 * Objetivo: mover la carga del primer request del día (cuando el motor
 * recalcula on-demand) al cron nocturno, mejorando la latencia perceptible
 * en horario laboral del POS.
 *
 * Selecciona "clientes activos" como aquellos que cumplen al menos UNA de:
 *  (a) Tienen al menos una venta completada dentro de la ventana
 *      `recommendaciones.ventana_dias` (señal observada).
 *  (b) Tienen al menos un padecimiento declarado en `cliente_padecimientos`
 *      (señal declarada — caso clave del BUG 2 FIX).
 *
 * Idempotencia y seguridad:
 *  - `PerfilSaludService::reconstruirPerfil` ya hace delete + insert en
 *    transacción por cliente, así que ejecutar el job dos veces no duplica.
 *  - Una excepción procesando un cliente NO aborta el batch: se loguea y
 *    se continúa con el siguiente. Esto evita que un dato corrupto en un
 *    cliente bloquee la actualización del resto.
 */
class ReconstruirPerfilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    /**
     * @param  int|null  $chunkSize  Tamaño de página al iterar clientes
     *                               (null → usa config recommendaciones.jobs.perfiles_chunk).
     */
    public function __construct(public ?int $chunkSize = null)
    {
    }

    public function handle(PerfilSaludService $service): void
    {
        $inicio = microtime(true);
        $chunk = max(50, (int) ($this->chunkSize ?? config('recommendaciones.jobs.perfiles_chunk', 200)));
        $ventanaDias = max(1, (int) config('recommendaciones.ventana_dias', 365));
        $desde = now()->subDays($ventanaDias);

        $procesados = 0;
        $errores = 0;
        $vacios = 0;

        $query = $this->clientesActivosQuery($desde);

        $query->chunkById($chunk, function ($filas) use ($service, &$procesados, &$errores, &$vacios) {
            foreach ($filas as $row) {
                $clienteId = (int) $row->id;
                try {
                    $service->reconstruirPerfil($clienteId);
                    $existePerfil = DB::table('cliente_perfil_afinidad')
                        ->where('cliente_id', $clienteId)
                        ->exists();

                    $procesados++;
                    if (! $existePerfil) {
                        $vacios++;
                    }
                } catch (Throwable $e) {
                    $errores++;
                    Log::warning('ReconstruirPerfilesJob: error procesando cliente', [
                        'cliente_id' => $clienteId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $duracionSeg = round(microtime(true) - $inicio, 3);

        Log::info('ReconstruirPerfilesJob completado', [
            'clientes_procesados' => $procesados,
            'perfiles_vacios' => $vacios,
            'errores' => $errores,
            'duracion_seg' => $duracionSeg,
            'ventana_dias' => $ventanaDias,
        ]);
    }

    /**
     * Selecciona clientes con señal útil: con compras recientes en ventana
     * O con padecimientos declarados. Excluye soft-deleted.
     */
    protected function clientesActivosQuery($desde)
    {
        return DB::table('clientes as c')
            ->whereNull('c.deleted_at')
            ->where(function ($q) use ($desde) {
                $q->whereExists(function ($sub) use ($desde) {
                    $sub->select(DB::raw(1))
                        ->from('ventas')
                        ->whereColumn('ventas.cliente_id', 'c.id')
                        ->where('ventas.estado', 'completada')
                        ->where('ventas.created_at', '>=', $desde);
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('cliente_padecimientos')
                        ->whereColumn('cliente_padecimientos.cliente_id', 'c.id');
                });
            })
            ->select('c.id')
            ->orderBy('c.id');
    }
}
