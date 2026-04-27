<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloque 4 — Experimento A/B documentado.
 *
 * Añade `grupo_ab` a ventas para que el análisis estadístico (Welch t-test)
 * pueda comparar **ticket promedio control vs tratamiento** sin depender
 * de la atribución por evento (que tiene su propia lookback de 72h).
 *
 * El grupo se "estampa" en el momento de crear la venta usando la misma
 * función de asignación que usa el RecomendacionController, garantizando
 * consistencia: si el cliente fue control durante toda su sesión, su
 * eventual venta queda etiquetada también como control.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('grupo_ab', 20)->nullable()->after('caja_sesion_id')
                ->comment('control | tratamiento | sin_ab — A/B testing recomendador');
            $table->index(['grupo_ab', 'estado', 'created_at'], 'ventas_grupo_ab_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex('ventas_grupo_ab_idx');
            $table->dropColumn('grupo_ab');
        });
    }
};
