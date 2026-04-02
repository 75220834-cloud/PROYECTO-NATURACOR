<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caja_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // empleado que abre
            $table->unsignedBigInteger('sucursal_id')->nullable();

            $table->decimal('monto_inicial', 10, 2)->default(0);
            $table->decimal('monto_real_cierre', 10, 2)->nullable();
            $table->decimal('total_efectivo', 10, 2)->default(0);
            $table->decimal('total_yape', 10, 2)->default(0);
            $table->decimal('total_plin', 10, 2)->default(0);
            $table->decimal('total_otros', 10, 2)->default(0);
            $table->decimal('total_esperado', 10, 2)->default(0);
            $table->decimal('diferencia', 10, 2)->default(0);
            $table->timestamp('apertura_at');
            $table->timestamp('cierre_at')->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('notas_cierre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_sesiones');
    }
};
