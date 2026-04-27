<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteHistorialPerfil extends Model
{
    protected $table = 'cliente_historial_perfil';

    protected $fillable = [
        'cliente_id',
        'enfermedad_id',
        'score',
        'evidencia_count',
        'fecha_computacion',
    ];

    protected $casts = [
        'score' => 'decimal:6',
        'evidencia_count' => 'integer',
        'fecha_computacion' => 'datetime',
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
