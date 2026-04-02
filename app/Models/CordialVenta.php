<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CordialVenta extends Model
{
    use HasFactory;
    protected $fillable = ['venta_id', 'tipo', 'precio', 'cantidad', 'es_invitado', 'empleado_invita_id', 'motivo_invitado'];
    protected $casts = ['precio' => 'decimal:2', 'es_invitado' => 'boolean'];

    public function venta() { return $this->belongsTo(Venta::class); }
    public function empleadoInvita() { return $this->belongsTo(User::class, 'empleado_invita_id'); }

    public static $precios = [
        'tienda_s3' => 3, 'tienda_s5' => 5,
        'llevar_s3' => 3, 'llevar_s5' => 5,
        'litro_especial_s40' => 40, 'litro_puro_s80' => 80,
        'invitado' => 0,
    ];

    public static $labels = [
        'tienda_s3' => 'Consumo en tienda S/3',
        'tienda_s5' => 'Consumo en tienda S/5',
        'llevar_s3' => 'Para llevar S/3',
        'llevar_s5' => 'Para llevar S/5',
        'litro_especial_s40' => 'Litro especial S/40',
        'litro_puro_s80' => 'Litro puro S/80',
        'invitado' => 'Invitado (gratis)',
    ];
}
