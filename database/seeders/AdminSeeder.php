<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sucursal;
use App\Models\Producto;
use App\Models\Cliente;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $empleadoRole = Role::firstOrCreate(['name' => 'empleado']);

        // Crear sucursal principal
        $sucursal = Sucursal::firstOrCreate(
            ['nombre' => 'Sede Principal'],
            ['direccion' => 'Lima, Perú', 'ruc' => '20000000000', 'activa' => true]
        );

        // Crear admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@naturacor.com'],
            [
                'name' => 'Administrador NATURACOR',
                'password' => Hash::make('Admin123!'),
                'sucursal_id' => $sucursal->id,
                'activo' => true,
            ]
        );
        $admin->assignRole($adminRole);

        // Crear empleado de prueba
        $empleado = User::firstOrCreate(
            ['email' => 'empleado@naturacor.com'],
            [
                'name' => 'Empleado Demo',
                'password' => Hash::make('Empleado123!'),
                'sucursal_id' => $sucursal->id,
                'activo' => true,
            ]
        );
        $empleado->assignRole($empleadoRole);

        // Productos de ejemplo
        $productosNaturales = [
            ['nombre' => 'Aloe Vera 500ml', 'precio' => 25.00, 'stock' => 30, 'tipo' => 'natural', 'frecuente' => true],
            ['nombre' => 'Moringa Cápsulas x60', 'precio' => 35.00, 'stock' => 20, 'tipo' => 'natural', 'frecuente' => true],
            ['nombre' => 'Cúrcuma en Polvo 200g', 'precio' => 18.00, 'stock' => 25, 'tipo' => 'natural', 'frecuente' => true],
            ['nombre' => 'Jengibre Extracto 250ml', 'precio' => 22.00, 'stock' => 15, 'tipo' => 'natural', 'frecuente' => false],
            ['nombre' => 'Maca Andina Cápsulas x90', 'precio' => 40.00, 'stock' => 10, 'tipo' => 'natural', 'frecuente' => true],
            ['nombre' => 'Muña Bolsas x20', 'precio' => 8.00, 'stock' => 50, 'tipo' => 'natural', 'frecuente' => false],
            ['nombre' => 'Hercampure Cápsulas x60', 'precio' => 30.00, 'stock' => 12, 'tipo' => 'natural', 'frecuente' => true],
            ['nombre' => 'Spirulina Polvo 200g', 'precio' => 45.00, 'stock' => 8, 'tipo' => 'natural', 'frecuente' => false],
            ['nombre' => 'Sangre de Grado 100ml', 'precio' => 20.00, 'stock' => 18, 'tipo' => 'natural', 'frecuente' => true],
            ['nombre' => 'Sacha Inchi Aceite 250ml', 'precio' => 38.00, 'stock' => 14, 'tipo' => 'natural', 'frecuente' => false],
        ];

        foreach ($productosNaturales as $p) {
            Producto::firstOrCreate(
                ['nombre' => $p['nombre']],
                [...$p, 'sucursal_id' => $sucursal->id, 'stock_minimo' => 5, 'activo' => true]
            );
        }

        // Clientes de ejemplo
        $clientes = [
            ['dni' => '12345678', 'nombre' => 'María', 'apellido' => 'García López', 'telefono' => '987654321'],
            ['dni' => '87654321', 'nombre' => 'Juan', 'apellido' => 'Pérez Rodríguez', 'telefono' => '976543210'],
            ['dni' => '11223344', 'nombre' => 'Ana', 'apellido' => 'Torres Vega', 'telefono' => '965432109'],
        ];
        foreach ($clientes as $c) {
            Cliente::firstOrCreate(['dni' => $c['dni']], $c);
        }

        $this->command->info('✅ NATURACOR seeder ejecutado correctamente.');
        $this->command->info('👤 Admin: admin@naturacor.com / Admin123!');
        $this->command->info('👤 Empleado: empleado@naturacor.com / Empleado123!');
    }
}
