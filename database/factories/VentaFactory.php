<?php

namespace Database\Factories;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'numero_boleta'  => 'B001-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 6, '0', STR_PAD_LEFT),
            'cliente_id'     => null,
            'user_id'        => null,
            'sucursal_id'    => null,
            'subtotal'       => 50.00,
            'igv'            => 9.00,
            'total'          => 59.00,
            'descuento_total'=> 0.00,
            'metodo_pago'    => 'efectivo',
            'estado'         => 'completada',
            'incluir_igv'    => true,
        ];
    }
}
