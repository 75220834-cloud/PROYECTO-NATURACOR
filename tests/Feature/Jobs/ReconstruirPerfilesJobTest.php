<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Recommendation\ReconstruirPerfilesJob;
use App\Models\Cliente;
use App\Models\ClientePadecimiento;
use App\Models\ClientePerfilAfinidad;
use App\Models\DetalleVenta;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReconstruirPerfilesJobTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $sucursal;
    private User $usuario;
    private Enfermedad $enfDigestivo;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursal = Sucursal::factory()->create(['activa' => true]);
        $this->usuario = User::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $this->enfDigestivo = Enfermedad::create([
            'nombre' => 'Digestión',
            'descripcion' => 'Pruebas Bloque 3',
            'categoria' => 'digestivo',
            'activa' => true,
        ]);

        $this->producto = Producto::factory()->create([
            'nombre' => 'Producto digestivo',
            'tipo' => 'natural',
            'stock' => 50,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $this->producto->enfermedades()->attach($this->enfDigestivo->id, [
            'instrucciones' => null,
            'orden' => 0,
        ]);
    }

    #[Test]
    public function el_job_implementa_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new ReconstruirPerfilesJob,
            'El job debe ser encolable para correr en background vía schedule.'
        );
    }

    #[Test]
    public function dispatch_encola_el_job(): void
    {
        Bus::fake();

        ReconstruirPerfilesJob::dispatch();

        Bus::assertDispatched(ReconstruirPerfilesJob::class);
    }

    #[Test]
    public function reconstruye_perfil_de_cliente_con_compras_recientes(): void
    {
        $cliente = Cliente::factory()->create();
        $this->crearVentaCompletadaConProducto($cliente, $this->producto);

        $this->assertSame(0, ClientePerfilAfinidad::where('cliente_id', $cliente->id)->count());

        (new ReconstruirPerfilesJob)->handle(app(\App\Services\Recommendation\PerfilSaludService::class));

        $perfil = ClientePerfilAfinidad::where('cliente_id', $cliente->id)->get();
        $this->assertGreaterThan(0, $perfil->count(),
            'Cliente con compra debería tener al menos 1 fila en cliente_perfil_afinidad.');
        $this->assertTrue($perfil->contains('enfermedad_id', $this->enfDigestivo->id));
    }

    #[Test]
    public function reconstruye_perfil_de_cliente_solo_con_padecimiento_declarado(): void
    {
        $cliente = Cliente::factory()->create();

        ClientePadecimiento::create([
            'cliente_id' => $cliente->id,
            'enfermedad_id' => $this->enfDigestivo->id,
            'registrado_por' => $this->usuario->id,
        ]);

        (new ReconstruirPerfilesJob)->handle(app(\App\Services\Recommendation\PerfilSaludService::class));

        $perfil = ClientePerfilAfinidad::where('cliente_id', $cliente->id)->get();
        $this->assertCount(1, $perfil,
            'Cliente sin compras pero con padecimiento declarado debe tener perfil (BUG 2 FIX).');

        $fila = $perfil->first();
        $this->assertSame($this->enfDigestivo->id, (int) $fila->enfermedad_id);
        $this->assertGreaterThanOrEqual(
            (float) config('recommendaciones.padecimiento_score_base', 0.80),
            (float) $fila->score,
            'Score de padecimiento declarado debe respetar el FLOOR configurable.'
        );
        $this->assertSame(0, (int) $fila->evidencia_count,
            'evidencia_count = 0 marca al perfil como "declarado puro".');
    }

    #[Test]
    public function ignora_clientes_sin_ventas_y_sin_padecimientos(): void
    {
        $clienteVacio = Cliente::factory()->create();
        $clienteActivo = Cliente::factory()->create();
        $this->crearVentaCompletadaConProducto($clienteActivo, $this->producto);

        (new ReconstruirPerfilesJob)->handle(app(\App\Services\Recommendation\PerfilSaludService::class));

        $this->assertSame(0, ClientePerfilAfinidad::where('cliente_id', $clienteVacio->id)->count(),
            'Cliente sin señal NO debe gastar tiempo de cómputo ni generar filas.');
        $this->assertGreaterThan(0, ClientePerfilAfinidad::where('cliente_id', $clienteActivo->id)->count());
    }

    #[Test]
    public function continua_si_un_cliente_falla_individualmente(): void
    {
        $clienteOk = Cliente::factory()->create();
        $clienteOk2 = Cliente::factory()->create();
        $this->crearVentaCompletadaConProducto($clienteOk, $this->producto);
        $this->crearVentaCompletadaConProducto($clienteOk2, $this->producto);

        $service = $this->createMock(\App\Services\Recommendation\PerfilSaludService::class);
        $service->expects($this->exactly(2))
            ->method('reconstruirPerfil')
            ->willReturnCallback(function (int $clienteId) use ($clienteOk) {
                if ($clienteId === $clienteOk->id) {
                    throw new \RuntimeException('Fallo simulado');
                }
            });

        // No debe lanzar excepción a pesar del fallo del primero
        (new ReconstruirPerfilesJob)->handle($service);

        $this->assertTrue(true, 'El job no aborta si un cliente falla.');
    }

    private function crearVentaCompletadaConProducto(Cliente $cliente, Producto $producto): Venta
    {
        $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => $this->usuario->id,
            'sucursal_id' => $this->sucursal->id,
            'subtotal' => 10,
            'igv' => 0,
            'total' => 10,
            'descuento_total' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'incluir_igv' => true,
        ]);
        $venta->update(['numero_boleta' => $venta->generarNumeroBoleta()]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'nombre_producto' => $producto->nombre,
            'precio_unitario' => 10,
            'cantidad' => 1,
            'descuento' => 0,
            'subtotal' => 10,
        ]);

        return $venta;
    }
}
