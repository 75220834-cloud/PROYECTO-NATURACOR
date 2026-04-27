<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecomendacionEvento extends Model
{
    protected $table = 'recomendacion_eventos';

    public const ACCION_MOSTRADA = 'mostrada';

    public const ACCION_CLIC = 'clic';

    public const ACCION_AGREGADA = 'agregada';

    public const ACCION_COMPRADA = 'comprada';

    protected $fillable = [
        'reco_sesion_id',
        'cliente_id',
        'producto_id',
        'score',
        'razones',
        'accion',
        'posicion',
        'venta_id',
        'user_id',
        'sucursal_id',
        'grupo_ab',
    ];

    protected $casts = [
        'razones' => 'array',
        'score' => 'decimal:4',
        'posicion' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
