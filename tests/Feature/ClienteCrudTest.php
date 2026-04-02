<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ClienteCrudTest extends TestCase
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
    public function lista_de_clientes_es_accesible(): void
    {
        Cliente::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/clientes');

        $response->assertStatus(200);
        $response->assertViewIs('clientes.index');
    }

    #[Test]
    public function puede_crear_cliente_con_datos_validos(): void
    {
        $response = $this->actingAs($this->admin)->post('/clientes', [
            'nombre'   => 'María',
            'apellido' => 'García',
            'dni'      => '12345678',
            'telefono' => '987654321',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clientes', [
            'nombre'   => 'María',
            'apellido' => 'García',
            'dni'      => '12345678',
        ]);
    }

    #[Test]
    public function crear_cliente_requiere_nombre_y_dni(): void
    {
        $response = $this->actingAs($this->admin)->post('/clientes', []);

        $response->assertSessionHasErrors(['nombre', 'dni']);
    }

    #[Test]
    public function dni_debe_ser_unico(): void
    {
        Cliente::factory()->create(['dni' => '11111111']);

        $response = $this->actingAs($this->admin)->post('/clientes', [
            'nombre'   => 'Pedro',
            'apellido' => 'López',
            'dni'      => '11111111',
        ]);

        $response->assertSessionHasErrors('dni');
    }

    #[Test]
    public function puede_ver_detalle_de_cliente(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->admin)->get("/clientes/{$cliente->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function puede_editar_cliente(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->admin)->put("/clientes/{$cliente->id}", [
            'nombre'   => 'NuevoNombre',
            'apellido' => 'NuevoApellido',
            'dni'      => $cliente->dni,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clientes', [
            'id'       => $cliente->id,
            'nombre'   => 'NuevoNombre',
            'apellido' => 'NuevoApellido',
        ]);
    }

    #[Test]
    public function puede_eliminar_cliente(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/clientes/{$cliente->id}");

        $response->assertRedirect('/clientes');
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
    }

    #[Test]
    public function puede_buscar_cliente_por_dni_via_api(): void
    {
        Cliente::factory()->create(['dni' => '99887766']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/clientes/dni?dni=99887766');

        $response->assertStatus(200);
        $response->assertJsonFragment(['dni' => '99887766']);
    }
}
