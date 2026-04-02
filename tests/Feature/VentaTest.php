<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\CajaSesion;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CajaSesion $caja;
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

        $this->caja = CajaSesion::factory()->create([
            'user_id'     => $this->admin->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
    }

    #[Test]
    public function puede_acceder_al_pos(): void
    {
        Producto::factory()->count(3)->create(['activo' => true, 'frecuente' => true]);

        $response = $this->actingAs($this->admin)->get('/ventas/pos');

        $response->assertStatus(200);
        $response->assertViewIs('ventas.pos');
        $response->assertViewHas('productos');
    }

    #[Test]
    public function pos_muestra_productos_frecuentes(): void
    {
        Producto::factory()->frecuente()->count(4)->create();
        Producto::factory()->count(2)->create(['frecuente' => false]);

        $response = $this->actingAs($this->admin)->get('/ventas/pos');

        $response->assertStatus(200);
        $frecuentes = $response->viewData('frecuentes');
        $this->assertCount(4, $frecuentes);
    }

    #[Test]
    public function puede_registrar_venta_con_un_producto(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 50, 'activo' => true]);

        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 2, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => null,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('ventas', ['estado' => 'completada']);
        $this->assertDatabaseHas('detalle_ventas', [
            'producto_id' => $producto->id,
            'cantidad'    => 2,
        ]);
    }

    #[Test]
    public function puede_registrar_venta_con_multiples_productos(): void
    {
        $p1 = Producto::factory()->create(['precio' => 5.00,  'stock' => 100]);
        $p2 = Producto::factory()->create(['precio' => 15.00, 'stock' => 100]);
        $p3 = Producto::factory()->create(['precio' => 3.00,  'stock' => 100]);

        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items' => [
                ['producto_id' => $p1->id, 'cantidad' => 1, 'descuento' => 0],
                ['producto_id' => $p2->id, 'cantidad' => 2, 'descuento' => 0],
                ['producto_id' => $p3->id, 'cantidad' => 3, 'descuento' => 0],
            ],
            'metodo_pago' => 'yape',
            'cliente_id'  => null,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertCount(3, \App\Models\DetalleVenta::all());
    }

    #[Test]
    public function venta_calcula_total_con_igv_incluido(): void
    {
        $producto = Producto::factory()->create(['precio' => 118.00, 'stock' => 10]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => null,
        ]);

        $venta = Venta::first();
        $this->assertNotNull($venta);
        $this->assertEquals(118.00, (float) $venta->total);
        // IGV extraído = 18/118 * 118 = 18
        $this->assertEquals(18.00, (float) $venta->igv);
    }

    #[Test]
    public function venta_se_asocia_a_cliente(): void
    {
        $producto = Producto::factory()->create(['precio' => 20.00, 'stock' => 10]);
        $cliente  = Cliente::factory()->create();

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $cliente->id,
            'estado'     => 'completada',
        ]);
    }

    #[Test]
    public function venta_sin_productos_retorna_error_422(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [],
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    #[Test]
    public function puede_ver_lista_de_ventas(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas');
        $response->assertStatus(200);
    }

    #[Test]
    public function venta_genera_numero_boleta(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 10]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => null,
        ]);

        $venta = Venta::first();
        $this->assertNotNull($venta->numero_boleta);
        $this->assertStringStartsWith('B001-', $venta->numero_boleta);
    }
}
