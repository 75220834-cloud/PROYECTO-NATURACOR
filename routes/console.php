<?php

use Illuminate\Support\Facades\DB;

/*
 * Limpiar todas las ventas y datos relacionados para empezar de cero.
 *
 * Ejecutar: php artisan limpiar:ventas
 */

use Illuminate\Support\Facades\Artisan;

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
