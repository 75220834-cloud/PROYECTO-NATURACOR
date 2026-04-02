<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 20)->unique();
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->decimal('acumulado_naturales', 10, 2)->default(0); // para fidelización regla 1
            $table->boolean('frecuente')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
