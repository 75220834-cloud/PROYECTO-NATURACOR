<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_boleta', 'cliente_id', 'user_id', 'sucursal_id',
        'subtotal', 'igv', 'total', 'descuento_total', 'metodo_pago',
        'metodos_pago_detalle', 'estado', 'incluir_igv', 'notas', 'caja_sesion_id',
        'grupo_ab',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2', 'igv' => 'decimal:2', 'total' => 'decimal:2',
        'descuento_total' => 'decimal:2', 'incluir_igv' => 'boolean',
        'metodos_pago_detalle' => 'array',
    ];

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function empleado() { return $this->belongsTo(User::class, 'user_id'); }
    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function detalles() { return $this->hasMany(DetalleVenta::class); }
    public function cordialVentas() { return $this->hasMany(CordialVenta::class); }
    public function cajaSesion() { return $this->belongsTo(CajaSesion::class); }
    public function canjes() { return $this->hasMany(FidelizacionCanje::class); }

    public function generarNumeroBoleta(): string
    {
        $ultimo = static::whereNotNull('numero_boleta')->orderBy('id', 'desc')->first();
        $numero = $ultimo ? (intval(substr($ultimo->numero_boleta, -6)) + 1) : 1;
        return 'B001-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}
