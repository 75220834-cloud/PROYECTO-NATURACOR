<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo inteligente (Fase 1): perfil de afinidad cliente ↔ enfermedades del recetario.
 * No modifica tablas existentes; solo añade esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_perfil_afinidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('enfermedad_id')->constrained('enfermedades')->cascadeOnDelete();
            $table->decimal('score', 10, 6)->default(0)->comment('Score normalizado [0,1] por cliente');
            $table->unsignedInteger('evidencia_count')->default(0)->comment('Líneas de detalle que aportaron señal');
            $table->timestamp('ultima_evidencia_at')->nullable();
            $table->timestamp('computed_at')->nullable()->comment('Momento del último cálculo');
            $table->timestamps();

            $table->unique(['cliente_id', 'enfermedad_id']);
            $table->index(['cliente_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_perfil_afinidad');
    }
};
