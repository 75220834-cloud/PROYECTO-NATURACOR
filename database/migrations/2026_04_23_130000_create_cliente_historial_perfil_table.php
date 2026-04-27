<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_historial_perfil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('enfermedad_id')->constrained('enfermedades')->cascadeOnDelete();
            $table->decimal('score', 10, 6)->default(0);
            $table->unsignedInteger('evidencia_count')->default(0);
            $table->timestamp('fecha_computacion');
            $table->timestamps();

            $table->index(['cliente_id', 'fecha_computacion']);
            $table->index(['enfermedad_id', 'fecha_computacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_historial_perfil');
    }
};
