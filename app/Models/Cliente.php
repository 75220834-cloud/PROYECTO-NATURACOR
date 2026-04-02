<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['dni', 'nombre', 'apellido', 'telefono', 'acumulado_naturales', 'frecuente'];
    protected $casts = ['acumulado_naturales' => 'decimal:2', 'frecuente' => 'boolean'];

    public function ventas() { return $this->hasMany(Venta::class); }
    public function canjes() { return $this->hasMany(FidelizacionCanje::class); }

    public function nombreCompleto(): string { return trim("{$this->nombre} {$this->apellido}"); }

    public function puedeReclamarPremio(): bool
    {
        $umbral = config('naturacor.fidelizacion_monto', 250);
        return $this->acumulado_naturales >= $umbral;
    }
}
