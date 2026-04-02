<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CajaMovimiento extends Model
{
    use HasFactory;
    protected $table = 'caja_movimientos';
    protected $fillable = ['caja_sesion_id', 'user_id', 'tipo', 'monto', 'descripcion', 'metodo_pago'];

    protected $casts = ['monto' => 'decimal:2'];

    public function cajaSesion() { return $this->belongsTo(CajaSesion::class); }
    public function empleado() { return $this->belongsTo(User::class, 'user_id'); }
}
