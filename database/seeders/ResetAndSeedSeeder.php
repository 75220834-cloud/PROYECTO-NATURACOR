<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\Sucursal;

class ResetAndSeedSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar todas las tablas de datos
        DB::table('logs_auditoria')->truncate();
        DB::table('fidelizacion_canjes')->truncate();
        DB::table('caja_movimientos')->truncate();
        DB::table('caja_sesiones')->truncate();
        DB::table('cordial_ventas')->truncate();
        DB::table('detalle_ventas')->truncate();
        DB::table('ventas')->truncate();
        DB::table('enfermedad_producto')->truncate();
        DB::table('enfermedades')->truncate();
        DB::table('clientes')->truncate();
        DB::table('productos')->truncate();
        DB::table('sucursales')->truncate();

        // Eliminar usuarios excepto admin
        $adminId = DB::table('users')->where('email', 'admin@naturacor.com')->value('id');
        if ($adminId) {
            DB::table('model_has_roles')->where('model_id', '!=', $adminId)->delete();
            DB::table('users')->where('id', '!=', $adminId)->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Crear sucursal principal
        $sucursalId = DB::table('sucursales')->insertGetId([
            'nombre'     => 'Sede Principal',
            'direccion'  => 'Jr. Naturacor 123, Lima',
            'telefono'   => '01-1234567',
            'ruc'        => '20600000001',
            'activa'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar sucursal al admin
        DB::table('users')->where('email', 'admin@naturacor.com')
            ->update(['sucursal_id' => $sucursalId]);

        // Crear 4 productos cordiales
        $cordiales = [
            ['nombre' => 'Cordial Normal',         'precio' => 3.00,  'descripcion' => 'Cordial vaso S/3'],
            ['nombre' => 'Cordial Especial',        'precio' => 5.00,  'descripcion' => 'Cordial vaso especial S/5'],
            ['nombre' => 'Cordial Litro Especial',  'precio' => 40.00, 'descripcion' => 'Cordial litro especial S/40'],
            ['nombre' => 'Cordial Puro Litro',      'precio' => 80.00, 'descripcion' => 'Cordial puro por litro S/80'],
        ];

        foreach ($cordiales as $c) {
            DB::table('productos')->insert([
                'nombre'       => $c['nombre'],
                'descripcion'  => $c['descripcion'],
                'precio'       => $c['precio'],
                'stock'        => 9999,
                'stock_minimo' => 0,
                'tipo'         => 'cordial',
                'frecuente'    => true,
                'activo'       => true,
                'sucursal_id'  => $sucursalId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $this->command->info('✅ Base de datos limpia.');
        $this->command->info('✅ Sucursal Principal creada (ID: ' . $sucursalId . ').');
        $this->command->info('✅ 4 productos cordiales creados.');
        $this->command->info('✅ Admin: admin@naturacor.com / Admin123!');
    }
}
