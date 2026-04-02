<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\CajaSesion;
use App\Models\Sucursal;
use App\Models\FidelizacionCanje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class FidelizacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Sucursal $sucursal;
    protected CajaSesion $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursal = Sucursal::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
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
    public function cliente_frecuente_acumula_compras_de_naturales(): void
    {
        // Umbral de fidelización: S/250 acumulado en naturales
        $producto = Producto::factory()->create([
            'precio' => 100.00,
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create(['frecuente' => true, 'acumulado_naturales' => 0]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $cliente->refresh();
        $this->assertGreaterThan(0, $cliente->acumulado_naturales);
    }

    #[Test]
    public function cliente_sin_registrar_no_acumula_fidelizacion(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 100.00,
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => null,
        ]);

        $this->assertDatabaseCount('fidelizacion_canjes', 0);
    }

    #[Test]
    public function cliente_que_supera_umbral_250_genera_canje(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 300.00, // supera el umbral de S/250 en una compra
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        // Cliente con acumulado ya en S/200 (le falta S/50 para el umbral)
        $cliente = Cliente::factory()->create([
            'frecuente'            => true,
            'acumulado_naturales'  => 200,
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        // Verificar que se registró un canje
        $this->assertDatabaseHas('fidelizacion_canjes', [
            'cliente_id' => $cliente->id,
            'tipo_regla' => 'regla1_250',
        ]);
    }

    #[Test]
    public function cliente_que_no_supera_umbral_no_genera_canje(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 50.00, // no supera el umbral de S/250
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create([
            'frecuente'           => true,
            'acumulado_naturales' => 0,
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $this->assertDatabaseCount('fidelizacion_canjes', 0);
    }

    #[Test]
    public function canje_se_guarda_en_fidelizacion_canjes(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 300.00,
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create([
            'frecuente'           => true,
            'acumulado_naturales' => 0,
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'yape',
            'cliente_id'  => $cliente->id,
        ]);

        $canje = FidelizacionCanje::where('cliente_id', $cliente->id)->first();
        $this->assertNotNull($canje);
        $this->assertEquals('regla1_250', $canje->tipo_regla);
        $this->assertGreaterThan(0, $canje->valor_premio);
    }

    #[Test]
    public function despues_del_canje_el_acumulado_se_reinicia_a_cero(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 300.00,
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create([
            'frecuente'           => true,
            'acumulado_naturales' => 0,
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $cliente->refresh();
        // El acumulado debe haberse reiniciado a 0 tras el canje
        $this->assertEquals(0, (float) $cliente->acumulado_naturales);
    }

    #[Test]
    public function litro_especial_s40_genera_canje_regla2(): void
    {
        $cliente = Cliente::factory()->create(['frecuente' => true, 'acumulado_naturales' => 0]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [],
            'cordial'     => [['tipo' => 'litro_especial_s40', 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $this->assertDatabaseHas('fidelizacion_canjes', [
            'cliente_id' => $cliente->id,
            'tipo_regla' => 'regla2_litro40',
        ]);
    }
}
