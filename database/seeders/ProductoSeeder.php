<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Productos por defecto de NATURACOR.
     * Se ejecuta con: php artisan db:seed --class=ProductoSeeder
     * Usa updateOrCreate para no duplicar si se ejecuta varias veces.
     */
    public function run(): void
    {
        $productos = [
            // ─── Cordiales ────────────────────────────────────
            [
                'nombre'       => 'Consumo en tienda S/3',
                'descripcion'  => 'Cordial para consumo en tienda, vaso estándar',
                'precio'       => 3.00,
                'stock'        => 9999,
                'stock_minimo' => 10,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => true,
            ],
            [
                'nombre'       => 'Consumo en tienda S/5',
                'descripcion'  => 'Cordial para consumo en tienda, vaso grande',
                'precio'       => 5.00,
                'stock'        => 9999,
                'stock_minimo' => 10,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => true,
            ],
            [
                'nombre'       => 'Para llevar S/3',
                'descripcion'  => 'Cordial para llevar, vaso estándar',
                'precio'       => 3.00,
                'stock'        => 9999,
                'stock_minimo' => 10,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => true,
            ],
            [
                'nombre'       => 'Para llevar S/5',
                'descripcion'  => 'Cordial para llevar, vaso grande',
                'precio'       => 5.00,
                'stock'        => 9999,
                'stock_minimo' => 10,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => true,
            ],
            [
                'nombre'       => 'Litro especial',
                'descripcion'  => 'Botella de 1 litro de cordial especial',
                'precio'       => 40.00,
                'stock'        => 100,
                'stock_minimo' => 5,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => true,
            ],
            [
                'nombre'       => 'Medio Litro Especial',
                'descripcion'  => 'Botella de medio litro de cordial especial',
                'precio'       => 20.00,
                'stock'        => 100,
                'stock_minimo' => 5,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => true,
            ],
            [
                'nombre'       => 'Litro puro',
                'descripcion'  => 'Botella de 1 litro de cordial puro concentrado',
                'precio'       => 80.00,
                'stock'        => 50,
                'stock_minimo' => 5,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => false,
            ],
            [
                'nombre'       => 'Medio Litro Puro',
                'descripcion'  => 'Botella de medio litro de cordial puro concentrado',
                'precio'       => 40.00,
                'stock'        => 50,
                'stock_minimo' => 5,
                'tipo'         => 'cordial',
                'activo'       => true,
                'frecuente'    => false,
            ],
        ];

        foreach ($productos as $prod) {
            Producto::updateOrCreate(
                ['nombre' => $prod['nombre'], 'tipo' => $prod['tipo']],
                $prod
            );
        }

        $this->command->info('✅ ' . count($productos) . ' productos por defecto creados/actualizados.');
    }
}
