<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class IATest2 extends TestCase
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
        Http::fake(['*' => Http::response('', 500)]);
    }

    #[Test]
    public function analizar_consulta_sobre_stock(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'Qué productos necesitan reposición de stock',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['modo', 'resultado', 'analisis']);
    }

    #[Test]
    public function analizar_consulta_sobre_ventas(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'ventas de esta semana',
        ]);
        $response->assertStatus(200);
        $resultado = $response->json('resultado');
        $this->assertNotEmpty($resultado);
    }

    #[Test]
    public function analizar_consulta_sobre_clientes(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'clientes frecuentes y fidelizacion',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['modo' => 'offline']);
    }

    #[Test]
    public function analizar_consulta_sobre_top_productos(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'top productos más vendidos',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['resultado']);
    }

    #[Test]
    public function analizar_con_cliente_id_valido(): void
    {
        $cliente = Cliente::factory()->create();
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta'   => 'analiza este cliente',
            'cliente_id' => $cliente->id,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['modo', 'resultado', 'cliente_contexto']);
    }

    #[Test]
    public function analizar_con_cliente_id_invalido_falla_validacion(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta'   => 'test',
            'cliente_id' => 99999,
        ]);
        $response->assertStatus(422);
    }

    #[Test]
    public function analizar_consulta_muy_larga_falla_validacion(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => str_repeat('a', 2001),
        ]);
        $response->assertStatus(422);
    }

    #[Test]
    public function ia_index_tiene_lista_de_clientes(): void
    {
        Cliente::factory()->count(3)->create();
        $response = $this->actingAs($this->admin)->get('/ia');
        $response->assertSuccessful();
        $response->assertViewHas('clientes');
        $clientes = $response->viewData('clientes');
        $this->assertGreaterThanOrEqual(3, $clientes->count());
    }

    #[Test]
    public function ia_index_tiene_analisis_negocio(): void
    {
        $response = $this->actingAs($this->admin)->get('/ia');
        $response->assertSuccessful();
        $response->assertViewHas('analisis');
        $analisis = $response->viewData('analisis');
        $this->assertArrayHasKey('ventas_hoy', $analisis);
        $this->assertArrayHasKey('productos_activos', $analisis);
        $this->assertArrayHasKey('clientes_total', $analisis);
    }

    #[Test]
    public function analisis_ventas_hoy_tiene_count_y_total(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'resumen general',
        ]);
        $analisis = $response->json('analisis');
        $this->assertArrayHasKey('count', $analisis['ventas_hoy']);
        $this->assertArrayHasKey('total', $analisis['ventas_hoy']);
    }

    #[Test]
    public function analisis_ventas_semana_tiene_count_y_total(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'resumen semanal',
        ]);
        $analisis = $response->json('analisis');
        $this->assertArrayHasKey('count', $analisis['ventas_semana']);
        $this->assertArrayHasKey('total', $analisis['ventas_semana']);
    }

    #[Test]
    public function analisis_cuenta_productos_activos_correctamente(): void
    {
        Producto::factory()->count(4)->create(['activo' => true]);
        Producto::factory()->count(2)->create(['activo' => false]);
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'inventario',
        ]);
        $analisis = $response->json('analisis');
        $this->assertGreaterThanOrEqual(4, $analisis['productos_activos']);
    }

    #[Test]
    public function analisis_cuenta_clientes_correctamente(): void
    {
        Cliente::factory()->count(5)->create();
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'clientes',
        ]);
        $analisis = $response->json('analisis');
        $this->assertGreaterThanOrEqual(5, $analisis['clientes_total']);
    }

    #[Test]
    public function analisis_stock_critico_es_array(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'stock',
        ]);
        $analisis = $response->json('analisis');
        $this->assertIsArray($analisis['stock_critico']);
    }

    #[Test]
    public function empleado_puede_analizar_con_ia(): void
    {
        $response = $this->actingAs($this->empleado)->postJson('/ia/analizar', [
            'consulta' => 'ventas del dia',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['modo' => 'offline']);
    }

    #[Test]
    public function no_autenticado_no_puede_analizar(): void
    {
        $response = $this->postJson('/ia/analizar', [
            'consulta' => 'test',
        ]);
        $response->assertStatus(401);
    }

    #[Test]
    public function analisis_con_stock_critico_detecta_productos_bajo_minimo(): void
    {
        Producto::factory()->create([
            'activo'       => true,
            'stock'        => 1,
            'stock_minimo' => 10,
        ]);
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'stock critico',
        ]);
        $analisis = $response->json('analisis');
        $this->assertGreaterThan(0, count($analisis['stock_critico']));
    }

    #[Test]
    public function analisis_clientes_frecuentes_es_numerico(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'clientes frecuentes',
        ]);
        $analisis = $response->json('analisis');
        $this->assertIsInt($analisis['clientes_frecuentes']);
    }

    #[Test]
    public function cliente_contexto_es_null_sin_cliente(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'analiza el negocio',
        ]);
        $response->assertStatus(200);
        $this->assertNull($response->json('cliente_contexto'));
    }

    #[Test]
    public function cliente_contexto_tiene_estructura_con_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta'   => 'analiza cliente',
            'cliente_id' => $cliente->id,
        ]);
        $contexto = $response->json('cliente_contexto');
        $this->assertNotNull($contexto);
        $this->assertArrayHasKey('cliente', $contexto);
        $this->assertArrayHasKey('patrones', $contexto);
        $this->assertArrayHasKey('recomendaciones', $contexto);
    }
}
