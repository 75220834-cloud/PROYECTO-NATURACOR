<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function crearAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('Admin123!'),
            'activo'   => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    protected function crearEmpleado(): User
    {
        $role = Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'email'    => 'empleado@test.com',
            'password' => bcrypt('Empleado123!'),
            'activo'   => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    #[Test]
    public function login_con_credenciales_correctas_redirige_al_dashboard(): void
    {
        $this->crearAdmin();

        $response = $this->post('/login', [
            'email'    => 'admin@test.com',
            'password' => 'Admin123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    #[Test]
    public function login_con_credenciales_incorrectas_falla(): void
    {
        $this->crearAdmin();

        $response = $this->post('/login', [
            'email'    => 'admin@test.com',
            'password' => 'clave_incorrecta',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function usuario_autenticado_accede_al_dashboard(): void
    {
        $admin = $this->crearAdmin();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
    }

    #[Test]
    public function solo_admin_accede_a_sucursales(): void
    {
        $admin    = $this->crearAdmin();
        $empleado = $this->crearEmpleado();

        $this->actingAs($admin)->get('/sucursales')->assertStatus(200);
        $this->actingAs($empleado)->get('/sucursales')->assertStatus(403);
    }

    #[Test]
    public function logout_cierra_sesion_correctamente(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin)->post('/logout');
        $this->assertGuest();
    }
}
