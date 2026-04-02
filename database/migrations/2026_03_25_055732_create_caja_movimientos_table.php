<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja_sesion_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->decimal('monto', 10, 2);
            $table->string('descripcion');
            $table->enum('metodo_pago', ['efectivo', 'yape', 'plin', 'otro'])->default('efectivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
