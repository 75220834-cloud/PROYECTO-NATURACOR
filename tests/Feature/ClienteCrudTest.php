<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ClienteCrudTest extends TestCase
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
        $this->cliente = Cliente::factory()->create();
    }

    #[Test]
    public function puede_ver_lista_clientes(): void
    {
        $response = $this->actingAs($this->empleado)->get('/clientes');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_buscar_clientes(): void
    {
        Cliente::factory()->create(['nombre' => 'Carlos', 'dni' => '11111111']);
        $response = $this->actingAs($this->empleado)->get('/clientes?search=Carlos');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_formulario_crear_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->get('/clientes/create');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_crear_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->post('/clientes', [
            'dni'      => '99999999',
            'nombre'   => 'Pedro',
            'apellido' => 'Ramirez',
            'telefono' => '987654321',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('clientes', ['dni' => '99999999']);
    }

    #[Test]
    public function crear_cliente_requiere_dni_y_nombre(): void
    {
        $response = $this->actingAs($this->empleado)->post('/clientes', []);
        $response->assertSessionHasErrors(['dni', 'nombre']);
    }

    #[Test]
    public function dni_debe_ser_unico(): void
    {
        $response = $this->actingAs($this->empleado)->post('/clientes', [
            'dni'    => $this->cliente->dni,
            'nombre' => 'Duplicado',
        ]);
        $response->assertSessionHasErrors('dni');
    }

    #[Test]
    public function puede_ver_detalle_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->get("/clientes/{$this->cliente->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_formulario_editar_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->get("/clientes/{$this->cliente->id}/edit");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_actualizar_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->put("/clientes/{$this->cliente->id}", [
            'nombre'   => 'Nombre Nuevo',
            'apellido' => 'Apellido Nuevo',
            'telefono' => '999888777',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('clientes', ['nombre' => 'Nombre Nuevo']);
    }

    #[Test]
    public function actualizar_cliente_requiere_nombre(): void
    {
        $response = $this->actingAs($this->empleado)->put("/clientes/{$this->cliente->id}", []);
        $response->assertSessionHasErrors('nombre');
    }

    #[Test]
    public function puede_eliminar_cliente(): void
    {
        $response = $this->actingAs($this->admin)->delete("/clientes/{$this->cliente->id}");
        $response->assertRedirect('/clientes');
        $this->assertSoftDeleted('clientes', ['id' => $this->cliente->id]);
    }

    #[Test]
    public function api_buscar_cliente_por_dni_existente(): void
    {
        $response = $this->actingAs($this->empleado)->get("/api/clientes/dni?dni={$this->cliente->dni}");
        $response->assertSuccessful();
        $response->assertJson(['found' => true]);
    }

    #[Test]
    public function api_buscar_cliente_por_dni_inexistente(): void
    {
        $response = $this->actingAs($this->empleado)->get('/api/clientes/dni?dni=00000000');
        $response->assertSuccessful();
        $response->assertJson(['found' => false]);
    }

    #[Test]
    public function api_autocompletar_clientes(): void
    {
        Cliente::factory()->create(['nombre' => 'Maria', 'dni' => '22222222']);
        $response = $this->actingAs($this->empleado)->get('/api/clientes/autocompletar?q=Mar');
        $response->assertSuccessful();
        $response->assertJsonStructure([['id', 'dni', 'nombre', 'acumulado']]);
    }

    #[Test]
    public function api_autocompletar_retorna_vacio_si_query_corto(): void
    {
        $response = $this->actingAs($this->empleado)->get('/api/clientes/autocompletar?q=M');
        $response->assertSuccessful();
        $response->assertJson([]);
    }

    #[Test]
    public function no_autenticado_redirige_login_en_clientes(): void
    {
        $response = $this->get('/clientes');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function puede_crear_cliente_via_json(): void
    {
        $response = $this->actingAs($this->empleado)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/clientes', [
                'dni'    => '88888888',
                'nombre' => 'JsonCliente',
            ]);
        $response->assertSuccessful();
        $response->assertJsonFragment(['dni' => '88888888']);
    }
}
