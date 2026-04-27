<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque 2 — Par (a, b) de productos co-comprados en una ventana temporal,
 * con sus métricas de similitud Jaccard y NPMI.
 *
 * Convención del par: producto_a_id < producto_b_id (par ordenado).
 * Por eso al consultar "vecinos de X" se debe filtrar en ambas columnas.
 *
 * Esta tabla se reconstruye periódicamente (offline) por
 * {@see \App\Services\Recommendation\CoocurrenciaService::recomputar()}.
 */
class ProductoCoocurrencia extends Model
{
    protected $table = 'producto_coocurrencias';

    protected $fillable = [
        'producto_a_id',
        'producto_b_id',
        'co_count',
        'count_a',
        'count_b',
        'total_transacciones',
        'score_jaccard',
        'score_npmi',
        'metrica_principal',
        'score',
        'dias_ventana',
        'computed_at',
    ];

    protected $casts = [
        'co_count' => 'integer',
        'count_a' => 'integer',
        'count_b' => 'integer',
        'total_transacciones' => 'integer',
        'score_jaccard' => 'decimal:6',
        'score_npmi' => 'decimal:6',
        'score' => 'decimal:6',
        'dias_ventana' => 'integer',
        'computed_at' => 'datetime',
    ];

    public function productoA(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_a_id');
    }

    public function productoB(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_b_id');
    }
}
