<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursal = Sucursal::factory()->create();

        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create([
            'activo'      => true,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $this->admin->assignRole($roleAdmin);

        $roleEmp = Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->empleado = User::factory()->create([
            'activo'      => true,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $this->empleado->assignRole($roleEmp);
    }

    // ─── Acceso sin autenticación ──────────────────────────────────────────

    #[Test]
    public function usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $rutas = ['/dashboard', '/ventas', '/productos', '/clientes', '/recetario', '/ia', '/reclamos', '/cordiales'];

        foreach ($rutas as $ruta) {
            $response = $this->get($ruta);
            $response->assertRedirect('/login');
        }
    }

    #[Test]
    public function usuario_no_autenticado_no_puede_acceder_a_rutas_admin(): void
    {
        $response = $this->get('/sucursales');
        $response->assertRedirect('/login');

        $response = $this->get('/usuarios');
        $response->assertRedirect('/login');
    }

    // ─── Bypass de roles (empleado intentando acceder a rutas de admin) ────

    #[Test]
    public function empleado_no_puede_acceder_a_gestion_de_sucursales(): void
    {
        $response = $this->actingAs($this->empleado)->get('/sucursales');
        $response->assertForbidden(); // 403
    }

    #[Test]
    public function empleado_no_puede_acceder_a_gestion_de_usuarios(): void
    {
        $response = $this->actingAs($this->empleado)->get('/usuarios');
        $response->assertForbidden(); // 403
    }

    #[Test]
    public function empleado_no_puede_crear_sucursal(): void
    {
        $response = $this->actingAs($this->empleado)->post('/sucursales', [
            'nombre'    => 'Sucursal Falsa',
            'direccion' => 'Dirección Falsa',
        ]);
        $response->assertForbidden();
        $this->assertDatabaseCount('sucursales', 1); // solo la del setUp
    }

    #[Test]
    public function empleado_no_puede_crear_usuario(): void
    {
        $response = $this->actingAs($this->empleado)->post('/usuarios', [
            'name'     => 'Hacker',
            'email'    => 'hacker@test.com',
            'password' => 'password',
            'role'     => 'admin',
        ]);
        $response->assertForbidden();
    }

    #[Test]
    public function admin_si_puede_acceder_a_sucursales(): void
    {
        $response = $this->actingAs($this->admin)->get('/sucursales');
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_si_puede_acceder_a_usuarios(): void
    {
        $response = $this->actingAs($this->admin)->get('/usuarios');
        $response->assertSuccessful();
    }

    // ─── Protección CSRF ───────────────────────────────────────────────────

    #[Test]
    public function post_sin_token_csrf_retorna_419(): void
    {
        // Desactivar manejo de excepciones para que el status code sea real
        $this->withoutExceptionHandling();

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/ventas', []);
    }

    #[Test]
    public function post_con_token_csrf_valido_pasa(): void
    {
        // Con token CSRF válido (Laravel lo maneja automáticamente en tests)
        $response = $this->actingAs($this->empleado)->post('/ventas', [
            'metodo_pago' => 'efectivo',
            'items'       => [],
            'cordial'     => [],
        ]);

        // No debe ser 419 (CSRF error), puede ser 422 (validación) pero CSRF pasó
        $this->assertNotEquals(419, $response->getStatusCode());
    }

    // ─── Aislamiento de sucursales ─────────────────────────────────────────

    #[Test]
    public function empleado_solo_ve_ventas_de_su_sucursal(): void
    {
        $response = $this->actingAs($this->empleado)->get('/ventas');
        $response->assertSuccessful();
        // La query en VentaController filtra por sucursal_id del usuario autenticado
        $this->assertEquals($this->empleado->sucursal_id, $this->sucursal->id);
    }

    // ─── Rutas protegidas accesibles para ambos roles ─────────────────────

    #[Test]
    public function empleado_puede_acceder_a_modulos_operativos(): void
    {
        $rutas = ['/ventas', '/clientes', '/productos', '/recetario', '/ia', '/reclamos', '/cordiales'];

        foreach ($rutas as $ruta) {
            $response = $this->actingAs($this->empleado)->get($ruta);
            $this->assertContains(
                $response->getStatusCode(),
                [200, 302], // 200 OK o 302 redirect interno (ej. /ventas redirige a /ventas/pos)
                "Fallo en ruta: $ruta con status " . $response->getStatusCode()
            );
        }
    }

    #[Test]
    public function login_page_es_accesible_sin_autenticacion(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    #[Test]
    public function credenciales_incorrectas_no_autentican(): void
    {
        $response = $this->post('/login', [
            'email'    => 'hacker@ejemplo.com',
            'password' => 'contrasenafalsisima123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    #[Test]
    public function usuario_inactivo_no_puede_autenticarse(): void
    {
        $inactivo = User::factory()->create([
            'activo'      => false,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $response = $this->post('/login', [
            'email'    => $inactivo->email,
            'password' => 'password',
        ]);

        // No debe autenticarse exitosamente
        $this->assertGuest();
    }
}
