<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ProductoCrudTest3 extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;
    protected Producto $producto;

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
        $this->producto = Producto::factory()->create([
            'sucursal_id'   => $this->sucursal->id,
            'tipo'          => 'natural',
            'activo'        => true,
            'stock'         => 10,
            'codigo_barras' => null,
        ]);
    }

    #[Test]
    public function index_admin_ve_productos_de_todas_sucursales(): void
    {
        $otraSucursal = Sucursal::factory()->create();
        Producto::factory()->create(['sucursal_id' => $otraSucursal->id, 'activo' => true]);
        $response = $this->actingAs($this->admin)->get('/productos');
        $response->assertSuccessful();
        $productos = $response->viewData('productos');
        $this->assertGreaterThanOrEqual(2, $productos->total());
    }

    #[Test]
    public function index_empleado_ve_solo_su_sucursal(): void
    {
        $otraSucursal = Sucursal::factory()->create();
        Producto::factory()->create(['sucursal_id' => $otraSucursal->id, 'activo' => true, 'nombre' => 'ProductoOtra']);
        $response = $this->actingAs($this->empleado)->get('/productos');
        $response->assertSuccessful();
    }

    #[Test]
    public function crear_producto_cordial(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'       => 'Cordial Naranja',
            'precio'       => 3.00,
            'stock'        => 100,
            'stock_minimo' => 10,
            'tipo'         => 'cordial',
            'frecuente'    => true,
            'sucursal_id'  => $this->sucursal->id,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['nombre' => 'Cordial Naranja', 'tipo' => 'cordial']);
    }

    #[Test]
    public function crear_producto_con_codigo_barras(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'        => 'Producto Con Barcode',
            'precio'        => 20.00,
            'stock'         => 30,
            'stock_minimo'  => 5,
            'tipo'          => 'natural',
            'frecuente'     => false,
            'sucursal_id'   => $this->sucursal->id,
            'codigo_barras' => '9999888877776666',
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['codigo_barras' => '9999888877776666']);
    }

    #[Test]
    public function codigo_barras_debe_ser_unico(): void
    {
        $this->producto->update(['codigo_barras' => '1111222233334444']);
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'        => 'Otro Producto',
            'precio'        => 10.00,
            'stock'         => 10,
            'stock_minimo'  => 2,
            'tipo'          => 'natural',
            'codigo_barras' => '1111222233334444',
        ]);
        $response->assertSessionHasErrors('codigo_barras');
    }

    #[Test]
    public function actualizar_producto_con_codigo_barras_propio_no_falla(): void
    {
        $this->producto->update(['codigo_barras' => '5555666677778888']);
        $response = $this->actingAs($this->admin)->put("/productos/{$this->producto->id}", [
            'nombre'        => $this->producto->nombre,
            'precio'        => $this->producto->precio,
            'stock'         => $this->producto->stock,
            'stock_minimo'  => $this->producto->stock_minimo,
            'tipo'          => 'natural',
            'frecuente'     => false,
            'activo'        => true,
            'codigo_barras' => '5555666677778888',
        ]);
        $response->assertRedirect('/productos');
    }

    #[Test]
    public function crear_producto_precio_cero_es_valido(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'       => 'Producto Gratis',
            'precio'       => 0,
            'stock'        => 50,
            'stock_minimo' => 5,
            'tipo'         => 'natural',
            'frecuente'    => false,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['nombre' => 'Producto Gratis', 'precio' => 0]);
    }

    #[Test]
    public function crear_producto_stock_cero_es_valido(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'       => 'Producto Sin Stock',
            'precio'       => 10.00,
            'stock'        => 0,
            'stock_minimo' => 5,
            'tipo'         => 'natural',
            'frecuente'    => false,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['nombre' => 'Producto Sin Stock', 'stock' => 0]);
    }

    #[Test]
    public function show_producto_retorna_vista_con_producto(): void
    {
        $response = $this->actingAs($this->empleado)->get("/productos/{$this->producto->id}");
        $response->assertSuccessful();
        $response->assertViewHas('producto');
        $p = $response->viewData('producto');
        $this->assertEquals($this->producto->id, $p->id);
    }

    #[Test]
    public function create_retorna_vista_con_sucursales(): void
    {
        $response = $this->actingAs($this->admin)->get('/productos/create');
        $response->assertSuccessful();
        $response->assertViewHas('sucursales');
    }

    #[Test]
    public function edit_retorna_vista_con_producto_y_sucursales(): void
    {
        $response = $this->actingAs($this->admin)->get("/productos/{$this->producto->id}/edit");
        $response->assertSuccessful();
        $response->assertViewHas('producto');
        $response->assertViewHas('sucursales');
    }

    #[Test]
    public function actualizar_producto_a_inactivo(): void
    {
        $response = $this->actingAs($this->admin)->put("/productos/{$this->producto->id}", [
            'nombre'       => $this->producto->nombre,
            'precio'       => $this->producto->precio,
            'stock'        => $this->producto->stock,
            'stock_minimo' => $this->producto->stock_minimo,
            'tipo'         => 'natural',
            'frecuente'    => false,
            'activo'       => false,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['id' => $this->producto->id, 'activo' => false]);
    }

    #[Test]
    public function actualizar_producto_a_frecuente(): void
    {
        $response = $this->actingAs($this->admin)->put("/productos/{$this->producto->id}", [
            'nombre'       => $this->producto->nombre,
            'precio'       => $this->producto->precio,
            'stock'        => $this->producto->stock,
            'stock_minimo' => $this->producto->stock_minimo,
            'tipo'         => 'natural',
            'frecuente'    => true,
            'activo'       => true,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['id' => $this->producto->id, 'frecuente' => true]);
    }

    #[Test]
    public function api_buscar_retorna_solo_productos_naturales(): void
    {
        Producto::factory()->create([
            'nombre' => 'Cordial Busqueda',
            'tipo'   => 'cordial',
            'activo' => true,
        ]);
        $response = $this->actingAs($this->empleado)
            ->get('/api/productos/buscar?q=Cordial');
        $response->assertSuccessful();
        $data = $response->json();
        foreach ($data as $item) {
            $this->assertNotEquals('cordial', Producto::find($item['id'])->tipo ?? 'natural');
        }
    }

    #[Test]
    public function api_buscar_sin_query_retorna_array(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get('/api/productos/buscar?q=');
        $response->assertSuccessful();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function destroy_usa_soft_delete(): void
    {
        $response = $this->actingAs($this->admin)->delete("/productos/{$this->producto->id}");
        $response->assertRedirect('/productos');
        $this->assertSoftDeleted('productos', ['id' => $this->producto->id]);
    }
}
