<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fidelizacion_canjes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();
            // String en lugar de enum para compatibilidad SQLite (tests)
            // Valores válidos: regla1_500, regla2_500_cordial
            $table->string('tipo_regla');
            $table->decimal('valor_premio', 10, 2)->default(0);
            $table->string('descripcion')->nullable();
            $table->string('descripcion_premio')->nullable(); // Descripción del premio 2026
            $table->boolean('entregado')->default(false);     // Si el empleado entregó el premio
            $table->timestamp('entregado_at')->nullable();    // Cuándo fue entregado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelizacion_canjes');
    }
};
