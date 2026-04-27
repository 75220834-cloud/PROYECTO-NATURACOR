<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloque 5 — Histórico semanal de demanda por producto/sucursal.
 *
 * Materializa, para cada combinación (producto, sucursal, año, semana ISO),
 * cuántas unidades se vendieron. Es el INPUT del modelo SES de pronóstico:
 *   - El job ActualizarDemandaJob recomputa este histórico a partir de
 *     detalle_ventas + ventas, manteniendo idempotencia mediante upsert.
 *   - El servicio DemandaForecastService lo consume ordenado para alimentar
 *     la recurrencia S_t = α·Y_t + (1-α)·S_{t-1}.
 *
 * Por qué semana ISO (semana_iso 1..53) en vez de fecha arbitraria:
 *   - Estabiliza la granularidad temporal (todos los productos en el mismo
 *     calendario), permite series alineables y joins triviales año-sobre-año.
 *   - Evita semanas a caballo entre meses que ensucian comparaciones.
 *
 * No usamos ENUM ni tipos exóticos para preservar portabilidad MySQL/SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_demanda_semana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedBigInteger('sucursal_id')->nullable()->index();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('semana_iso')->comment('1..53 según ISO-8601');
            $table->date('semana_inicio')->comment('Lunes de la semana ISO (para ordenar y plot)');
            $table->unsignedInteger('unidades_vendidas')->default(0);
            $table->timestamps();

            // Una sola fila por combinación: el job hace upsert sobre estas claves
            $table->unique(
                ['producto_id', 'sucursal_id', 'anio', 'semana_iso'],
                'pds_unique_pscw'
            );
            $table->index(['producto_id', 'sucursal_id', 'semana_inicio'], 'pds_idx_serie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_demanda_semana');
    }
};
