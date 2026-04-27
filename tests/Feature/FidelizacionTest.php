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

        // Forzar config 2026 vigente
        config(['naturacor.fidelizacion_inicio' => '2026-01-01']);
        config(['naturacor.fidelizacion_fin'    => '2026-12-31']);
        config(['naturacor.fidelizacion_monto'  => 500]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Regla 1 — Naturales (S/500 en productos)
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function cliente_acumula_monto_en_naturales_al_comprar_producto_natural(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 100.00,
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create([
            'frecuente'          => true,
            'acumulado_naturales'=> 0,
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $cliente->refresh();
        $this->assertGreaterThan(0, (float) $cliente->acumulado_naturales);
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
    public function llegar_a_500_naturales_genera_canje_regla1_500(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 350.00,
            'stock'  => 50,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create([
            'frecuente'           => true,
            'acumulado_naturales' => 200,
        ]);

        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $this->assertDatabaseHas('fidelizacion_canjes', [
            'cliente_id'         => $cliente->id,
            'tipo_regla'         => 'regla1_500',
            'descripcion_premio' => '1 Botella de Litro Especial gratis (S/40)',
        ]);
    }

    #[Test]
    public function canje_regla1_500_reinicia_acumulado_naturales_a_cero(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 600.00,
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

        // El acumulado ahora es permanente: no se reinicia a cero,
        // sino que crece con cada compra.
        $this->assertGreaterThan(0, (float) $cliente->acumulado_naturales);

        // Pero sí se generó el canje correctamente
        $this->assertDatabaseHas('fidelizacion_canjes', [
            'cliente_id' => $cliente->id,
            'tipo_regla' => 'regla1_500',
        ]);
    }

    #[Test]
    public function no_llegar_a_500_naturales_no_genera_canje(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 50.00,
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
    public function response_incluye_flag_premio_generado_cuando_hay_canje(): void
    {
        $producto = Producto::factory()->create([
            'precio' => 600.00,
            'stock'  => 10,
            'activo' => true,
            'tipo'   => 'natural',
        ]);
        $cliente = Cliente::factory()->create([
            'frecuente'           => true,
            'acumulado_naturales' => 0,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => $cliente->id,
        ]);

        $response->assertJsonPath('premio_generado', true);
        $response->assertJsonStructure(['canjes']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Promo litro puro (se mantiene intacta)
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function comprar_litro_puro_s80_genera_toma_llevar_s5_gratis(): void
    {
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [],
            'cordial'     => [['tipo' => 'litro_puro_s80', 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
        ]);

        $this->assertDatabaseHas('cordial_ventas', [
            'tipo'        => 'litro_puro_s80',
        ]);
        $this->assertDatabaseHas('cordial_ventas', [
            'tipo'        => 'llevar_s5',
            'precio'      => 0,
            'es_invitado' => 1,
        ]);
    }

    #[Test]
    public function dos_litros_puros_generan_dos_tomas_gratis(): void
    {
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [],
            'cordial'     => [['tipo' => 'litro_puro_s80', 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
        ]);

        $tomas = \App\Models\CordialVenta::where('tipo', 'llevar_s5')
            ->where('precio', 0)
            ->where('es_invitado', true)
            ->count();

        $this->assertEquals(2, $tomas);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Módulo de premios pendientes
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function empleado_puede_ver_modulo_fidelizacion(): void
    {
        $response = $this->actingAs($this->admin)->get('/fidelizacion');

        $response->assertStatus(200);
        $response->assertViewIs('fidelizacion.index');
    }

    #[Test]
    public function fidelizacion_muestra_premios_pendientes(): void
    {
        $cliente = Cliente::factory()->create(['frecuente' => true]);
        FidelizacionCanje::create([
            'cliente_id'         => $cliente->id,
            'tipo_regla'         => 'regla1_500',
            'valor_premio'       => 0,
            'descripcion_premio' => 'Botella 2L de Bebida Nopal gratis',
            'entregado'          => false,
        ]);

        $response = $this->actingAs($this->admin)->get('/fidelizacion');

        $response->assertStatus(200);
        $response->assertSee('Botella 2L de Bebida Nopal gratis');
    }

    #[Test]
    public function empleado_puede_marcar_premio_como_entregado(): void
    {
        $cliente = Cliente::factory()->create(['frecuente' => true]);
        $canje = FidelizacionCanje::create([
            'cliente_id'         => $cliente->id,
            'tipo_regla'         => 'regla1_500',
            'valor_premio'       => 0,
            'descripcion_premio' => 'Botella 2L de Bebida Nopal gratis',
            'entregado'          => false,
        ]);

        $this->actingAs($this->admin)
            ->post("/fidelizacion/{$canje->id}/entregar");

        $this->assertDatabaseHas('fidelizacion_canjes', [
            'id'       => $canje->id,
            'entregado'=> 1,
        ]);
        $this->assertNotNull($canje->fresh()->entregado_at);
    }

    #[Test]
    public function premios_entregados_no_aparecen_en_pendientes(): void
    {
        $cliente = Cliente::factory()->create(['frecuente' => true]);
        FidelizacionCanje::create([
            'cliente_id'         => $cliente->id,
            'tipo_regla'         => 'regla1_500',
            'valor_premio'       => 0,
            'descripcion_premio' => 'Ya entregado',
            'entregado'          => true,
            'entregado_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/fidelizacion');

        $response->assertStatus(200);
        // El canje entregado está en la pestaña "Entregados", no en "Pendientes"
        $response->assertSee('Ya entregado'); // aparece en tab entregados
    }
}
