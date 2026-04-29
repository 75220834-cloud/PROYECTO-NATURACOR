<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Cliente;
use App\Models\Reclamo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ReclamoTest2 extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;
    protected Reclamo $reclamo;

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
        $this->reclamo = Reclamo::create([
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'producto',
            'descripcion' => 'Descripción de reclamo de prueba completa',
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);
    }

    #[Test]
    public function puede_ver_lista_reclamos(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_reclamos_por_estado(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos?estado=pendiente');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_filtrar_reclamos_por_tipo(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos?tipo=producto');
        $response->assertSuccessful();
    }

    #[Test]
    public function no_autenticado_redirige_login_en_reclamos(): void
    {
        $response = $this->get('/reclamos');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function puede_ver_formulario_crear_reclamo(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos/create');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_crear_reclamo_sin_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'servicio',
            'descripcion' => 'Descripción detallada del reclamo de servicio recibido',
        ]);
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseHas('reclamos', ['tipo' => 'servicio']);
    }

    #[Test]
    public function puede_crear_reclamo_con_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'cliente_id'  => $cliente->id,
            'tipo'        => 'producto',
            'descripcion' => 'Descripción detallada del reclamo de producto recibido',
        ]);
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseHas('reclamos', [
            'cliente_id' => $cliente->id,
            'tipo'       => 'producto',
        ]);
    }

    #[Test]
    public function crear_reclamo_requiere_tipo_y_descripcion(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', []);
        $response->assertSessionHasErrors(['tipo', 'descripcion']);
    }

    #[Test]
    public function descripcion_reclamo_debe_tener_minimo_10_caracteres(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'producto',
            'descripcion' => 'Corto',
        ]);
        $response->assertSessionHasErrors('descripcion');
    }

    #[Test]
    public function tipo_reclamo_debe_ser_valido(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'invalido',
            'descripcion' => 'Descripción detallada del reclamo recibido aquí',
        ]);
        $response->assertSessionHasErrors('tipo');
    }

    #[Test]
    public function puede_ver_detalle_reclamo(): void
    {
        $response = $this->actingAs($this->empleado)->get("/reclamos/{$this->reclamo->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_actualizar_estado_reclamo(): void
    {
        $response = $this->actingAs($this->admin)->put("/reclamos/{$this->reclamo->id}", [
            'estado'     => 'en_proceso',
            'resolucion' => 'En proceso de revisión',
        ]);
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseHas('reclamos', [
            'id'     => $this->reclamo->id,
            'estado' => 'en_proceso',
        ]);
    }

    #[Test]
    public function puede_resolver_reclamo(): void
    {
        $response = $this->actingAs($this->admin)->put("/reclamos/{$this->reclamo->id}", [
            'estado'     => 'resuelto',
            'resolucion' => 'Reclamo resuelto satisfactoriamente para el cliente',
        ]);
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseHas('reclamos', [
            'id'     => $this->reclamo->id,
            'estado' => 'resuelto',
        ]);
    }

    #[Test]
    public function actualizar_reclamo_requiere_estado_valido(): void
    {
        $response = $this->actingAs($this->admin)->put("/reclamos/{$this->reclamo->id}", [
            'estado' => 'invalido',
        ]);
        $response->assertSessionHasErrors('estado');
    }

    #[Test]
    public function puede_escalar_reclamo(): void
    {
        $response = $this->actingAs($this->empleado)->post("/reclamos/{$this->reclamo->id}/escalar");
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseHas('reclamos', [
            'id'       => $this->reclamo->id,
            'escalado' => true,
            'estado'   => 'en_proceso',
        ]);
    }

    #[Test]
    public function admin_puede_eliminar_reclamo(): void
    {
        $response = $this->actingAs($this->admin)->delete("/reclamos/{$this->reclamo->id}");
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseMissing('reclamos', ['id' => $this->reclamo->id]);
    }

    #[Test]
    public function empleado_no_puede_eliminar_reclamo(): void
    {
        $response = $this->actingAs($this->empleado)->delete("/reclamos/{$this->reclamo->id}");
        $response->assertForbidden();
        $this->assertDatabaseHas('reclamos', ['id' => $this->reclamo->id]);
    }

    #[Test]
    public function puede_crear_reclamo_tipo_otro(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'otro',
            'descripcion' => 'Descripción detallada de otro tipo de reclamo recibido',
        ]);
        $response->assertRedirect('/reclamos');
        $this->assertDatabaseHas('reclamos', ['tipo' => 'otro']);
    }

    #[Test]
    public function puede_ver_reclamos_filtrados_por_estado_resuelto(): void
    {
        Reclamo::create([
            'vendedor_id' => $this->admin->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'servicio',
            'descripcion' => 'Reclamo resuelto de prueba para filtrar en lista',
            'estado'      => 'resuelto',
            'escalado'    => false,
        ]);
        $response = $this->actingAs($this->admin)->get('/reclamos?estado=resuelto');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_reclamos_tipo_servicio(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos?tipo=servicio');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_reclamos_tipo_otro(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos?tipo=otro');
        $response->assertSuccessful();
    }
}
