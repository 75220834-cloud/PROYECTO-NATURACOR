<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\FidelizacionCanje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class FidelizacionTest2 extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sucursal = Sucursal::factory()->create();
        Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true, 'sucursal_id' => $this->sucursal->id]);
        $this->admin->assignRole('admin');
        $this->empleado = User::factory()->create(['activo' => true, 'sucursal_id' => $this->sucursal->id]);
        $this->empleado->assignRole('empleado');
    }

    #[Test]
    public function puede_ver_pagina_fidelizacion(): void
    {
        $response = $this->actingAs($this->empleado)->get('/fidelizacion');
        $response->assertSuccessful();
    }

    #[Test]
    public function no_autenticado_redirige_login_en_fidelizacion(): void
    {
        $response = $this->get('/fidelizacion');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function fidelizacion_con_clientes_sin_acumulado(): void
    {
        Cliente::factory()->count(3)->create(['acumulado_naturales' => 0]);
        $response = $this->actingAs($this->empleado)->get('/fidelizacion');
        $response->assertSuccessful();
    }

    #[Test]
    public function fidelizacion_con_clientes_en_progreso(): void
    {
        Cliente::factory()->count(2)->create(['acumulado_naturales' => 250]);
        $response = $this->actingAs($this->empleado)->get('/fidelizacion');
        $response->assertSuccessful();
    }

    #[Test]
    public function fidelizacion_busqueda_por_nombre(): void
    {
        Cliente::factory()->create(['nombre' => 'Juan', 'apellido' => 'Perez']);
        $response = $this->actingAs($this->empleado)->get('/fidelizacion?search=Juan');
        $response->assertSuccessful();
    }

    #[Test]
    public function fidelizacion_busqueda_por_dni(): void
    {
        Cliente::factory()->create(['dni' => '12345678']);
        $response = $this->actingAs($this->empleado)->get('/fidelizacion?search=12345678');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_entregar_premio_fidelizacion(): void
    {
        $cliente = Cliente::factory()->create(['acumulado_naturales' => 500]);
        $canje = FidelizacionCanje::create([
            'cliente_id'        => $cliente->id,
            'tipo_regla'        => 'regla1_500',
            'valor_premio'      => 40.00,
            'descripcion'       => 'Premio regla 1',
            'descripcion_premio'=> 'Litro especial gratis',
            'entregado'         => false,
        ]);
        $response = $this->actingAs($this->admin)->post("/fidelizacion/{$canje->id}/entregar");
        $response->assertRedirect();
        $this->assertDatabaseHas('fidelizacion_canjes', [
            'id'       => $canje->id,
            'entregado'=> true,
        ]);
    }

    #[Test]
    public function entregar_premio_descuenta_stock_si_hay_producto(): void
    {
        $cliente = Cliente::factory()->create(['acumulado_naturales' => 500]);
        $producto = Producto::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'nombre'      => 'Litro especial cordial',
            'tipo'        => 'cordial',
            'activo'      => true,
            'stock'       => 5,
        ]);
        $canje = FidelizacionCanje::create([
            'cliente_id'        => $cliente->id,
            'tipo_regla'        => 'regla1_500',
            'valor_premio'      => 40.00,
            'descripcion'       => 'Premio regla 1',
            'descripcion_premio'=> 'Litro especial gratis',
            'entregado'         => false,
        ]);
        $this->actingAs($this->admin)->post("/fidelizacion/{$canje->id}/entregar");
        $this->assertDatabaseHas('fidelizacion_canjes', ['id' => $canje->id, 'entregado' => true]);
    }
}
