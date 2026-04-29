<?php

namespace Database\Factories;

use App\Models\DetalleVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleVentaFactory extends Factory
{
    protected $model = DetalleVenta::class;

    public function definition(): array
    {
        return [
            'venta_id'        => null,
            'producto_id'     => null,
            'nombre_producto' => $this->faker->words(3, true),
            'precio_unitario' => 59.00,
            'cantidad'        => 1,
            'descuento'       => 0.00,
            'subtotal'        => 59.00,
            'es_gratis'       => false,
        ];
    }
}
