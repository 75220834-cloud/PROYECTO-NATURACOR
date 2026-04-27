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
    public function historialPerfil()
    {
        return $this->hasMany(ClienteHistorialPerfil::class);
    }

    /** Perfil de afinidad hacia enfermedades del recetario (módulo de recomendación). */
    public function perfilAfinidad()
    {
        return $this->hasMany(ClientePerfilAfinidad::class);
    }

    public function nombreCompleto(): string { return trim("{$this->nombre} {$this->apellido}"); }

    /** Regla 1 — Naturales: umbral configurable */
    public function puedeReclamarPremio(): bool
    {
        return $this->premiosTeoricosDisponibles() > 0;
    }

    public function premiosTeoricosTotales(): int
    {
        $umbral = max(1, (int) config('naturacor.fidelizacion_monto', 500));
        $acumulado = (float) $this->acumulado_naturales;

        return (int) floor($acumulado / $umbral);
    }

    public function premiosEmitidosTotales(): int
    {
        if ($this->relationLoaded('canjes')) {
            return $this->canjes->count();
        }

        return $this->canjes()->count();
    }

    public function premiosTeoricosDisponibles(): int
    {
        return max(0, $this->premiosTeoricosTotales() - $this->premiosEmitidosTotales());
    }



    /** Mantener por compatibilidad; el acumulado ahora es permanente. */
    public static function reiniciarAcumulados(): int
    {
        return 0;
    }

    public function padecimientos() {
    return $this->hasMany(ClientePadecimiento::class);
    }
}
