<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('vendedor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->enum('tipo', ['producto', 'servicio', 'otro'])->default('otro');
            $table->text('descripcion');
            $table->enum('estado', ['pendiente', 'en_proceso', 'resuelto'])->default('pendiente');
            $table->text('resolucion')->nullable();
            $table->boolean('escalado')->default(false);
            $table->foreignId('admin_resolutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
    }
};
