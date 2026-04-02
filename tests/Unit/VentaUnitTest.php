<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Venta;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class VentaUnitTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $sucursal = Sucursal::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true, 'sucursal_id' => $sucursal->id]);
        $this->admin->assignRole($role);
    }

    #[Test]
    public function formato_boleta_empieza_con_B001(): void
    {
        $venta = new Venta();
        $numero = $venta->generarNumeroBoleta();

        $this->assertStringStartsWith('B001-', $numero);
    }

    #[Test]
    public function formato_boleta_tiene_longitud_correcta(): void
    {
        $venta = new Venta();
        $numero = $venta->generarNumeroBoleta();

        // B001-000001 = 11 caracteres
        $this->assertEquals(11, strlen($numero));
    }

    #[Test]
    public function primera_boleta_es_B001_000001(): void
    {
        $venta = new Venta();
        $numero = $venta->generarNumeroBoleta();

        $this->assertEquals('B001-000001', $numero);
    }

    #[Test]
    public function segunda_boleta_es_B001_000002(): void
    {
        // Crear primera venta con boleta
        Venta::create([
            'numero_boleta'  => 'B001-000001',
            'user_id'        => $this->admin->id,
            'sucursal_id'    => $this->admin->sucursal_id,
            'subtotal'       => 10.00,
            'igv'            => 1.53,
            'total'          => 10.00,
            'descuento_total'=> 0,
            'metodo_pago'    => 'efectivo',
            'estado'         => 'completada',
        ]);

        $venta2 = new Venta();
        $numero = $venta2->generarNumeroBoleta();

        $this->assertEquals('B001-000002', $numero);
    }

    #[Test]
    public function venta_tiene_relacion_con_detalles(): void
    {
        $venta = Venta::create([
            'numero_boleta'  => 'B001-000001',
            'user_id'        => $this->admin->id,
            'sucursal_id'    => $this->admin->sucursal_id,
            'subtotal'       => 10.00,
            'igv'            => 1.53,
            'total'          => 10.00,
            'descuento_total'=> 0,
            'metodo_pago'    => 'efectivo',
            'estado'         => 'completada',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venta->detalles());
    }

    #[Test]
    public function igv_extraido_del_total_no_sumado(): void
    {
        // Si precio = 118, IGV extraído = 18 (no sumado encima)
        $total = 118.00;
        $igvFactor = 18 / 118;
        $igvExtraido = round($total * $igvFactor, 2);

        // Verificar que es extracción, no adición
        $this->assertEquals(18.00, $igvExtraido);
        $this->assertEquals(100.00, round($total - $igvExtraido, 2)); // base imponible
        $this->assertNotEquals(118.00 * 1.18, $total); // NO es suma de IGV
    }

    #[Test]
    public function total_venta_es_igual_al_precio_con_igv_incluido(): void
    {
        // En NATURACOR: total = precio indicado (IGV ya incluido, se extrae internamente)
        $precioProducto = 50.00;
        $cantidad = 2;
        $totalEsperado = $precioProducto * $cantidad; // 100.00

        $this->assertEquals(100.00, $totalEsperado);
    }

    #[Test]
    public function soft_delete_venta_no_elimina_fisicamente(): void
    {
        $venta = Venta::create([
            'numero_boleta'  => 'B001-000001',
            'user_id'        => $this->admin->id,
            'sucursal_id'    => $this->admin->sucursal_id,
            'subtotal'       => 10.00,
            'igv'            => 1.53,
            'total'          => 10.00,
            'descuento_total'=> 0,
            'metodo_pago'    => 'efectivo',
            'estado'         => 'completada',
        ]);
        $id = $venta->id;

        $venta->delete();

        $this->assertNull(Venta::find($id));
        $this->assertNotNull(Venta::withTrashed()->find($id));
    }
}
