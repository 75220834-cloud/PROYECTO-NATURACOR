<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque 5 — Snapshot del modelo SES por producto/sucursal.
 *
 * Persiste el OUTPUT del DemandaForecastService:
 *   - prediccion: unidades esperadas para `semana_objetivo`.
 *   - intervalo_inf / intervalo_sup: ±z·σ_residuos (CI naive 95%).
 *   - mae / mape: error in-sample para auditoría académica.
 *
 * El widget del dashboard cruza esta tabla con productos.stock para detectar
 * "productos en riesgo" (prediccion > stock_actual) y guiar la reposición.
 */
class ProductoPrediccionDemanda extends Model
{
    protected $table = 'producto_prediccion_demanda';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'semana_objetivo',
        'prediccion',
        'intervalo_inf',
        'intervalo_sup',
        'alpha_usado',
        'modelo',
        'n_observaciones',
        'mae',
        'mape',
        'computed_at',
    ];

    protected $casts = [
        // 'date:Y-m-d' fuerza serialización consistente sin componente de hora.
        // Sin el formato explícito, SQLite guarda 'YYYY-MM-DD HH:MM:SS' y luego
        // los WHERE con string corto fallan en silencio (UNIQUE → falsos duplicados).
        'semana_objetivo' => 'date:Y-m-d',
        'prediccion'      => 'decimal:2',
        'intervalo_inf'   => 'decimal:2',
        'intervalo_sup'   => 'decimal:2',
        'alpha_usado'     => 'decimal:3',
        'n_observaciones' => 'integer',
        'mae'             => 'decimal:4',
        'mape'            => 'decimal:4',
        'computed_at'     => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
