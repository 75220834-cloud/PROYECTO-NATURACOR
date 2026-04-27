<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloque 5 — Snapshot del modelo de pronóstico de demanda.
 *
 * Persiste la PREDICCIÓN del modelo SES (Simple Exponential Smoothing) para
 * la próxima semana ISO, junto con su error histórico (MAE, MAPE) e
 * intervalos de confianza aproximados, para auditoría académica.
 *
 * Diseño:
 *   - Un registro por (producto, sucursal, semana_objetivo). El job hace
 *     upsert sobre esa clave para que el dashboard siempre muestre la
 *     última versión sin acumular basura.
 *   - alpha_usado y modelo se guardan EXPLÍCITAMENTE: si mañana cambiamos
 *     SES → Holt-Winters, el dashboard puede etiquetar correctamente.
 *   - mae / mape calculados sobre el historial in-sample (limitación SES
 *     mencionada en tesis); para muestras < 4 puntos quedan NULL para no
 *     reportar métricas inestables.
 *   - intervalo_inf / intervalo_sup ≈ predicción ± z·sd_residuos.
 *     Aproximación honesta para SES; en el paper se cita como "naive 95% CI".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_prediccion_demanda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedBigInteger('sucursal_id')->nullable()->index();
            $table->date('semana_objetivo')->comment('Lunes de la semana cuya demanda se predice');
            $table->decimal('prediccion', 10, 2)->comment('Unidades pronosticadas (>=0)');
            $table->decimal('intervalo_inf', 10, 2)->nullable();
            $table->decimal('intervalo_sup', 10, 2)->nullable();
            $table->decimal('alpha_usado', 4, 3);
            $table->string('modelo', 30)->default('SES');
            $table->unsignedSmallInteger('n_observaciones')->default(0)
                ->comment('Cantidad de semanas usadas para entrenar');
            $table->decimal('mae', 10, 4)->nullable()->comment('Mean Absolute Error in-sample');
            $table->decimal('mape', 8, 4)->nullable()->comment('Mean Absolute Percent Error in-sample (0..1)');
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['producto_id', 'sucursal_id', 'semana_objetivo'],
                'ppd_unique_pss'
            );
            $table->index(['sucursal_id', 'semana_objetivo'], 'ppd_idx_dashboard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_prediccion_demanda');
    }
};
