<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CajaSesion extends Model
{
    use HasFactory;

    protected $table = 'caja_sesiones';

    protected $fillable = [

        'user_id', 'sucursal_id', 'monto_inicial', 'monto_real_cierre',
        'total_efectivo', 'total_yape', 'total_plin', 'total_otros',
        'total_esperado', 'diferencia', 'apertura_at', 'cierre_at', 'estado', 'notas_cierre',
    ];

    protected $casts = [
        'apertura_at' => 'datetime', 'cierre_at' => 'datetime',
        'monto_inicial' => 'decimal:2', 'monto_real_cierre' => 'decimal:2',
        'total_efectivo' => 'decimal:2', 'total_yape' => 'decimal:2',
        'total_plin' => 'decimal:2', 'total_otros' => 'decimal:2',
        'total_esperado' => 'decimal:2', 'diferencia' => 'decimal:2',
    ];

    public function empleado() { return $this->belongsTo(User::class, 'user_id'); }
    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function movimientos() { return $this->hasMany(CajaMovimiento::class); }
    public function ventas() { return $this->hasMany(Venta::class); }

    public function estaAbierta(): bool { return $this->estado === 'abierta'; }
}
