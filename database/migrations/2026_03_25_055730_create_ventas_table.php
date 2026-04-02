<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_boleta')->unique()->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // empleado
            $table->unsignedBigInteger('sucursal_id')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('descuento_total', 10, 2)->default(0);
            $table->enum('metodo_pago', ['efectivo', 'yape', 'plin', 'tarjeta', 'otro'])->default('efectivo');
            $table->json('metodos_pago_detalle')->nullable(); // para pagos mixtos {efectivo: X, yape: Y}
            $table->enum('estado', ['completada', 'anulada', 'pendiente'])->default('completada');
            $table->boolean('incluir_igv')->default(true);
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('caja_sesion_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
