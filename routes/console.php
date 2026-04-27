<?php

use App\Jobs\Recommendation\ActualizarDemandaJob;
use App\Jobs\Recommendation\ReconstruirCoocurrenciaJob;
use App\Jobs\Recommendation\ReconstruirPerfilesJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
 * Limpiar todas las ventas y datos relacionados para empezar de cero.
 *
 * Ejecutar: php artisan limpiar:ventas
 */

Artisan::command('limpiar:ventas', function () {
    if (!$this->confirm('⚠️ ¿Seguro que deseas ELIMINAR TODAS las ventas, cordiales, canjes y logs? Esta acción NO se puede deshacer.')) {
        $this->info('Operación cancelada.');
        return;
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    // Eliminar en orden: primero las tablas dependientes
    $tablas = [
        'fidelizacion_canjes' => \App\Models\FidelizacionCanje::class,
        'cordial_ventas'      => \App\Models\CordialVenta::class,
        'detalle_ventas'      => \App\Models\DetalleVenta::class,
        'caja_movimientos'    => \App\Models\CajaMovimiento::class,
        'ventas'              => \App\Models\Venta::class,
        'caja_sesiones'       => \App\Models\CajaSesion::class,
        'logs_auditoria'      => \App\Models\LogAuditoria::class,
    ];

    foreach ($tablas as $nombre => $modelo) {
        $count = DB::table($nombre)->count();
        DB::table($nombre)->truncate();
        $this->line("  ✅ {$nombre}: {$count} registros eliminados");
    }

    // Reiniciar acumulados de clientes
    \App\Models\Cliente::query()->update([
        'acumulado_naturales' => 0,
    ]);
    $this->line("  ✅ Acumulados de clientes reiniciados a 0");

    // Restaurar stock de productos a valores originales (opcional: no tocamos stock)
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $this->newLine();
    $this->info('🧹 Limpieza completada. El sistema está listo para pruebas desde cero.');
})->purpose('Eliminar TODAS las ventas, cordiales, canjes y logs para empezar de cero');

/*
|--------------------------------------------------------------------------
| Bloque 3 — Schedule nocturno del motor de recomendación
|--------------------------------------------------------------------------
|
| Reconstruye offline las dos estructuras pesadas del híbrido para que el
| primer request del día encuentre el caché caliente y la latencia del POS
| no se vea impactada:
|
|   02:00 → ReconstruirPerfilesJob       (cliente_perfil_afinidad)
|   02:30 → ReconstruirCoocurrenciaJob   (producto_coocurrencias)
|
| Ambos jobs:
|   - withoutOverlapping(): si el anterior aún corre, no se dispara otro.
|   - onOneServer():        seguro en multi-server (no-op single server).
|
| Como ambos implementan ShouldQueue, el scheduler solo hace dispatch a la
| cola configurada (`recommendaciones.jobs.cola`) y NO se queda bloqueado
| esperando a que terminen. Por eso no necesitamos runInBackground() (que
| además no es compatible con Schedule::job()).
|
| Activación: requiere `php artisan schedule:run` cada minuto vía cron en
| Linux/cPanel, o `php artisan schedule:work` en Windows local. Ver
| `guia_despliegue_produccion.md` (sección "Cron del motor de recomendación").
|
| Para desactivar uno de los jobs sin tocar código:
|   REC_JOB_PERFILES_ENABLED=false   o   REC_JOB_COO_ENABLED=false
*/

if ((bool) config('recommendaciones.jobs.perfiles_enabled', true)) {
    Schedule::job(
        new ReconstruirPerfilesJob,
        (string) config('recommendaciones.jobs.cola', 'default'),
    )
        ->dailyAt((string) config('recommendaciones.jobs.perfiles_hora', '02:00'))
        ->name('recomendaciones-perfiles')
        ->withoutOverlapping()
        ->onOneServer();
}

if ((bool) config('recommendaciones.jobs.cooccurrencia_enabled', true)) {
    Schedule::job(
        new ReconstruirCoocurrenciaJob,
        (string) config('recommendaciones.jobs.cola', 'default'),
    )
        ->dailyAt((string) config('recommendaciones.jobs.cooccurrencia_hora', '02:30'))
        ->name('recomendaciones-cooccurrencia')
        ->withoutOverlapping()
        ->onOneServer();
}

/*
|--------------------------------------------------------------------------
| Bloque 5 — Schedule semanal del modelo de pronóstico de demanda (SES)
|--------------------------------------------------------------------------
|
| Una vez por semana (default lunes 03:00) recomputa el histórico
| `producto_demanda_semana` y las predicciones SES en
| `producto_prediccion_demanda`. Pesado pero corre offline; el dashboard
| consulta la tabla y NO recalcula al pintar el widget.
|
| Para desactivar sin tocar código: REC_JOB_DEMANDA_ENABLED=false.
| Para cambiar día u hora: REC_JOB_DEMANDA_DIA / REC_JOB_DEMANDA_HORA.
*/

if ((bool) config('recommendaciones.forecast.job_enabled', true)) {
    $diaSemana = max(0, min(6, (int) config('recommendaciones.forecast.job_dia_semana', 1)));

    Schedule::job(
        new ActualizarDemandaJob,
        (string) config('recommendaciones.jobs.cola', 'default'),
    )
        ->weeklyOn($diaSemana, (string) config('recommendaciones.forecast.job_hora', '03:00'))
        ->name('recomendaciones-demanda')
        ->withoutOverlapping()
        ->onOneServer();
}
