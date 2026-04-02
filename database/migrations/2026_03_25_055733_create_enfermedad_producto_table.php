<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enfermedad_producto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enfermedad_id');
            $table->unsignedBigInteger('producto_id');
            $table->text('instrucciones')->nullable(); // cómo tomar el producto
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->unique(['enfermedad_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enfermedad_producto');
    }
};
