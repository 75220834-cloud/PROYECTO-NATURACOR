<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class SucursalCrudTest2 extends TestCase
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
    }

    #[Test]
    public function admin_puede_ver_lista_sucursales(): void
    {
        $response = $this->actingAs($this->admin)->get('/sucursales');
        $response->assertSuccessful();
    }

    #[Test]
    public function empleado_no_puede_ver_sucursales(): void
    {
        $response = $this->actingAs($this->empleado)->get('/sucursales');
        $response->assertForbidden();
    }

    #[Test]
    public function no_autenticado_redirige_login_sucursales(): void
    {
        $response = $this->get('/sucursales');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function admin_puede_ver_formulario_crear_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->get('/sucursales/create');
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_crear_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->post('/sucursales', [
            'nombre'    => 'Sucursal Nueva',
            'direccion' => 'Jr. Los Pinos 123',
            'telefono'  => '064-123456',
            'ruc'       => '20123456789',
        ]);
        $response->assertRedirect('/sucursales');
        $this->assertDatabaseHas('sucursales', ['nombre' => 'Sucursal Nueva']);
    }

    #[Test]
    public function crear_sucursal_requiere_nombre(): void
    {
        $response = $this->actingAs($this->admin)->post('/sucursales', []);
        $response->assertSessionHasErrors('nombre');
    }

    #[Test]
    public function admin_puede_crear_sucursal_solo_con_nombre(): void
    {
        $response = $this->actingAs($this->admin)->post('/sucursales', [
            'nombre' => 'Sucursal Minima',
        ]);
        $response->assertRedirect('/sucursales');
        $this->assertDatabaseHas('sucursales', ['nombre' => 'Sucursal Minima']);
    }

    #[Test]
    public function admin_puede_ver_detalle_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->get("/sucursales/{$this->sucursal->id}/edit");
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_ver_formulario_editar_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->get("/sucursales/{$this->sucursal->id}/edit");
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_actualizar_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->put("/sucursales/{$this->sucursal->id}", [
            'nombre'    => 'Sucursal Actualizada',
            'direccion' => 'Nueva direccion 456',
            'telefono'  => '064-654321',
            'ruc'       => '20987654321',
            'activa'    => true,
        ]);
        $response->assertRedirect('/sucursales');
        $this->assertDatabaseHas('sucursales', ['nombre' => 'Sucursal Actualizada']);
    }

    #[Test]
    public function actualizar_sucursal_requiere_nombre(): void
    {
        $response = $this->actingAs($this->admin)->put("/sucursales/{$this->sucursal->id}", []);
        $response->assertSessionHasErrors('nombre');
    }

    #[Test]
    public function admin_puede_desactivar_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->put("/sucursales/{$this->sucursal->id}", [
            'nombre' => $this->sucursal->nombre,
            'activa' => false,
        ]);
        $response->assertRedirect('/sucursales');
        $this->assertDatabaseHas('sucursales', [
            'id'     => $this->sucursal->id,
            'activa' => false,
        ]);
    }

    #[Test]
    public function admin_puede_eliminar_sucursal(): void
    {
        $otraSucursal = Sucursal::factory()->create();
        $response = $this->actingAs($this->admin)->delete("/sucursales/{$otraSucursal->id}");
        $response->assertRedirect('/sucursales');
        $this->assertSoftDeleted('sucursales', ['id' => $otraSucursal->id]);
    }

    #[Test]
    public function lista_sucursales_muestra_multiples(): void
    {
        Sucursal::factory()->count(3)->create();
        $response = $this->actingAs($this->admin)->get('/sucursales');
        $response->assertSuccessful();
    }
}
