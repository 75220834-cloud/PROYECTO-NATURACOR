<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class UsuarioCrudTest extends TestCase
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
    public function admin_puede_ver_lista_de_usuarios(): void
    {
        $response = $this->actingAs($this->admin)->get('/usuarios');
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_ver_formulario_crear_usuario(): void
    {
        $response = $this->actingAs($this->admin)->get('/usuarios/create');
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_crear_usuario_empleado(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Nuevo Empleado',
            'email'                 => 'nuevo@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'rol'                   => 'empleado',
            'sucursal_id'           => $this->sucursal->id,
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    }

    #[Test]
    public function crear_usuario_requiere_nombre_email_y_password(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', []);
        $response->assertSessionHasErrors(['name', 'email', 'password', 'rol']);
    }

    #[Test]
    public function email_debe_ser_unico_al_crear_usuario(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Duplicado',
            'email'                 => $this->empleado->email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'rol'                   => 'empleado',
        ]);
        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function admin_puede_ver_detalle_de_usuario(): void
    {
        $response = $this->actingAs($this->admin)->get("/usuarios/{$this->empleado->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_ver_formulario_editar_usuario(): void
    {
        $response = $this->actingAs($this->admin)->get("/usuarios/{$this->empleado->id}/edit");
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_actualizar_usuario(): void
    {
        $response = $this->actingAs($this->admin)->put("/usuarios/{$this->empleado->id}", [
            'name'        => 'Nombre Actualizado',
            'email'       => $this->empleado->email,
            'rol'         => 'empleado',
            'sucursal_id' => $this->sucursal->id,
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', ['name' => 'Nombre Actualizado']);
    }

    #[Test]
    public function admin_puede_eliminar_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create(['activo' => true, 'sucursal_id' => $this->sucursal->id]);
        $otroUsuario->assignRole('empleado');
        $response = $this->actingAs($this->admin)->delete("/usuarios/{$otroUsuario->id}");
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseMissing('users', ['id' => $otroUsuario->id, 'activo' => true]);
    }

    #[Test]
    public function admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $response = $this->actingAs($this->admin)->delete("/usuarios/{$this->admin->id}");
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'activo' => true]);
    }

    #[Test]
    public function empleado_no_puede_ver_lista_de_usuarios(): void
    {
        $response = $this->actingAs($this->empleado)->get('/usuarios');
        $response->assertForbidden();
    }
}
