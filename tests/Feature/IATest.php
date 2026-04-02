<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\CajaSesion;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class IATest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $sucursal = Sucursal::factory()->create();

        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true, 'sucursal_id' => $sucursal->id]);
        $this->admin->assignRole($roleAdmin);

        $roleEmp = Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->empleado = User::factory()->create(['activo' => true, 'sucursal_id' => $sucursal->id]);
        $this->empleado->assignRole($roleEmp);
    }

    #[Test]
    public function admin_puede_acceder_al_modulo_ia(): void
    {
        $response = $this->actingAs($this->admin)->get('/ia');

        $response->assertStatus(200);
        $response->assertViewIs('ia.index');
    }

    #[Test]
    public function empleado_puede_acceder_al_modulo_ia(): void
    {
        $response = $this->actingAs($this->empleado)->get('/ia');

        $response->assertStatus(200);
    }

    #[Test]
    public function usuario_no_autenticado_no_puede_acceder_a_ia(): void
    {
        $response = $this->get('/ia');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function endpoint_analizar_acepta_post_con_consulta(): void
    {
        // Simular modo offline (sin API key configurada)
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('', 500),
            'api.groq.com/*' => Http::response('', 500),
            'www.google.com' => Http::response('', 200),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => '¿Cuáles son los productos más vendidos?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['modo', 'resultado', 'analisis']);
    }

    #[Test]
    public function sin_api_key_responde_en_modo_offline(): void
    {
        // Bloquear todas las peticiones externas para forzar modo offline
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'Analiza el negocio',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['modo' => 'offline']);
    }

    #[Test]
    public function respuesta_offline_contiene_datos_del_negocio(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        // Crear datos de negocio
        Producto::factory()->count(3)->create(['activo' => true]);

        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'Analiza el negocio',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('analisis', $data);
        $this->assertArrayHasKey('ventas_hoy', $data['analisis']);
        $this->assertArrayHasKey('stock_critico', $data['analisis']);
        $this->assertArrayHasKey('productos_activos', $data['analisis']);
    }

    #[Test]
    public function respuesta_offline_contiene_texto_de_analisis(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'ventas de hoy',
        ]);

        $response->assertStatus(200);
        $resultado = $response->json('resultado');
        $this->assertNotEmpty($resultado);
        $this->assertIsString($resultado);
    }

    #[Test]
    public function modulo_ia_muestra_modo_en_vista(): void
    {
        Http::fake([
            'www.google.com' => Http::response('', 200),
        ]);

        $response = $this->actingAs($this->admin)->get('/ia');

        $response->assertStatus(200);
        $response->assertViewHas('modoOnline');
        $response->assertViewHas('analisis');
    }

    #[Test]
    public function analizar_sin_consulta_usa_consulta_por_defecto(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', []);

        $response->assertStatus(200);
        $response->assertJson(['modo' => 'offline']);
    }

    #[Test]
    public function analisis_de_negocio_tiene_estructura_correcta(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $response = $this->actingAs($this->admin)->postJson('/ia/analizar', [
            'consulta' => 'clientes frecuentes',
        ]);

        $analisis = $response->json('analisis');

        $this->assertArrayHasKey('ventas_hoy', $analisis);
        $this->assertArrayHasKey('ventas_semana', $analisis);
        $this->assertArrayHasKey('ventas_mes', $analisis);
        $this->assertArrayHasKey('top_productos', $analisis);
        $this->assertArrayHasKey('clientes_total', $analisis);
        $this->assertArrayHasKey('clientes_frecuentes', $analisis);
    }
}
