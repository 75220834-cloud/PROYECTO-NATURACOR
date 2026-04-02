<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DetalleVenta extends Model
{
    use HasFactory;
    protected $fillable = ['venta_id', 'producto_id', 'nombre_producto', 'precio_unitario', 'cantidad', 'descuento', 'subtotal', 'es_gratis'];
    protected $casts = ['precio_unitario' => 'decimal:2', 'descuento' => 'decimal:2', 'subtotal' => 'decimal:2', 'es_gratis' => 'boolean'];

    public function venta() { return $this->belongsTo(Venta::class); }
    public function producto() { return $this->belongsTo(Producto::class); }
}
