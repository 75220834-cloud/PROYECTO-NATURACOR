<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cordial_ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            // tipo: tienda_s3, tienda_s5, llevar_s3, llevar_s5, litro_especial_s40, litro_puro_s80, invitado
            $table->enum('tipo', ['tienda_s3', 'tienda_s5', 'llevar_s3', 'llevar_s5', 'litro_especial_s40', 'litro_puro_s80', 'invitado']);
            $table->decimal('precio', 10, 2)->default(0);
            $table->integer('cantidad')->default(1);
            $table->boolean('es_invitado')->default(false);
            $table->unsignedBigInteger('empleado_invita_id')->nullable();
            $table->string('motivo_invitado')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cordial_ventas');
    }
};
