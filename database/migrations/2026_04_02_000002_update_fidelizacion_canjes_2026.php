<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de actualización vacía — las columnas de fidelización 2026
 * ya fueron incorporadas directamente en la migración base de fidelizacion_canjes.
 * Este archivo se conserva para no romrar el historial de migraciones en producción.
 * En producción (MySQL): ejecutar manualmente si la tabla ya existe con estructura antigua.
 */
return new class extends Migration {
    public function up(): void
    {
        // Solo ejecutar en producción si la tabla ya existía sin las columnas nuevas
        if (!Schema::hasColumn('fidelizacion_canjes', 'entregado')) {
            Schema::table('fidelizacion_canjes', function (Blueprint $table) {
                $table->boolean('entregado')->default(false)->after('descripcion');
                $table->timestamp('entregado_at')->nullable()->after('entregado');
                $table->string('descripcion_premio')->nullable()->after('descripcion');
            });
        }
    }

    public function down(): void
    {
        // No hacer nada — las columnas son parte de la estructura base
    }
};
