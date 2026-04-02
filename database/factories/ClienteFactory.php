<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre'              => fake()->firstName(),
            'apellido'            => fake()->lastName(),
            'dni'                 => fake()->unique()->numerify('########'),
            'telefono'            => fake()->numerify('9########'),
            'frecuente'           => false,
            'acumulado_naturales' => 0,
        ];
    }
}
