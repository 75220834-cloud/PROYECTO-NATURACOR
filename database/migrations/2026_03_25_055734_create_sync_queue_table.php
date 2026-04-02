<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('tabla'); // nombre de la tabla a sincronizar
            $table->unsignedBigInteger('registro_id');
            $table->enum('operacion', ['insert', 'update', 'delete']);
            $table->json('datos');
            $table->enum('estado', ['pendiente', 'sincronizado', 'error'])->default('pendiente');
            $table->text('error_mensaje')->nullable();
            $table->integer('intentos')->default(0);
            $table->timestamp('sincronizado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
