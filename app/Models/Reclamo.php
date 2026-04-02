<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reclamo extends Model
{
    use HasFactory;

    protected $table = 'reclamos';

    protected $fillable = [
        'cliente_id',
        'vendedor_id',
        'sucursal_id',
        'tipo',
        'descripcion',
        'estado',
        'resolucion',
        'escalado',
        'admin_resolutor_id',
    ];

    protected $casts = [
        'escalado' => 'boolean',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function adminResolutor()
    {
        return $this->belongsTo(User::class, 'admin_resolutor_id');
    }

    // Scopes útiles
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEscalados($query)
    {
        return $query->where('escalado', true);
    }

    public function scopeDeSucursal($query, int $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }
}
