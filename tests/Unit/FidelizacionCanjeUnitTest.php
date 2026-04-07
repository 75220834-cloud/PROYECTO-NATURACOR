<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\FidelizacionCanje;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class FidelizacionCanjeUnitTest extends TestCase
{
    use RefreshDatabase;

    protected Cliente $cliente;
    protected Venta $venta;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $sucursal = Sucursal::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['sucursal_id' => $sucursal->id]);
        $this->admin->assignRole($role);

        $this->cliente = Cliente::factory()->create();
        $this->venta   = Venta::create([
            'numero_boleta'  => 'B001-000001',
            'user_id'        => $this->admin->id,
            'sucursal_id'    => $sucursal->id,
            'subtotal'       => 100.00,
            'igv'            => 15.25,
            'total'          => 100.00,
            'descuento_total'=> 0,
            'metodo_pago'    => 'efectivo',
            'estado'         => 'completada',
        ]);
    }

    #[Test]
    public function puede_crear_canje_regla1_500(): void
    {
        $canje = FidelizacionCanje::create([
            'cliente_id'         => $this->cliente->id,
            'venta_id'           => $this->venta->id,
            'tipo_regla'         => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio'       => 0,
            'descripcion'        => 'Test',
            'descripcion_premio' => 'Botella 2L de Bebida Nopal gratis',
            'entregado'          => false,
        ]);

        $this->assertDatabaseHas('fidelizacion_canjes', [
            'tipo_regla'  => 'regla1_500',
            'entregado'   => 0,
        ]);
        $this->assertFalse($canje->entregado);
    }



    #[Test]
    public function constantes_de_regla_son_correctas(): void
    {
        $this->assertEquals('regla1_500', FidelizacionCanje::REGLA_NATURALES);
    }

    #[Test]
    public function marcar_como_entregado_actualiza_campos(): void
    {
        $canje = FidelizacionCanje::create([
            'cliente_id'         => $this->cliente->id,
            'tipo_regla'         => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio'       => 0,
            'descripcion_premio' => 'Botella 2L de Bebida Nopal gratis',
            'entregado'          => false,
        ]);

        $canje->update([
            'entregado'    => true,
            'entregado_at' => now(),
        ]);

        $this->assertTrue($canje->fresh()->entregado);
        $this->assertNotNull($canje->fresh()->entregado_at);
    }

    #[Test]
    public function scope_pendientes_excluye_entregados(): void
    {
        FidelizacionCanje::create([
            'cliente_id'   => $this->cliente->id,
            'tipo_regla'   => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio' => 0,
            'entregado'    => false,
        ]);
        FidelizacionCanje::create([
            'cliente_id'   => $this->cliente->id,
            'tipo_regla'   => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio' => 0,
            'entregado'    => true,
            'entregado_at' => now(),
        ]);

        $pendientes = FidelizacionCanje::pendientes()->get();
        $this->assertCount(1, $pendientes);
        $this->assertFalse($pendientes->first()->entregado);
    }

    #[Test]
    public function relacion_cliente_es_belongs_to(): void
    {
        $canje = FidelizacionCanje::create([
            'cliente_id'   => $this->cliente->id,
            'tipo_regla'   => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio' => 0,
            'entregado'    => false,
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $canje->cliente()
        );
        $this->assertEquals($this->cliente->id, $canje->cliente->id);
    }

    #[Test]
    public function relacion_venta_es_belongs_to(): void
    {
        $canje = FidelizacionCanje::create([
            'cliente_id'   => $this->cliente->id,
            'venta_id'     => $this->venta->id,
            'tipo_regla'   => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio' => 0,
            'entregado'    => false,
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $canje->venta()
        );
        $this->assertEquals($this->venta->id, $canje->venta->id);
    }

    #[Test]
    public function valor_premio_se_castea_como_decimal(): void
    {
        $canje = FidelizacionCanje::create([
            'cliente_id'   => $this->cliente->id,
            'tipo_regla'   => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio' => 40.00,
            'entregado'    => false,
        ]);

        $this->assertEquals(40.00, (float) $canje->valor_premio);
    }

    #[Test]
    public function descripcion_premio_se_guarda_correctamente(): void
    {
        $canje = FidelizacionCanje::create([
            'cliente_id'         => $this->cliente->id,
            'tipo_regla'         => FidelizacionCanje::REGLA_NATURALES,
            'valor_premio'       => 0,
            'descripcion_premio' => 'Botella 2L de Bebida Nopal gratis',
            'entregado'          => false,
        ]);

        $this->assertEquals('Botella 2L de Bebida Nopal gratis', $canje->fresh()->descripcion_premio);
    }
}
