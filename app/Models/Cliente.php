<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dni', 'nombre', 'apellido', 'telefono',
        'acumulado_naturales', 'frecuente',
    ];
    protected $casts = [
        'acumulado_naturales'  => 'decimal:2',
        'frecuente'            => 'boolean',
    ];

    public function ventas() { return $this->hasMany(Venta::class); }
    public function canjes() { return $this->hasMany(FidelizacionCanje::class); }

    public function nombreCompleto(): string { return trim("{$this->nombre} {$this->apellido}"); }

    /** Regla 1 — Naturales: umbral S/500 (vigente 2026) */
    public function puedeReclamarPremio(): bool
    {
        $umbral = config('naturacor.fidelizacion_monto', 500);
        return (float) $this->acumulado_naturales >= $umbral;
    }



    /** Reinicio anual de acumulados (se ejecuta el 01/01/2027) */
    public static function reiniciarAcumulados(): int
    {
        return static::query()->update([
            'acumulado_naturales'  => 0,
        ]);
    }
}
