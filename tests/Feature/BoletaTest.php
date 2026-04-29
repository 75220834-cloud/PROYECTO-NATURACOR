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

class BoletaTest extends TestCase
{
    use RefreshDatabase;

    protected User $empleado;
    protected Sucursal $sucursal;
    protected Venta $venta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sucursal = Sucursal::factory()->create();
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->empleado = User::factory()->create([
            'activo'      => true,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $this->empleado->assignRole('empleado');

        $this->venta = Venta::factory()->create([
            'sucursal_id'   => $this->sucursal->id,
            'user_id'       => $this->empleado->id,
            'total'         => 59.00,
            'subtotal'      => 50.00,
            'igv'           => 9.00,
            'metodo_pago'   => 'efectivo',
            'numero_boleta' => 'B001-000001',
        ]);

        $producto = Producto::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'precio'      => 59.00,
            'stock'       => 10,
        ]);

        DetalleVenta::factory()->create([
            'venta_id'        => $this->venta->id,
            'producto_id'     => $producto->id,
            'nombre_producto' => $producto->nombre,
            'cantidad'        => 1,
            'precio_unitario' => 59.00,
            'subtotal'        => 59.00,
        ]);
    }

    #[Test]
    public function puede_ver_boleta(): void
    {
        $response = $this->actingAs($this->empleado)->get("/boletas/{$this->venta->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_descargar_boleta_pdf(): void
    {
        $response = $this->actingAs($this->empleado)->get("/boletas/{$this->venta->id}/pdf");
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_ticket_de_boleta(): void
    {
        $response = $this->actingAs($this->empleado)->get("/boletas/{$this->venta->id}/ticket");
        $response->assertSuccessful();
    }

    #[Test]
    public function whatsapp_sin_telefono_retorna_error(): void
    {
        $response = $this->actingAs($this->empleado)->get("/boletas/{$this->venta->id}/whatsapp");
        $response->assertRedirect();
    }

    #[Test]
    public function boleta_no_autenticado_redirige_login(): void
    {
        $response = $this->get("/boletas/{$this->venta->id}");
        $response->assertRedirect('/login');
    }
}
