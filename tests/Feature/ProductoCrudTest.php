<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ProductoCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole($role);
    }

    #[Test]
    public function lista_de_productos_es_accesible(): void
    {
        Producto::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->get('/productos');

        $response->assertStatus(200);
        $response->assertViewIs('productos.index');
    }

    #[Test]
    public function puede_crear_producto_con_datos_validos(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'      => 'Té Verde Premium',
            'descripcion' => 'Alta calidad',
            'precio'      => '15.50',
            'stock'       => '100',
            'stock_minimo'=> '10',
            'tipo'        => 'natural',
            'frecuente'   => '1',
        ]);

        $response->assertRedirect('/productos');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Té Verde Premium',
            'precio' => 15.50,
            'stock'  => 100,
        ]);
    }

    #[Test]
    public function crear_producto_requiere_nombre_precio_y_stock(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', []);

        $response->assertSessionHasErrors(['nombre', 'precio', 'stock']);
    }

    #[Test]
    public function precio_no_puede_ser_negativo(): void
    {
        $response = $this->actingAs($this->admin)->post('/productos', [
            'nombre'      => 'Test',
            'precio'      => '-5',
            'stock'       => '10',
            'stock_minimo'=> '0',
            'tipo'        => 'natural',
        ]);

        $response->assertSessionHasErrors('precio');
    }

    #[Test]
    public function puede_ver_formulario_de_edicion(): void
    {
        $producto = Producto::factory()->create();

        $response = $this->actingAs($this->admin)->get("/productos/{$producto->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('productos.edit');
    }

    #[Test]
    public function puede_actualizar_producto(): void
    {
        $producto = Producto::factory()->create();

        $response = $this->actingAs($this->admin)->put("/productos/{$producto->id}", [
            'nombre'      => 'Actualizado',
            'precio'      => '25.00',
            'stock'       => '200',
            'stock_minimo'=> '5',
            'tipo'        => 'natural',
            'activo'      => '1',
        ]);

        $response->assertRedirect('/productos');
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'nombre' => 'Actualizado']);
    }

    #[Test]
    public function puede_eliminar_producto(): void
    {
        $producto = Producto::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/productos/{$producto->id}");

        $response->assertRedirect('/productos');
        $this->assertSoftDeleted('productos', ['id' => $producto->id]);
    }

    #[Test]
    public function api_buscar_productos_retorna_resultados(): void
    {
        // La API buscar() filtra solo tipo='natural' → crear producto con ese tipo
        Producto::factory()->create([
            'nombre' => 'Maca Premium',
            'activo' => true,
            'tipo'   => 'natural',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/productos/buscar?q=Maca');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nombre' => 'Maca Premium']);
    }

    #[Test]
    public function producto_con_stock_bajo_se_detecta(): void
    {
        $producto = Producto::factory()->create(['stock' => 2, 'stock_minimo' => 5]);
        $this->assertTrue($producto->tieneStockBajo());
    }

    #[Test]
    public function producto_con_stock_suficiente_no_tiene_stock_bajo(): void
    {
        $producto = Producto::factory()->create(['stock' => 50, 'stock_minimo' => 5]);
        $this->assertFalse($producto->tieneStockBajo());
    }
}
