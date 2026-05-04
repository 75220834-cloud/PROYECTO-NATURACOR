<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'stock', 'stock_minimo',
        'tipo', 'frecuente', 'activo', 'imagen', 'sucursal_id', 'codigo_barras',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'frecuente' => 'boolean',
        'activo' => 'boolean',
    ];

    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function detalleVentas() { return $this->hasMany(DetalleVenta::class); }
    public function enfermedades() { return $this->belongsToMany(Enfermedad::class, 'enfermedad_producto')->withPivot('instrucciones', 'orden')->withTimestamps(); }
    public function valoraciones() { return $this->hasMany(Valoracion::class); }
    public function valoracionesAprobadas() { return $this->hasMany(Valoracion::class)->where('aprobada', true); }

    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }
}
