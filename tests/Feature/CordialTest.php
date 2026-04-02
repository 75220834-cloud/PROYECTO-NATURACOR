<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\CajaSesion;
use App\Models\CordialVenta;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class CordialTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Sucursal $sucursal;
    protected CajaSesion $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursal = Sucursal::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create([
            'activo'      => true,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $this->admin->assignRole($role);

        $this->caja = CajaSesion::factory()->create([
            'user_id'     => $this->admin->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
    }

    #[Test]
    public function puede_ver_listado_de_cordiales(): void
    {
        $response = $this->actingAs($this->admin)->get('/cordiales');

        $response->assertStatus(200);
        $response->assertViewIs('cordiales.index');
    }

    #[Test]
    public function puede_ver_formulario_de_nueva_venta_cordial(): void
    {
        $response = $this->actingAs($this->admin)->get('/cordiales/create');

        $response->assertStatus(200);
        $response->assertViewIs('cordiales.create');
        $response->assertViewHas('tipos');
        $response->assertViewHas('precios');
    }

    #[Test]
    public function puede_registrar_venta_de_cordial_normal_s3(): void
    {
        $response = $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'tienda_s3',
            'cantidad'    => 1,
            'es_invitado' => false,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertRedirect(route('cordiales.index'));

        $this->assertDatabaseHas('cordial_ventas', [
            'tipo'       => 'tienda_s3',
            'precio'     => 3,
            'cantidad'   => 1,
        ]);
    }

    #[Test]
    public function puede_registrar_venta_de_cordial_especial_s5(): void
    {
        $response = $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'tienda_s5',
            'cantidad'    => 2,
            'es_invitado' => false,
            'metodo_pago' => 'yape',
        ]);

        $response->assertRedirect(route('cordiales.index'));

        $this->assertDatabaseHas('cordial_ventas', [
            'tipo'     => 'tienda_s5',
            'precio'   => 5,
            'cantidad' => 2,
        ]);
    }

    #[Test]
    public function puede_registrar_venta_de_litro_especial_s40(): void
    {
        $response = $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'litro_especial_s40',
            'cantidad'    => 1,
            'es_invitado' => false,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertRedirect(route('cordiales.index'));

        $this->assertDatabaseHas('cordial_ventas', [
            'tipo'   => 'litro_especial_s40',
            'precio' => 40,
        ]);
    }

    #[Test]
    public function total_de_cordial_se_calcula_correctamente(): void
    {
        $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'tienda_s5',
            'cantidad'    => 3,
            'es_invitado' => false,
            'metodo_pago' => 'efectivo',
        ]);

        $venta = Venta::latest()->first();
        $this->assertNotNull($venta);
        // 3 cordiales a S/5 = S/15 total
        $this->assertEquals(15.00, (float) $venta->total);
    }

    #[Test]
    public function invitado_registra_precio_cero(): void
    {
        $response = $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'            => 'invitado',
            'cantidad'        => 1,
            'es_invitado'     => true,
            'motivo_invitado' => 'Cliente fidelizado recibió cordial de cortesía',
            'metodo_pago'     => 'efectivo',
        ]);

        $response->assertRedirect(route('cordiales.index'));

        $this->assertDatabaseHas('cordial_ventas', [
            'tipo'        => 'invitado',
            'precio'      => 0,
            'es_invitado' => 1,
        ]);
    }

    #[Test]
    public function tipo_invalido_falla_validacion(): void
    {
        $response = $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'tipo_inexistente',
            'cantidad'    => 1,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('tipo');
        $this->assertDatabaseCount('cordial_ventas', 0);
    }

    #[Test]
    public function cantidad_minima_es_uno(): void
    {
        $response = $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'tienda_s3',
            'cantidad'    => 0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('cantidad');
    }

    #[Test]
    public function venta_cordial_genera_boleta_correlativa(): void
    {
        $this->actingAs($this->admin)->post('/cordiales', [
            'tipo'        => 'tienda_s3',
            'cantidad'    => 1,
            'metodo_pago' => 'efectivo',
        ]);

        $venta = Venta::latest()->first();
        $this->assertStringStartsWith('B001-', $venta->numero_boleta);
    }

    #[Test]
    public function endpoint_precios_retorna_json_con_precios_correctos(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/cordiales/precios');

        $response->assertStatus(200);
        $response->assertJsonStructure(['precios', 'labels']);
        $response->assertJsonPath('precios.tienda_s3', 3);
        $response->assertJsonPath('precios.tienda_s5', 5);
        $response->assertJsonPath('precios.litro_especial_s40', 40);
        $response->assertJsonPath('precios.litro_puro_s80', 80);
    }
}
