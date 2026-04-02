<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Reclamo;
use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ReclamoTest extends TestCase
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

    #[Test]
    public function empleado_puede_ver_listado_de_reclamos(): void
    {
        $response = $this->actingAs($this->empleado)->get('/reclamos');
        $response->assertStatus(200);
        $response->assertViewIs('reclamos.index');
    }

    #[Test]
    public function admin_puede_ver_listado_de_reclamos(): void
    {
        $response = $this->actingAs($this->admin)->get('/reclamos');
        $response->assertStatus(200);
    }

    #[Test]
    public function empleado_puede_registrar_un_reclamo_sin_cliente(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'cliente_id'  => null,
            'tipo'        => 'servicio',
            'descripcion' => 'El vendedor no atendió correctamente al cliente.',
        ]);

        $response->assertRedirect(route('reclamos.index'));
        $this->assertDatabaseHas('reclamos', [
            'tipo'        => 'servicio',
            'estado'      => 'pendiente',
            'vendedor_id' => $this->empleado->id,
        ]);
    }

    #[Test]
    public function empleado_puede_registrar_reclamo_con_cliente(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'cliente_id'  => $cliente->id,
            'tipo'        => 'producto',
            'descripcion' => 'El producto llegó vencido y en mal estado.',
        ]);

        $response->assertRedirect(route('reclamos.index'));
        $this->assertDatabaseHas('reclamos', [
            'cliente_id'  => $cliente->id,
            'tipo'        => 'producto',
            'estado'      => 'pendiente',
        ]);
    }

    #[Test]
    public function descripcion_es_obligatoria(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'otro',
            'descripcion' => '',
        ]);

        $response->assertSessionHasErrors('descripcion');
        $this->assertDatabaseCount('reclamos', 0);
    }

    #[Test]
    public function tipo_invalido_falla_validacion(): void
    {
        $response = $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'tipo_inexistente',
            'descripcion' => 'Descripción válida del reclamo.',
        ]);

        $response->assertSessionHasErrors('tipo');
    }

    #[Test]
    public function reclamo_nuevo_tiene_estado_pendiente(): void
    {
        $this->actingAs($this->empleado)->post('/reclamos', [
            'tipo'        => 'producto',
            'descripcion' => 'Producto defectuoso recibido por el cliente.',
        ]);

        $reclamo = Reclamo::first();
        $this->assertEquals('pendiente', $reclamo->estado);
        $this->assertFalse($reclamo->escalado);
    }

    #[Test]
    public function admin_puede_cambiar_estado_a_en_proceso(): void
    {
        $reclamo = Reclamo::create([
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'servicio',
            'descripcion' => 'Reclamo de prueba.',
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);

        $response = $this->actingAs($this->admin)->put("/reclamos/{$reclamo->id}", [
            'estado'     => 'en_proceso',
            'resolucion' => 'Analizando el caso.',
        ]);

        $response->assertRedirect(route('reclamos.index'));
        $this->assertDatabaseHas('reclamos', [
            'id'     => $reclamo->id,
            'estado' => 'en_proceso',
        ]);
    }

    #[Test]
    public function admin_puede_resolver_un_reclamo(): void
    {
        $reclamo = Reclamo::create([
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'producto',
            'descripcion' => 'Producto defectuoso.',
            'estado'      => 'en_proceso',
            'escalado'    => false,
        ]);

        $response = $this->actingAs($this->admin)->put("/reclamos/{$reclamo->id}", [
            'estado'     => 'resuelto',
            'resolucion' => 'Se realizó el cambio del producto al cliente.',
        ]);

        $response->assertRedirect(route('reclamos.index'));

        $reclamo->refresh();
        $this->assertEquals('resuelto', $reclamo->estado);
        $this->assertEquals('Se realizó el cambio del producto al cliente.', $reclamo->resolucion);
        $this->assertEquals($this->admin->id, $reclamo->admin_resolutor_id);
    }

    #[Test]
    public function puede_escalar_reclamo_al_administrador(): void
    {
        $reclamo = Reclamo::create([
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'otro',
            'descripcion' => 'Situación compleja que requiere supervisión.',
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);

        $response = $this->actingAs($this->empleado)->post("/reclamos/{$reclamo->id}/escalar");

        $response->assertRedirect(route('reclamos.index'));

        $reclamo->refresh();
        $this->assertTrue($reclamo->escalado);
        $this->assertEquals('en_proceso', $reclamo->estado);
    }

    #[Test]
    public function estado_cambia_de_pendiente_a_en_proceso_a_resuelto(): void
    {
        $reclamo = Reclamo::create([
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'servicio',
            'descripcion' => 'Flujo de estados completo.',
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);

        // pendiente → en_proceso
        $this->actingAs($this->admin)->put("/reclamos/{$reclamo->id}", ['estado' => 'en_proceso']);
        $this->assertEquals('en_proceso', $reclamo->fresh()->estado);

        // en_proceso → resuelto
        $this->actingAs($this->admin)->put("/reclamos/{$reclamo->id}", [
            'estado'     => 'resuelto',
            'resolucion' => 'Problema solucionado.',
        ]);
        $this->assertEquals('resuelto', $reclamo->fresh()->estado);
    }

    #[Test]
    public function solo_muestra_reclamos_de_la_sucursal_propia(): void
    {
        $otraSucursal = Sucursal::factory()->create();
        $otroVendedor = User::factory()->create(['sucursal_id' => $otraSucursal->id]);

        // Reclamo de nuestra sucursal
        Reclamo::create([
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo'        => 'producto',
            'descripcion' => 'Reclamo de sucursal propia.',
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);

        // Reclamo de otra sucursal
        Reclamo::create([
            'vendedor_id' => $otroVendedor->id,
            'sucursal_id' => $otraSucursal->id,
            'tipo'        => 'servicio',
            'descripcion' => 'Reclamo de otra sucursal.',
            'estado'      => 'pendiente',
            'escalado'    => false,
        ]);

        $response = $this->actingAs($this->empleado)->get('/reclamos');
        $response->assertStatus(200);

        $reclamos = $response->viewData('reclamos');
        $this->assertCount(1, $reclamos);
        $this->assertEquals($this->sucursal->id, $reclamos->first()->sucursal_id);
    }
}
