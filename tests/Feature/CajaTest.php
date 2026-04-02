<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\CajaSesion;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class CajaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->sucursal = Sucursal::factory()->create();
        $this->admin = User::factory()->create([
            'activo'      => true,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $this->admin->assignRole($role);
    }

    #[Test]
    public function puede_ver_pagina_de_caja(): void
    {
        $response = $this->actingAs($this->admin)->get('/caja');
        $response->assertStatus(200);
    }

    #[Test]
    public function puede_abrir_caja_con_monto_inicial(): void
    {
        $response = $this->actingAs($this->admin)->post('/caja/abrir', [
            'monto_inicial' => '200.00',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('caja_sesiones', [
            'user_id'       => $this->admin->id,
            'estado'        => 'abierta',
            'monto_inicial' => '200.00',
        ]);
    }

    #[Test]
    public function abrir_caja_requiere_monto_inicial(): void
    {
        $response = $this->actingAs($this->admin)->post('/caja/abrir', []);
        $response->assertSessionHasErrors('monto_inicial');
    }

    #[Test]
    public function caja_abierta_registra_ventas(): void
    {
        $this->actingAs($this->admin)->post('/caja/abrir', ['monto_inicial' => '100.00']);
        $caja = CajaSesion::where('user_id', $this->admin->id)->where('estado', 'abierta')->first();
        $this->assertNotNull($caja);

        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 20, 'activo' => true]);

        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => null,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('ventas', [
            'caja_sesion_id' => $caja->id,
            'estado'         => 'completada',
        ]);
    }

    #[Test]
    public function puede_cerrar_caja(): void
    {
        $this->actingAs($this->admin)->post('/caja/abrir', ['monto_inicial' => '150.00']);
        $caja = CajaSesion::where('user_id', $this->admin->id)->where('estado', 'abierta')->first();

        $response = $this->actingAs($this->admin)->post('/caja/cerrar', [
            'monto_real'   => '280.00',
            'notas'        => 'Todo correcto',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('caja_sesiones', [
            'id'     => $caja->id,
            'estado' => 'cerrada',
        ]);
    }

    #[Test]
    public function puede_ver_resumen_de_caja_cerrada(): void
    {
        $caja = CajaSesion::factory()->cerrada()->create([
            'user_id'     => $this->admin->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/caja/{$caja->id}");
        $response->assertStatus(200);
    }
}
