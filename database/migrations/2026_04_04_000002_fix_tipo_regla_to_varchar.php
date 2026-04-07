<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * FIX: En producción MySQL, tipo_regla puede haber sido creado como ENUM
 * en una migración anterior. Lo cambiamos a VARCHAR(255) para aceptar
 * valores como 'regla1_500' y 'regla2_500_cordial'.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Cambiar tipo_regla de ENUM a VARCHAR(255)
            DB::statement("ALTER TABLE `fidelizacion_canjes` MODIFY `tipo_regla` VARCHAR(255) NOT NULL");
        }
    }

    public function down(): void
    {
        // No revertir — el tipo VARCHAR es más flexible
    }
};
