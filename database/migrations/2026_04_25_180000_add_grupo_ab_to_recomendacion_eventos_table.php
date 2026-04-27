<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloque 4 — Experimento A/B documentado.
 *
 * Añade `grupo_ab` a recomendacion_eventos para etiquetar cada evento
 * con el grupo experimental al que pertenece el cliente al momento del
 * registro:
 *  - 'control'      : cliente NO recibió recomendaciones (grupo de control).
 *  - 'tratamiento'  : cliente recibió recomendaciones del motor híbrido.
 *  - 'sin_ab'       : sistema con A/B desactivado (legacy / pre-experimento).
 *
 * Diseño: string en lugar de enum para evitar problemas de portabilidad
 * MySQL/SQLite (tests usan SQLite). Validación a nivel de aplicación
 * (constantes en AbTestingService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recomendacion_eventos', function (Blueprint $table) {
            $table->string('grupo_ab', 20)->nullable()->after('sucursal_id')
                ->comment('control | tratamiento | sin_ab — para análisis A/B');
            $table->index(['grupo_ab', 'accion', 'created_at'], 'rec_eventos_grupo_ab_idx');
        });
    }

    public function down(): void
    {
        Schema::table('recomendacion_eventos', function (Blueprint $table) {
            $table->dropIndex('rec_eventos_grupo_ab_idx');
            $table->dropColumn('grupo_ab');
        });
    }
};
