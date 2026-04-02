<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->enum('tipo', ['natural', 'cordial'])->default('natural');
            $table->boolean('frecuente')->default(false); // botones rápidos
            $table->boolean('activo')->default(true);
            $table->string('imagen')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
