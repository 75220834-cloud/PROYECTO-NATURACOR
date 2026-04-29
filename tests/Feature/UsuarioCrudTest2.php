<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class UsuarioCrudTest2 extends TestCase
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
    public function admin_puede_crear_usuario_admin(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Nuevo Admin',
            'email'                 => 'nuevo.admin@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'rol'                   => 'admin',
            'sucursal_id'           => $this->sucursal->id,
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', ['email' => 'nuevo.admin@test.com']);
    }

    #[Test]
    public function password_debe_tener_minimo_8_caracteres(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Test',
            'email'                 => 'test@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
            'rol'                   => 'empleado',
        ]);
        $response->assertSessionHasErrors('password');
    }

    #[Test]
    public function password_debe_coincidir_con_confirmacion(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Test',
            'email'                 => 'test2@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'diferente123',
            'rol'                   => 'empleado',
        ]);
        $response->assertSessionHasErrors('password');
    }

    #[Test]
    public function rol_debe_ser_valido(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Test',
            'email'                 => 'test3@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'rol'                   => 'superadmin',
        ]);
        $response->assertSessionHasErrors('rol');
    }

    #[Test]
    public function admin_puede_actualizar_password_usuario(): void
    {
        $response = $this->actingAs($this->admin)->put("/usuarios/{$this->empleado->id}", [
            'name'                  => $this->empleado->name,
            'email'                 => $this->empleado->email,
            'password'              => 'nuevapassword123',
            'password_confirmation' => 'nuevapassword123',
            'rol'                   => 'empleado',
            'sucursal_id'           => $this->sucursal->id,
        ]);
        $response->assertRedirect('/usuarios');
    }

    #[Test]
    public function admin_puede_actualizar_usuario_sin_cambiar_password(): void
    {
        $response = $this->actingAs($this->admin)->put("/usuarios/{$this->empleado->id}", [
            'name'        => 'Nombre Cambiado',
            'email'       => $this->empleado->email,
            'rol'         => 'empleado',
            'sucursal_id' => $this->sucursal->id,
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', ['name' => 'Nombre Cambiado']);
    }

    #[Test]
    public function admin_puede_cambiar_rol_de_empleado_a_admin(): void
    {
        $response = $this->actingAs($this->admin)->put("/usuarios/{$this->empleado->id}", [
            'name'        => $this->empleado->name,
            'email'       => $this->empleado->email,
            'rol'         => 'admin',
            'sucursal_id' => $this->sucursal->id,
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertTrue($this->empleado->fresh()->hasRole('admin'));
    }

    #[Test]
    public function lista_usuarios_muestra_roles(): void
    {
        $response = $this->actingAs($this->admin)->get('/usuarios');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_detalle_usuario_con_ventas(): void
    {
        $response = $this->actingAs($this->admin)->get("/usuarios/{$this->empleado->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_crear_usuario_sin_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Sin Sucursal',
            'email'                 => 'sinsucursal@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'rol'                   => 'empleado',
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', ['email' => 'sinsucursal@test.com']);
    }

    #[Test]
    public function email_invalido_falla_validacion(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', [
            'name'                  => 'Test',
            'email'                 => 'no-es-email',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'rol'                   => 'empleado',
        ]);
        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function admin_puede_desactivar_usuario(): void
    {
        $response = $this->actingAs($this->admin)->put("/usuarios/{$this->empleado->id}", [
            'name'        => $this->empleado->name,
            'email'       => $this->empleado->email,
            'rol'         => 'empleado',
            'sucursal_id' => $this->sucursal->id,
            'activo'      => false,
        ]);
        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'id'     => $this->empleado->id,
            'activo' => false,
        ]);
    }
}
