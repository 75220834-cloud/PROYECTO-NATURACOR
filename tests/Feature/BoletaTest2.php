<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class BoletaTest2 extends TestCase
{
    use RefreshDatabase;

    protected User $empleado;
    protected Sucursal $sucursal;
    protected Venta $venta;
    protected Venta $ventaConCliente;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sucursal = Sucursal::factory()->create();
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        $this->empleado = User::factory()->create([
            'activo'      => true,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $this->empleado->assignRole('empleado');

        // Venta sin cliente
        $this->venta = Venta::factory()->create([
            'sucursal_id'   => $this->sucursal->id,
            'user_id'       => $this->empleado->id,
            'total'         => 59.00,
            'subtotal'      => 50.00,
            'igv'           => 9.00,
            'metodo_pago'   => 'efectivo',
            'numero_boleta' => 'B001-000001',
            'cliente_id'    => null,
        ]);

        $producto = Producto::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'precio'      => 59.00,
            'stock'       => 10,
            'nombre'      => 'Producto Test Boleta',
        ]);

        DetalleVenta::factory()->create([
            'venta_id'        => $this->venta->id,
            'producto_id'     => $producto->id,
            'nombre_producto' => $producto->nombre,
            'cantidad'        => 1,
            'precio_unitario' => 59.00,
            'subtotal'        => 59.00,
        ]);

        // Cliente con telefono para whatsapp
        $this->cliente = Cliente::factory()->create([
            'nombre'   => 'Juan',
            'apellido' => 'Perez',
            'telefono' => '987654321',
        ]);

        // Venta con cliente que tiene telefono
        $this->ventaConCliente = Venta::factory()->create([
            'sucursal_id'   => $this->sucursal->id,
            'user_id'       => $this->empleado->id,
            'cliente_id'    => $this->cliente->id,
            'total'         => 30.00,
            'subtotal'      => 25.42,
            'igv'           => 4.58,
            'metodo_pago'   => 'yape',
            'numero_boleta' => 'B001-000002',
        ]);

        DetalleVenta::factory()->create([
            'venta_id'        => $this->ventaConCliente->id,
            'producto_id'     => $producto->id,
            'nombre_producto' => $producto->nombre,
            'cantidad'        => 1,
            'precio_unitario' => 30.00,
            'subtotal'        => 30.00,
        ]);
    }

    #[Test]
    public function whatsapp_con_telefono_redirige_a_wa_me(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->ventaConCliente->id}/whatsapp");
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('wa.me', $location);
    }

    #[Test]
    public function whatsapp_url_contiene_numero_con_prefijo_51(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->ventaConCliente->id}/whatsapp");
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('51987654321', $location);
    }

    #[Test]
    public function whatsapp_url_contiene_texto_naturacor(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->ventaConCliente->id}/whatsapp");
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('NATURACOR', urldecode($location));
    }

    #[Test]
    public function whatsapp_url_contiene_numero_boleta(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->ventaConCliente->id}/whatsapp");
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('B001-000002', urldecode($location));
    }

    #[Test]
    public function whatsapp_sin_telefono_retorna_error_con_mensaje(): void
    {
        $clienteSinTelefono = Cliente::factory()->create(['telefono' => null]);
        $ventaSinTel = Venta::factory()->create([
            'sucursal_id'   => $this->sucursal->id,
            'user_id'       => $this->empleado->id,
            'cliente_id'    => $clienteSinTelefono->id,
            'numero_boleta' => 'B001-000003',
        ]);
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$ventaSinTel->id}/whatsapp");
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function whatsapp_telefono_vacio_retorna_error(): void
    {
        $clienteTelVacio = Cliente::factory()->create(['telefono' => '']);
        $ventaTelVacio = Venta::factory()->create([
            'sucursal_id'   => $this->sucursal->id,
            'user_id'       => $this->empleado->id,
            'cliente_id'    => $clienteTelVacio->id,
            'numero_boleta' => 'B001-000004',
        ]);
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$ventaTelVacio->id}/whatsapp");
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function boleta_show_carga_relaciones(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->venta->id}");
        $response->assertSuccessful();
        $response->assertViewHas('venta');
        $venta = $response->viewData('venta');
        $this->assertTrue($venta->relationLoaded('detalles'));
        $this->assertTrue($venta->relationLoaded('empleado'));
    }

    #[Test]
    public function boleta_show_con_cliente_muestra_datos(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->ventaConCliente->id}");
        $response->assertSuccessful();
        $venta = $response->viewData('venta');
        $this->assertNotNull($venta->cliente);
        $this->assertEquals($this->cliente->id, $venta->cliente->id);
    }

    #[Test]
    public function ticket_carga_relaciones_correctamente(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->venta->id}/ticket");
        $response->assertSuccessful();
        $response->assertViewHas('venta');
        $venta = $response->viewData('venta');
        $this->assertTrue($venta->relationLoaded('detalles'));
    }

    #[Test]
    public function pdf_boleta_con_cliente(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->ventaConCliente->id}/pdf");
        $response->assertSuccessful();
    }

    #[Test]
    public function pdf_boleta_sin_cliente(): void
    {
        $response = $this->actingAs($this->empleado)
            ->get("/boletas/{$this->venta->id}/pdf");
        $response->assertSuccessful();
    }
}
