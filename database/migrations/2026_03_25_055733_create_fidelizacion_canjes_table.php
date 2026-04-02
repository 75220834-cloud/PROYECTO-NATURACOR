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
            $table->enum('tipo_regla', ['regla1_250', 'regla2_litro40', 'regla2_litro80']);
            $table->decimal('valor_premio', 10, 2)->default(0);
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelizacion_canjes');
    }
};
