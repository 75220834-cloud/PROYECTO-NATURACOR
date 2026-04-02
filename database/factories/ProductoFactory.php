<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre'      => fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'precio'      => fake()->randomFloat(2, 1, 200),
            'stock'       => fake()->numberBetween(10, 500),
            'stock_minimo'=> 5,
            'tipo'        => fake()->randomElement(['natural', 'cordial']),
            'frecuente'   => false,
            'activo'      => true,
        ];
    }

    public function frecuente(): static
    {
        return $this->state(['frecuente' => true, 'tipo' => 'cordial']);
    }

    public function cordial(): static
    {
        return $this->state(['tipo' => 'cordial', 'stock' => 9999]);
    }
}
