<?php

namespace Database\Factories;

use App\Models\CajaSesion;
use Illuminate\Database\Eloquent\Factories\Factory;

class CajaSesionFactory extends Factory
{
    protected $model = CajaSesion::class;

    public function definition(): array
    {
        return [
            'monto_inicial' => 100.00,
            'estado'        => 'abierta',
            'apertura_at'   => now(),
        ];
    }

    public function cerrada(): static
    {
        return $this->state([
            'estado'            => 'cerrada',
            'cierre_at'         => now(),
            'monto_real_cierre' => fake()->randomFloat(2, 100, 1000),
        ]);
    }
}
