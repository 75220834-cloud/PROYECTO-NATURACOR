<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\CajaSesion;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class VentaTest2 extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;
    protected CajaSesion $caja;

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
        $this->caja = CajaSesion::factory()->create([
            'user_id'     => $this->admin->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
    }

    #[Test]
    public function venta_con_metodo_pago_yape(): void
    {
        $producto = Producto::factory()->create(['precio' => 25.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'yape',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('ventas', ['metodo_pago' => 'yape', 'estado' => 'completada']);
    }

    #[Test]
    public function venta_con_metodo_pago_plin(): void
    {
        $producto = Producto::factory()->create(['precio' => 30.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'plin',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('ventas', ['metodo_pago' => 'plin']);
    }

    #[Test]
    public function venta_con_metodo_pago_tarjeta(): void
    {
        $producto = Producto::factory()->create(['precio' => 50.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'tarjeta',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('ventas', ['metodo_pago' => 'tarjeta']);
    }

    #[Test]
    public function venta_descuenta_stock_del_producto(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 20, 'activo' => true]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 3, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $this->assertEquals(17, $producto->fresh()->stock);
    }

    #[Test]
    public function venta_con_descuento_en_producto(): void
    {
        $producto = Producto::factory()->create(['precio' => 50.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 10]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('detalle_ventas', ['descuento' => 10]);
    }

    #[Test]
    public function venta_falla_si_stock_insuficiente(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 1, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 5, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    #[Test]
    public function venta_registra_numero_boleta_unico(): void
    {
        $p1 = Producto::factory()->create(['precio' => 10.00, 'stock' => 10, 'activo' => true]);
        $p2 = Producto::factory()->create(['precio' => 20.00, 'stock' => 10, 'activo' => true]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items' => [['producto_id' => $p1->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items' => [['producto_id' => $p2->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $boletas = Venta::pluck('numero_boleta')->toArray();
        $this->assertCount(2, array_unique($boletas));
    }

    #[Test]
    public function venta_se_asocia_a_caja_activa(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 10, 'activo' => true]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $this->assertDatabaseHas('ventas', ['caja_sesion_id' => $this->caja->id]);
    }

    #[Test]
    public function venta_actualiza_total_efectivo_de_caja(): void
    {
        $producto = Producto::factory()->create(['precio' => 100.00, 'stock' => 10, 'activo' => true]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $this->caja->refresh();
        $this->assertGreaterThan(0, (float) $this->caja->total_efectivo);
    }

    #[Test]
    public function venta_con_yape_actualiza_total_yape_de_caja(): void
    {
        $producto = Producto::factory()->create(['precio' => 50.00, 'stock' => 10, 'activo' => true]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'yape',
        ]);
        $this->caja->refresh();
        $this->assertGreaterThan(0, (float) $this->caja->total_yape);
    }

    #[Test]
    public function venta_con_plin_actualiza_total_plin_de_caja(): void
    {
        $producto = Producto::factory()->create(['precio' => 30.00, 'stock' => 10, 'activo' => true]);
        $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'plin',
        ]);
        $this->caja->refresh();
        $this->assertGreaterThan(0, (float) $this->caja->total_plin);
    }

    #[Test]
    public function puede_ver_detalle_de_venta(): void
    {
        $venta = Venta::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->admin->id,
        ]);
        $response = $this->actingAs($this->admin)->get("/ventas/{$venta->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_anular_venta(): void
    {
        $venta = Venta::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->admin->id,
            'estado'      => 'completada',
        ]);
        $response = $this->actingAs($this->admin)->delete("/ventas/{$venta->id}");
        $response->assertRedirect();
        $this->assertDatabaseHas('ventas', ['id' => $venta->id, 'estado' => 'anulada']);
    }

    #[Test]
    public function empleado_no_puede_anular_venta(): void
    {
        $venta = Venta::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->empleado->id,
            'estado'      => 'completada',
        ]);
        $response = $this->actingAs($this->empleado)->delete("/ventas/{$venta->id}");
        $response->assertForbidden();
        $this->assertDatabaseHas('ventas', ['id' => $venta->id, 'estado' => 'completada']);
    }

    #[Test]
    public function puede_filtrar_ventas_por_fecha_desde(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas?fecha_desde=' . now()->format('Y-m-d'));
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_ventas_por_fecha_hasta(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas?fecha_hasta=' . now()->format('Y-m-d'));
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_ventas_por_metodo_pago(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas?metodo_pago=efectivo');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_ventas_por_rango_de_fechas(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas?fecha_desde=' . now()->subDays(7)->format('Y-m-d') . '&fecha_hasta=' . now()->format('Y-m-d'));
        $response->assertSuccessful();
    }

    #[Test]
    public function venta_requiere_metodo_pago(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
        ]);
        $response->assertStatus(422);
    }

    #[Test]
    public function venta_cliente_id_invalido_falla(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
            'cliente_id'  => 99999,
        ]);
        $response->assertStatus(422);
    }

    #[Test]
    public function venta_solo_cordial_sin_items(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'cordial'     => [['tipo' => 'tienda_s3', 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function venta_acumula_fidelizacion_con_cliente(): void
    {
        $cliente  = Cliente::factory()->create(['acumulado_naturales' => 0]);
        $producto = Producto::factory()->create([
            'precio' => 100.00, 'stock' => 10, 'activo' => true, 'tipo' => 'natural',
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
    public function create_no_existe_ruta_directa(): void
    {
        // La ruta create está excluida — el POS es la entrada de ventas
        $response = $this->actingAs($this->admin)->get('/ventas/pos');
        $response->assertSuccessful();
        $response->assertViewIs('ventas.pos');
    }

    #[Test]
    public function edit_no_existe_ruta_directa(): void
    {
        // La ruta edit está excluida — las ventas no se editan, solo se anulan
        $venta = Venta::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->admin->id,
        ]);
        $response = $this->actingAs($this->admin)->get("/ventas/{$venta->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function update_retorna_405(): void
    {
        $venta = Venta::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->admin->id,
        ]);
        $response = $this->actingAs($this->admin)->putJson("/ventas/{$venta->id}", []);
        $response->assertStatus(405);
    }

    #[Test]
    public function no_autenticado_no_puede_ver_ventas(): void
    {
        $response = $this->get('/ventas');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function no_autenticado_no_puede_ver_pos(): void
    {
        $response = $this->get('/ventas/pos');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function venta_json_retorna_venta_id_y_numero_boleta(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertJsonStructure(['success', 'venta_id', 'numero_boleta']);
    }

    #[Test]
    public function venta_con_dos_productos_y_descuentos_diferentes(): void
    {
        $p1 = Producto::factory()->create(['precio' => 100.00, 'stock' => 10, 'activo' => true]);
        $p2 = Producto::factory()->create(['precio' => 50.00,  'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items' => [
                ['producto_id' => $p1->id, 'cantidad' => 1, 'descuento' => 20],
                ['producto_id' => $p2->id, 'cantidad' => 2, 'descuento' => 5],
            ],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals(2, DetalleVenta::count());
    }

    #[Test]
    public function pos_muestra_caja_activa_del_usuario(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas/pos');
        $response->assertSuccessful();
        $response->assertViewHas('cajaActiva');
    }

    #[Test]
    public function venta_cordial_y_productos_juntos(): void
    {
        $producto = Producto::factory()->create(['precio' => 20.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'cordial'     => [['tipo' => 'tienda_s3', 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('cordial_ventas', ['tipo' => 'tienda_s3']);
    }

    #[Test]
    public function venta_promo_litro_puro_genera_toma_gratis(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'cordial'     => [['tipo' => 'litro_puro_s80', 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['promos' => ['1 toma llevar S/5 gratis por litro puro']]);
        $this->assertDatabaseHas('cordial_ventas', ['tipo' => 'llevar_s5', 'es_invitado' => true]);
    }

    #[Test]
    public function venta_retorna_premio_generado_false_sin_cliente(): void
    {
        $producto = Producto::factory()->create(['precio' => 10.00, 'stock' => 10, 'activo' => true]);
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertJsonFragment(['premio_generado' => false]);
    }

    #[Test]
    public function venta_producto_inexistente_falla(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ventas', [
            'items'       => [['producto_id' => 99999, 'cantidad' => 1, 'descuento' => 0]],
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertStatus(422);
    }

    #[Test]
    public function lista_ventas_paginada(): void
    {
        Venta::factory()->count(5)->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->admin->id,
        ]);
        $response = $this->actingAs($this->admin)->get('/ventas');
        $response->assertSuccessful();
        $response->assertViewHas('ventas');
    }
}
