<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloque 2 — Filtrado colaborativo basado en co-ocurrencia.
 *
 * Almacena pares de productos (a, b) que se han comprado juntos
 * en una ventana temporal, junto con sus scores de similitud
 * Jaccard y NPMI (Pointwise Mutual Information normalizado).
 *
 * Convención: producto_a_id < producto_b_id (par ordenado) para
 * evitar duplicados (a,b)/(b,a) y reducir el tamaño de la matriz a la mitad.
 *
 * Métricas:
 *  - Jaccard:   J(A,B) = co(A,B) / (n_A + n_B - co(A,B))                 ∈ [0,1]
 *  - NPMI:      NPMI(A,B) = log( P(A,B) / (P(A)·P(B)) ) / -log P(A,B)    ∈ [-1,1]
 *
 * El campo `score` denormaliza la métrica activa (configurable) para
 * permitir índice secundario y consultas con ORDER BY score DESC eficientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_coocurrencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_a_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('producto_b_id')->constrained('productos')->cascadeOnDelete();

            $table->unsignedInteger('co_count')->default(0)
                ->comment('Transacciones donde A y B aparecen juntos');
            $table->unsignedInteger('count_a')->default(0)
                ->comment('Apariciones de A en la ventana');
            $table->unsignedInteger('count_b')->default(0)
                ->comment('Apariciones de B en la ventana');
            $table->unsignedInteger('total_transacciones')->default(0)
                ->comment('N (denominador para PMI)');

            $table->decimal('score_jaccard', 10, 6)->default(0)
                ->comment('Jaccard similarity ∈ [0,1]');
            $table->decimal('score_npmi', 10, 6)->default(0)
                ->comment('NPMI ∈ [-1,1]');

            $table->string('metrica_principal', 16)->default('jaccard')
                ->comment('Métrica usada para el campo `score` (jaccard|npmi)');
            $table->decimal('score', 10, 6)->default(0)
                ->comment('Métrica activa denormalizada para queries rápidas');

            $table->unsignedSmallInteger('dias_ventana')->default(90)
                ->comment('Ventana temporal del cómputo (auditable)');
            $table->timestamp('computed_at')->nullable()
                ->comment('Timestamp del último cálculo masivo');

            $table->timestamps();

            $table->unique(['producto_a_id', 'producto_b_id'], 'uq_pcoo_par');
            $table->index(['producto_a_id', 'score'], 'ix_pcoo_a_score');
            $table->index(['producto_b_id', 'score'], 'ix_pcoo_b_score');
            $table->index('score', 'ix_pcoo_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_coocurrencias');
    }
};
