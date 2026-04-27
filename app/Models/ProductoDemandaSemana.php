<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque 5 — Histórico semanal de demanda por producto/sucursal.
 *
 * Una fila representa "unidades vendidas de producto P en sucursal S
 * durante la semana ISO X del año Y". Es el INPUT del modelo SES.
 * El job ActualizarDemandaJob mantiene la tabla idempotentemente.
 */
class ProductoDemandaSemana extends Model
{
    protected $table = 'producto_demanda_semana';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'anio',
        'semana_iso',
        'semana_inicio',
        'unidades_vendidas',
    ];

    protected $casts = [
        'anio'              => 'integer',
        'semana_iso'        => 'integer',
        // 'date:Y-m-d': consistencia entre INSERT y WHERE en drivers que
        // guardan como TEXT (SQLite). MySQL DATE no se ve afectado.
        'semana_inicio'     => 'date:Y-m-d',
        'unidades_vendidas' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
