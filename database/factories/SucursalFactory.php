<?php

namespace Database\Factories;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    protected $model = Sucursal::class;

    public function definition(): array
    {
        return [
            'nombre'    => 'Sucursal ' . fake()->city(),
            'direccion' => fake()->address(),
            'telefono'  => fake()->numerify('0#-#######'),
            'ruc'       => fake()->numerify('20#########'),
            'activa'    => true,
        ];
    }
}
