<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Cliente;
use App\Models\Enfermedad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ClienteCrudTest2 extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;
    protected Cliente $cliente;

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
        $this->cliente = Cliente::factory()->create([
            'dni'      => '12345678',
            'nombre'   => 'Maria',
            'apellido' => 'Lopez',
            'telefono' => '999888777',
        ]);
    }

    #[Test]
    public function show_cliente_carga_total_compras(): void
    {
        $response = $this->actingAs($this->empleado)->get("/clientes/{$this->cliente->id}");
        $response->assertSuccessful();
        $response->assertViewHas('totalCompras');
        $response->assertViewHas('canjesPendientes');
    }

    #[Test]
    public function show_cliente_carga_relaciones_ventas_y_canjes(): void
    {
        $response = $this->actingAs($this->empleado)->get("/clientes/{$this->cliente->id}");
        $response->assertSuccessful();
        $cliente = $response->viewData('cliente');
        $this->assertTrue($cliente->relationLoaded('ventas'));
        $this->assertTrue($cliente->relationLoaded('canjes'));
    }

    #[Test]
    public function api_padecimientos_retorna_estructura_correcta(): void
    {
        $response = $this->actingAs($this->empleado)
            ->getJson("/api/clientes/{$this->cliente->id}/padecimientos");
        $response->assertSuccessful();
        $response->assertJsonStructure(['padecimientos', 'enfermedades']);
    }

    #[Test]
    public function api_padecimientos_retorna_enfermedades_activas(): void
    {
        Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Inactiva', 'activa' => false]);
        $response = $this->actingAs($this->empleado)
            ->getJson("/api/clientes/{$this->cliente->id}/padecimientos");
        $enfermedades = $response->json('enfermedades');
        $nombres = array_column($enfermedades, 'nombre');
        $this->assertContains('Diabetes', $nombres);
        $this->assertNotContains('Inactiva', $nombres);
    }

    #[Test]
    public function api_guardar_padecimientos_sincroniza_correctamente(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Gastritis', 'activa' => true]);
        $response = $this->actingAs($this->empleado)
            ->postJson("/api/clientes/{$this->cliente->id}/padecimientos", [
                'enfermedad_ids' => [$enfermedad->id],
            ]);
        $response->assertSuccessful();
        $response->assertJsonStructure(['padecimientos']);
        $this->assertDatabaseHas('cliente_padecimientos', [
            'cliente_id'    => $this->cliente->id,
            'enfermedad_id' => $enfermedad->id,
        ]);
    }

    #[Test]
    public function api_guardar_padecimientos_vacio_elimina_todos(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Colitis', 'activa' => true]);
        \App\Models\ClientePadecimiento::create([
            'cliente_id'    => $this->cliente->id,
            'enfermedad_id' => $enfermedad->id,
        ]);
        $response = $this->actingAs($this->empleado)
            ->postJson("/api/clientes/{$this->cliente->id}/padecimientos", [
                'enfermedad_ids' => [],
            ]);
        $response->assertSuccessful();
        $this->assertDatabaseMissing('cliente_padecimientos', [
            'cliente_id'    => $this->cliente->id,
            'enfermedad_id' => $enfermedad->id,
        ]);
    }

    #[Test]
    public function api_guardar_padecimientos_sin_ids_no_falla(): void
    {
        $response = $this->actingAs($this->empleado)
            ->postJson("/api/clientes/{$this->cliente->id}/padecimientos", []);
        $response->assertSuccessful();
    }

    #[Test]
    public function api_autocompletar_busca_por_apellido(): void
    {
        Cliente::factory()->create(['nombre' => 'Pedro', 'apellido' => 'Gonzales', 'dni' => '87654321']);
        $response = $this->actingAs($this->empleado)
            ->get('/api/clientes/autocompletar?q=Gonza');
        $response->assertSuccessful();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $nombres = array_column($data, 'nombre');
        $this->assertTrue(collect($nombres)->contains(fn($n) => str_contains($n, 'Pedro')));
    }

    #[Test]
    public function api_autocompletar_retorna_acumulado_como_float(): void
    {
        Cliente::factory()->create([
            'nombre'              => 'Rosa',
            'dni'                 => '11112222',
            'acumulado_naturales' => 150.50,
        ]);
        $response = $this->actingAs($this->empleado)
            ->get('/api/clientes/autocompletar?q=Rosa');
        $response->assertSuccessful();
        $data = $response->json();
        $this->assertIsFloat($data[0]['acumulado']);
    }

    #[Test]
    public function puede_buscar_clientes_por_apellido(): void
    {
        Cliente::factory()->create(['nombre' => 'Carlos', 'apellido' => 'Ramirez', 'dni' => '33334444']);
        $response = $this->actingAs($this->empleado)->get('/clientes?search=Ramirez');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_buscar_clientes_por_dni(): void
    {
        $response = $this->actingAs($this->empleado)->get('/clientes?search=12345678');
        $response->assertSuccessful();
    }

    #[Test]
    public function store_redirige_a_show_del_cliente_creado(): void
    {
        $response = $this->actingAs($this->empleado)->post('/clientes', [
            'dni'    => '55556666',
            'nombre' => 'Nuevo Cliente Test',
        ]);
        $response->assertRedirect();
        $cliente = Cliente::where('dni', '55556666')->first();
        $this->assertNotNull($cliente);
        $response->assertRedirect("/clientes/{$cliente->id}");
    }

    #[Test]
    public function update_redirige_a_show_del_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->put("/clientes/{$this->cliente->id}", [
            'nombre'   => 'Maria Actualizada',
            'apellido' => 'Lopez',
            'telefono' => '999888777',
        ]);
        $response->assertRedirect("/clientes/{$this->cliente->id}");
    }

    #[Test]
    public function index_muestra_clientes_paginados(): void
    {
        Cliente::factory()->count(5)->create();
        $response = $this->actingAs($this->empleado)->get('/clientes');
        $response->assertSuccessful();
        $response->assertViewHas('clientes');
    }
}
