<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\CajaSesion;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class DashboardTest extends TestCase
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
    public function admin_puede_ver_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
    }

    #[Test]
    public function empleado_puede_ver_dashboard(): void
    {
        $response = $this->actingAs($this->empleado)->get('/dashboard');
        $response->assertSuccessful();
    }

    #[Test]
    public function no_autenticado_redirige_login_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function dashboard_tiene_datos_ventas_hoy(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('totalHoy');
        $response->assertViewHas('countHoy');
    }

    #[Test]
    public function dashboard_tiene_datos_stock_bajo(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('stockBajo');
    }

    #[Test]
    public function dashboard_tiene_datos_caja_activa(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('cajaActiva');
    }

    #[Test]
    public function dashboard_tiene_total_mes(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('totalMes');
    }

    #[Test]
    public function dashboard_muestra_stock_bajo_correctamente(): void
    {
        Producto::factory()->create([
            'sucursal_id'  => $this->sucursal->id,
            'activo'       => true,
            'stock'        => 2,
            'stock_minimo' => 5,
        ]);
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $stockBajo = $response->viewData('stockBajo');
        $this->assertGreaterThan(0, $stockBajo->count());
    }

    #[Test]
    public function dashboard_muestra_caja_activa_cuando_existe(): void
    {
        CajaSesion::factory()->create([
            'user_id'     => $this->admin->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $cajaActiva = $response->viewData('cajaActiva');
        $this->assertNotNull($cajaActiva);
    }

    #[Test]
    public function dashboard_sin_ventas_muestra_totales_en_cero(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $totalHoy = $response->viewData('totalHoy');
        $this->assertEquals(0, (float) $totalHoy);
    }

    #[Test]
    public function dashboard_muestra_ventas_de_hoy(): void
    {
        Venta::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'user_id'     => $this->admin->id,
            'total'       => 150.00,
            'estado'      => 'completada',
            'created_at'  => now(),
        ]);
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $totalHoy = $response->viewData('totalHoy');
        $this->assertEquals(150.00, (float) $totalHoy);
    }

    #[Test]
    public function dashboard_tiene_premios_pendientes(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('premiosPendientes');
    }

    #[Test]
    public function dashboard_tiene_mas_vendidos(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('masVendidos');
    }

    #[Test]
    public function dashboard_tiene_ventas_semana(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('ventasSemana');
    }

    #[Test]
    public function dashboard_tiene_ingresos_por_metodo(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('ingresosPorMetodo');
    }

    #[Test]
    public function dashboard_tiene_efectivo_neto(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSuccessful();
        $response->assertViewHas('efectivoNetoHoy');
    }

    #[Test]
    public function dashboard_empleado_filtra_por_su_sucursal(): void
    {
        $otraSucursal = Sucursal::factory()->create();
        Venta::factory()->create([
            'sucursal_id' => $otraSucursal->id,
            'user_id'     => $this->empleado->id,
            'total'       => 999.00,
            'estado'      => 'completada',
            'created_at'  => now(),
        ]);
        $response = $this->actingAs($this->empleado)->get('/dashboard');
        $response->assertSuccessful();
        $totalHoy = $response->viewData('totalHoy');
        $this->assertEquals(0, (float) $totalHoy);
    }
}
