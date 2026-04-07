<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ClienteUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['naturacor.fidelizacion_monto'           => 500]);
    }

    #[Test]
    public function nombre_completo_combina_nombre_y_apellido(): void
    {
        $cliente = new Cliente([
            'nombre'   => 'María',
            'apellido' => 'García',
            'dni'      => '12345678',
        ]);

        $this->assertEquals('María García', $cliente->nombreCompleto());
    }

    #[Test]
    public function nombre_completo_sin_apellido_no_tiene_espacio_sobrante(): void
    {
        $cliente = new Cliente([
            'nombre'   => 'María',
            'apellido' => null,
            'dni'      => '12345678',
        ]);

        $this->assertEquals('María', $cliente->nombreCompleto());
    }

    #[Test]
    public function puede_reclamar_premio_cuando_acumulado_naturales_igual_a_umbral(): void
    {
        $cliente = new Cliente(['acumulado_naturales' => '500.00']);

        $this->assertTrue($cliente->puedeReclamarPremio());
    }

    #[Test]
    public function puede_reclamar_premio_cuando_acumulado_naturales_supera_umbral(): void
    {
        $cliente = new Cliente(['acumulado_naturales' => '650.00']);

        $this->assertTrue($cliente->puedeReclamarPremio());
    }

    #[Test]
    public function no_puede_reclamar_premio_cuando_acumulado_naturales_inferior_al_umbral(): void
    {
        $cliente = new Cliente(['acumulado_naturales' => '499.99']);

        $this->assertFalse($cliente->puedeReclamarPremio());
    }

    #[Test]
    public function puede_reclamar_premio_cuando_acumulado_naturales_exactamente_en_umbral(): void
    {
        $cliente = new Cliente(['acumulado_naturales' => '500.00']);

        $this->assertTrue($cliente->puedeReclamarPremio());
    }

    #[Test]
    public function no_puede_reclamar_premio_cuando_acumulado_naturales_muy_bajo(): void
    {
        $cliente = new Cliente(['acumulado_naturales' => '200.00']);

        $this->assertFalse($cliente->puedeReclamarPremio());
    }

    #[Test]
    public function reiniciar_acumulados_pone_naturales_en_cero(): void
    {
        $sucursal = Sucursal::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id]);
        $user->assignRole($role);

        Cliente::factory()->create([
            'acumulado_naturales'  => 200,
        ]);
        Cliente::factory()->create([
            'acumulado_naturales'  => 450,
        ]);

        $count = Cliente::reiniciarAcumulados();

        $this->assertEquals(2, $count);
        $this->assertEquals(0, (float) Cliente::first()->acumulado_naturales);
    }

    #[Test]
    public function cliente_tiene_relacion_ventas(): void
    {
        $sucursal = Sucursal::factory()->create();
        $cliente  = Cliente::factory()->create();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $cliente->ventas()
        );
    }

    #[Test]
    public function cliente_tiene_relacion_canjes(): void
    {
        $cliente = Cliente::factory()->create();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $cliente->canjes()
        );
    }

    #[Test]
    public function soft_delete_cliente_no_lo_elimina_fisicamente(): void
    {
        $cliente = Cliente::factory()->create();
        $id = $cliente->id;

        $cliente->delete();

        $this->assertNull(Cliente::find($id));
        $this->assertNotNull(Cliente::withTrashed()->find($id));
    }

    #[Test]
    public function acumulados_se_castean_como_decimal(): void
    {
        $cliente = Cliente::factory()->create([
            'acumulado_naturales' => '123.456',
        ]);

        $this->assertIsString($cliente->getRawOriginal('acumulado_naturales'));
        $this->assertEquals(123.46, round((float) $cliente->acumulado_naturales, 2));
    }
}
