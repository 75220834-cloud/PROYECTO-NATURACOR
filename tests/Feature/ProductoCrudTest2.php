<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ProductoCrudTest2 extends TestCase
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
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'natural',
            'activo'      => true,
            'stock'       => 10,
        ]);
    }

    #[Test]
    public function puede_ver_lista_productos(): void
    {
        $response = $this->actingAs($this->empleado)->get('/productos');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_buscar_productos_por_nombre(): void
    {
        $response = $this->actingAs($this->empleado)->get('/productos?search=test');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_productos_por_tipo(): void
    {
        $response = $this->actingAs($this->empleado)->get('/productos?tipo=natural');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_productos_stock_bajo(): void
    {
        $response = $this->actingAs($this->empleado)->get('/productos?stock_bajo=1');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_formulario_crear_producto(): void
    {
        $response = $this->actingAs($this->admin)->get('/productos/create');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_crear_producto(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'       => 'Producto Test',
            'precio'       => 15.00,
            'stock'        => 20,
            'stock_minimo' => 5,
            'tipo'         => 'natural',
            'frecuente'    => false,
            'sucursal_id'  => $this->sucursal->id,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['nombre' => 'Producto Test']);
    }

    #[Test]
    public function crear_producto_requiere_campos_obligatorios(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', []);
        $response->assertSessionHasErrors(['nombre', 'precio', 'stock', 'tipo']);
    }

    #[Test]
    public function puede_ver_detalle_producto(): void
    {
        $response = $this->actingAs($this->empleado)->get("/productos/{$this->producto->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_formulario_editar_producto(): void
    {
        $response = $this->actingAs($this->admin)->get("/productos/{$this->producto->id}/edit");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_actualizar_producto(): void
    {
        $response = $this->actingAs($this->admin)->put("/productos/{$this->producto->id}", [
            'nombre'       => 'Producto Actualizado',
            'precio'       => 20.00,
            'stock'        => 15,
            'stock_minimo' => 3,
            'tipo'         => 'natural',
            'frecuente'    => false,
            'activo'       => true,
        ]);
        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['nombre' => 'Producto Actualizado']);
    }

    #[Test]
    public function puede_eliminar_producto(): void
    {
        $response = $this->actingAs($this->admin)->delete("/productos/{$this->producto->id}");
        $response->assertRedirect('/productos');
        $this->assertDatabaseMissing('productos', ['id' => $this->producto->id, 'deleted_at' => null]);
    }

    #[Test]
    public function api_buscar_producto_por_nombre(): void
    {
        $response = $this->actingAs($this->empleado)->get("/api/productos/buscar?q={$this->producto->nombre}");
        $response->assertSuccessful();
        $response->assertJsonStructure([['id', 'nombre', 'precio', 'stock']]);
    }

    #[Test]
    public function api_buscar_producto_por_barcode_existente(): void
    {
        $this->producto->update(['codigo_barras' => '1234567890']);
        $response = $this->actingAs($this->empleado)->get('/api/productos/barcode?codigo=1234567890');
        $response->assertSuccessful();
        $response->assertJson(['found' => true]);
    }

    #[Test]
    public function api_buscar_producto_por_barcode_inexistente(): void
    {
        $response = $this->actingAs($this->empleado)->get('/api/productos/barcode?codigo=0000000000');
        $response->assertSuccessful();
        $response->assertJson(['found' => false]);
    }

    #[Test]
    public function api_buscar_producto_sin_codigo_barcode(): void
    {
        $response = $this->actingAs($this->empleado)->get('/api/productos/barcode');
        $response->assertSuccessful();
        $response->assertJson(['found' => false]);
    }

    #[Test]
    public function no_autenticado_redirige_login_en_productos(): void
    {
        $response = $this->get('/productos');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function puede_exportar_productos(): void
    {
        $response = $this->actingAs($this->admin)->get('/productos/exportar');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_descargar_plantilla_productos(): void
    {
        $response = $this->actingAs($this->admin)->get('/productos/plantilla');
        $response->assertSuccessful();
    }
}
