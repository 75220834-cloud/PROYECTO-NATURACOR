<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perfil materializado: afinidad numérica del cliente hacia enfermedades/condiciones del recetario,
 * inferida solo desde historial de compras y la tabla pivote enfermedad_producto.
 */
class ClientePerfilAfinidad extends Model
{
    protected $table = 'cliente_perfil_afinidad';

    protected $fillable = [
        'cliente_id',
        'enfermedad_id',
        'score',
        'evidencia_count',
        'ultima_evidencia_at',
        'computed_at',
    ];

    protected $casts = [
        'score' => 'decimal:6',
        'evidencia_count' => 'integer',
        'ultima_evidencia_at' => 'datetime',
        'computed_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function enfermedad(): BelongsTo
    {
        return $this->belongsTo(Enfermedad::class);
    }
}
