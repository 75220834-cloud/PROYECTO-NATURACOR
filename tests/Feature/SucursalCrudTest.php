<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class SucursalCrudTest extends TestCase
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
    public function lista_de_sucursales_es_accesible_para_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/sucursales');
        $response->assertStatus(200);
        $response->assertViewIs('sucursales.index');
    }

    #[Test]
    public function puede_crear_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->post('/sucursales', [
            'nombre'    => 'Nueva Sede Norte',
            'direccion' => 'Av. Progreso 456',
            'telefono'  => '01-5551234',
            'ruc'       => '20123456789',
        ]);

        $response->assertRedirect('/sucursales');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sucursales', [
            'nombre' => 'Nueva Sede Norte',
            'activa' => true,
        ]);
    }

    #[Test]
    public function crear_sucursal_requiere_nombre(): void
    {
        $response = $this->actingAs($this->admin)->post('/sucursales', []);
        $response->assertSessionHasErrors('nombre');
    }

    #[Test]
    public function puede_ver_formulario_de_edicion_de_sucursal(): void
    {
        $sucursal = Sucursal::factory()->create();

        $response = $this->actingAs($this->admin)->get("/sucursales/{$sucursal->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('sucursales.edit');
    }

    #[Test]
    public function puede_actualizar_sucursal(): void
    {
        $sucursal = Sucursal::factory()->create();

        $response = $this->actingAs($this->admin)->put("/sucursales/{$sucursal->id}", [
            'nombre'    => 'Sede Actualizada',
            'direccion' => 'Nueva Dirección 789',
            'activa'    => '1',
        ]);

        $response->assertRedirect('/sucursales');
        $this->assertDatabaseHas('sucursales', [
            'id'     => $sucursal->id,
            'nombre' => 'Sede Actualizada',
        ]);
    }

    #[Test]
    public function puede_eliminar_sucursal(): void
    {
        $sucursal = Sucursal::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/sucursales/{$sucursal->id}");

        $response->assertRedirect('/sucursales');
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('sucursales', ['id' => $sucursal->id]);
    }

    #[Test]
    public function sucursal_creada_es_activa_por_defecto(): void
    {
        $this->actingAs($this->admin)->post('/sucursales', ['nombre' => 'Test Sede']);

        $this->assertDatabaseHas('sucursales', ['nombre' => 'Test Sede', 'activa' => true]);
    }
}
