<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReiniciarFidelizacion extends Command
{
    /**
     * Reinicia los acumulados de fidelización de todos los clientes.
     * Debe ejecutarse automáticamente el 01/01/2027.
     * Uso manual: php artisan fidelizacion:reiniciar [--force]
     */
    protected $signature = 'fidelizacion:reiniciar {--force : Omite la verificación de fecha}';
    protected $description = 'Reinicia los acumulados de fidelización (acumulado_naturales) de todos los clientes al inicio del nuevo año.';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $hoy     = now()->format('Y-m-d');
            $finProg = config('naturacor.fidelizacion_fin', '2026-12-31');

            if ($hoy <= $finProg) {
                $this->warn("El programa de fidelización aún está vigente hasta {$finProg}.");
                $this->warn("Si deseas forzar el reinicio usa: php artisan fidelizacion:reiniciar --force");
                return self::FAILURE;
            }
        }

        if (!$this->confirm('¿Confirmas reiniciar TODOS los acumulados de fidelización? Esta acción no se puede deshacer.', false)) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $totalClientes = Cliente::reiniciarAcumulados();

        $this->info("✅ Acumulados reiniciados para {$totalClientes} clientes.");
        Log::info("fidelizacion:reiniciar ejecutado. Clientes reiniciados: {$totalClientes}");

        return self::SUCCESS;
    }
}
