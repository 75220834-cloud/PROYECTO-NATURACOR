<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FidelizacionCanje extends Model
{
    use HasFactory;
    protected $table = 'fidelizacion_canjes';
    protected $fillable = [
        'cliente_id', 'venta_id', 'producto_id',
        'tipo_regla', 'valor_premio', 'descripcion',
        'descripcion_premio', 'entregado', 'entregado_at',
    ];

    protected $casts = [
        'valor_premio' => 'decimal:2',
        'entregado'    => 'boolean',
        'entregado_at' => 'datetime',
    ];

    /** Tipos de regla válidos para 2026 */
    public const REGLA_NATURALES = 'regla1_500';

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function venta()   { return $this->belongsTo(Venta::class); }
    public function producto() { return $this->belongsTo(Producto::class); }

    /** Scope: premios aún no entregados */
    public function scopePendientes($query)
    {
        return $query->where('entregado', false);
    }
}
