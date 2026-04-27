<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas del módulo de recomendación (tesis / evaluación).
 * Registro append-only de eventos: mostrada, clic, agregada, comprada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recomendacion_eventos', function (Blueprint $table) {
            $table->id();
            $table->uuid('reco_sesion_id')->nullable()->index();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->decimal('score', 12, 4)->nullable()->comment('Score devuelto por el motor al momento del evento');
            $table->json('razones')->nullable();
            $table->string('accion', 20)->index();
            $table->unsignedTinyInteger('posicion')->nullable()->comment('Ranking 1..k en la lista mostrada');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('sucursal_id')->nullable()->index();
            $table->timestamps();

            $table->index(['cliente_id', 'accion', 'created_at']);
            $table->index(['producto_id', 'accion', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recomendacion_eventos');
    }
};
