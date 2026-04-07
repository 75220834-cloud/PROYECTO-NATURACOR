<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * BUG #1 FIX: Agregar tipos 'medio_litro_especial' y 'medio_litro_puro' al enum
 * de cordial_ventas.tipo que solo tenía 7 valores pero el modelo define 9.
 *
 * Compatible con MySQL (ALTER COLUMN MODIFY) y SQLite (usa string, no enum).
 */
return new class extends Migration {
    public function up(): void
    {
        // En MySQL: modificar la columna enum para incluir los 2 tipos nuevos
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `cordial_ventas` MODIFY `tipo` ENUM(
                'tienda_s3', 'tienda_s5', 'llevar_s3', 'llevar_s5',
                'litro_especial_s40', 'medio_litro_especial',
                'litro_puro_s80', 'medio_litro_puro',
                'invitado'
            ) NOT NULL");
        }
        // SQLite no usa enums reales, no necesita cambio
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `cordial_ventas` MODIFY `tipo` ENUM(
                'tienda_s3', 'tienda_s5', 'llevar_s3', 'llevar_s5',
                'litro_especial_s40', 'litro_puro_s80', 'invitado'
            ) NOT NULL");
        }
    }
};
