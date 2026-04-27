<?php

namespace Tests\Feature\Analytics;

use App\Models\Cliente;
use App\Models\ClientePadecimiento;
use App\Models\Enfermedad;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bloque 6 — Tests de integración del panel Mapa de calor de enfermedades.
 *
 * Verifica:
 *  - Las dos rutas (HTML y CSV) requieren autenticación.
 *  - La vista renderiza correctamente con datos reales.
 *  - El CSV se sirve con headers de descarga y contenido esperado.
 *  - Los filtros de query string llegan al servicio.
 */
class HeatmapEnfermedadesFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function adminAutenticado(): User
    {
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole('admin');

        return $u;
    }

    private function ventaEnFecha(Sucursal $sucursal, Cliente $cliente, Carbon $fecha): void
    {
        Carbon::setTestNow($fecha);
        $u = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $v = Venta::create([
            'cliente_id'  => $cliente->id,
            'user_id'     => $u->id,
            'sucursal_id' => $sucursal->id,
            'subtotal'    => 0, 'igv' => 0, 'total' => 0, 'descuento_total' => 0,
            'metodo_pago' => 'efectivo', 'estado' => 'completada', 'incluir_igv' => true,
        ]);
        $v->update(['numero_boleta' => $v->generarNumeroBoleta()]);
        Carbon::setTestNow();
    }

    #[Test]
    public function vista_requiere_autenticacion(): void
    {
        $this->get('/metricas/heatmap-enfermedades')
            ->assertRedirect('/login');
    }

    #[Test]
    public function csv_requiere_autenticacion(): void
    {
        $this->get('/metricas/heatmap-enfermedades/export.csv')
            ->assertRedirect('/login');
    }

    #[Test]
    public function vista_responde_ok_para_usuario_autenticado_sin_datos(): void
    {
        $u = $this->adminAutenticado();

        $this->actingAs($u)->get('/metricas/heatmap-enfermedades')
            ->assertOk()
            ->assertSee('Mapa de calor de enfermedades')
            ->assertSee('Sin enfermedades o sucursales activas para construir la matriz.');
    }

    #[Test]
    public function vista_renderiza_celdas_con_datos_reales(): void
    {
        $u = $this->adminAutenticado();
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja Centro', 'activa' => true]);
        $diab = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $cli = Cliente::factory()->create();
        ClientePadecimiento::create(['cliente_id' => $cli->id, 'enfermedad_id' => $diab->id]);
        $this->ventaEnFecha($jauja, $cli, now()->subDay());

        $res = $this->actingAs($u)->get('/metricas/heatmap-enfermedades?fuente=declarada&dias=30');

        $res->assertOk()
            ->assertSee('Jauja Centro')
            ->assertSee('Diabetes')
            // Pico por celda = 1 → aparece en el KPI superior
            ->assertSee('Pico por celda')
            // Top por sucursal: "1 clientes" en el insight de negocio
            ->assertSeeText('1 clientes');
    }

    #[Test]
    public function csv_se_sirve_como_attachment_con_filas_correctas(): void
    {
        $u = $this->adminAutenticado();
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $diab = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $cli = Cliente::factory()->create();
        ClientePadecimiento::create(['cliente_id' => $cli->id, 'enfermedad_id' => $diab->id]);
        $this->ventaEnFecha($jauja, $cli, now()->subDay());

        $res = $this->actingAs($u)->get('/metricas/heatmap-enfermedades/export.csv?fuente=declarada&dias=30');

        $res->assertOk();
        $this->assertStringStartsWith('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('heatmap_enfermedades_declarada_30d_', $res->headers->get('Content-Disposition'));

        $body = $res->getContent();
        $this->assertStringContainsString('enfermedad,categoria,Jauja,total', $body);
        $this->assertStringContainsString('Diabetes,,1,1', $body);
    }

    #[Test]
    public function filtros_query_string_se_propagan_al_servicio(): void
    {
        $u = $this->adminAutenticado();
        Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);

        $res = $this->actingAs($u)->get('/metricas/heatmap-enfermedades?fuente=observada&orden=alfabetico&dias=15');

        $res->assertOk()
            ->assertSee('value="15"', false);
        // El select tiene la opción seleccionada apropiada
        $res->assertSee('selected', false);
    }
}
