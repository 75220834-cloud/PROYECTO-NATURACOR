<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ReporteTest extends TestCase
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
    public function admin_puede_ver_pagina_reportes(): void
    {
        $response = $this->actingAs($this->admin)->get('/reportes');
        $response->assertSuccessful();
    }

    #[Test]
    public function empleado_no_puede_ver_pagina_reportes(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reportes');
        $response->assertForbidden();
    }

    #[Test]
    public function no_autenticado_redirige_login_en_reportes(): void
    {
        $response = $this->get('/reportes');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function admin_puede_generar_reporte_sin_filtros(): void
    {
        $response = $this->actingAs($this->admin)->post('/reportes/generar', []);
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_generar_reporte_con_filtro_fecha(): void
    {
        $response = $this->actingAs($this->admin)->post('/reportes/generar', [
            'fecha_desde' => now()->subDays(7)->format('Y-m-d'),
            'fecha_hasta' => now()->format('Y-m-d'),
        ]);
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_generar_reporte_con_filtro_sucursal(): void
    {
        $response = $this->actingAs($this->admin)->post('/reportes/generar', [
            'sucursal_id' => $this->sucursal->id,
        ]);
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_generar_reporte_con_filtro_metodo_pago(): void
    {
        $response = $this->actingAs($this->admin)->post('/reportes/generar', [
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_generar_reporte_con_filtro_empleado(): void
    {
        $response = $this->actingAs($this->admin)->post('/reportes/generar', [
            'user_id' => $this->empleado->id,
        ]);
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_puede_generar_reporte_con_filtro_producto(): void
    {
        $producto = Producto::factory()->create(['sucursal_id' => $this->sucursal->id]);
        $response = $this->actingAs($this->admin)->post('/reportes/generar', [
            'producto_id' => $producto->id,
        ]);
        $response->assertSuccessful();
    }

    #[Test]
    public function empleado_no_puede_generar_reporte(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reportes/generar', []);
        $response->assertForbidden();
    }

    #[Test]
    public function admin_puede_exportar_reporte_pdf(): void
    {
        $response = $this->actingAs($this->admin)->post('/reportes/generar', [
            'exportar' => 'pdf',
        ]);
        $response->assertSuccessful();
    }
}
