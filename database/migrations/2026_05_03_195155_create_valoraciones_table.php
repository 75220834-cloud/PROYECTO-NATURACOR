<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valoraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->string('nombre_cliente', 100);
            $table->tinyInteger('estrellas')->unsigned(); // 1-5
            $table->text('comentario')->nullable();
            $table->boolean('aprobada')->default(false);
            $table->timestamps();

            $table->index(['producto_id', 'aprobada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valoraciones');
    }
};
