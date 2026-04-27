<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReiniciarFidelizacion extends Command
{
    /**
     * Comando legado: desde la refactorización, el acumulado es permanente.
     */
    protected $signature = 'fidelizacion:reiniciar {--force : Omite la verificación de fecha}';
    protected $description = 'Comando legado: el acumulado de fidelización ahora es permanente y no se reinicia.';

    public function handle(): int
    {
        $this->warn('Este comando quedó deshabilitado: el acumulado de fidelización ahora es permanente.');
        $this->line('No se realizó ningún cambio en clientes.');
        Log::info('fidelizacion:reiniciar invocado en modo legado (sin cambios).');

        return self::SUCCESS;
    }
}
