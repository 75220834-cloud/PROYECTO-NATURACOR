<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cliente_padecimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('enfermedad_id')->constrained('enfermedades')->onDelete('cascade');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['cliente_id', 'enfermedad_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('cliente_padecimientos');
    }
};
