<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->string('nombre_producto'); // snapshot del nombre al momento de la venta
            $table->decimal('precio_unitario', 10, 2);
            $table->integer('cantidad');
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->boolean('es_gratis')->default(false); // para fidelización
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
