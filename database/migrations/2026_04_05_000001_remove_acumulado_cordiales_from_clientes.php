<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'acumulado_cordiales')) {
                $table->dropColumn('acumulado_cordiales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('acumulado_cordiales', 10, 2)->default(0)->after('acumulado_naturales');
        });
    }
};
