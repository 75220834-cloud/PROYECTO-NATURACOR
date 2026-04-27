<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Recommendation\ReconstruirCoocurrenciaJob;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\ProductoCoocurrencia;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\Recommendation\CoocurrenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReconstruirCoocurrenciaJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_job_implementa_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new ReconstruirCoocurrenciaJob,
            'El job de co-ocurrencia debe ser encolable.'
        );
    }

    #[Test]
    public function dispatch_encola_el_job(): void
    {
        Bus::fake();

        ReconstruirCoocurrenciaJob::dispatch();

        Bus::assertDispatched(ReconstruirCoocurrenciaJob::class);
    }

    #[Test]
    public function recomputa_y_persiste_pares_jaccard(): void
    {
        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);

        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $sucursal->id]);

        for ($i = 0; $i < 3; $i++) {
            $v = Venta::create([
                'cliente_id' => null,
                'user_id' => $user->id,
                'sucursal_id' => $sucursal->id,
                'subtotal' => 0, 'igv' => 0, 'total' => 0, 'descuento_total' => 0,
                'metodo_pago' => 'efectivo', 'estado' => 'completada', 'incluir_igv' => true,
            ]);
            $v->update(['numero_boleta' => $v->generarNumeroBoleta()]);
            foreach ([$A, $B] as $p) {
                DetalleVenta::create([
                    'venta_id' => $v->id, 'producto_id' => $p->id,
                    'nombre_producto' => $p->nombre, 'precio_unitario' => 1,
                    'cantidad' => 1, 'descuento' => 0, 'subtotal' => 1,
                ]);
            }
        }

        (new ReconstruirCoocurrenciaJob(diasVentana: 30, minCoCount: 2, metrica: 'jaccard'))
            ->handle(app(CoocurrenciaService::class));

        $this->assertSame(1, ProductoCoocurrencia::query()->count(),
            'Se debe persistir exactamente el par (A,B).');
        $par = ProductoCoocurrencia::query()->first();
        $this->assertEqualsWithDelta(1.0, (float) $par->score, 1e-5,
            'A y B siempre juntos → Jaccard = 1.');
    }

    #[Test]
    public function el_job_es_idempotente(): void
    {
        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $sucursal->id]);

        for ($i = 0; $i < 2; $i++) {
            $v = Venta::create([
                'cliente_id' => null, 'user_id' => $user->id, 'sucursal_id' => $sucursal->id,
                'subtotal' => 0, 'igv' => 0, 'total' => 0, 'descuento_total' => 0,
                'metodo_pago' => 'efectivo', 'estado' => 'completada', 'incluir_igv' => true,
            ]);
            $v->update(['numero_boleta' => $v->generarNumeroBoleta()]);
            foreach ([$A, $B] as $p) {
                DetalleVenta::create([
                    'venta_id' => $v->id, 'producto_id' => $p->id,
                    'nombre_producto' => $p->nombre, 'precio_unitario' => 1,
                    'cantidad' => 1, 'descuento' => 0, 'subtotal' => 1,
                ]);
            }
        }

        $job = new ReconstruirCoocurrenciaJob(diasVentana: 30, minCoCount: 2, metrica: 'jaccard');
        $job->handle(app(CoocurrenciaService::class));
        $primerConteo = ProductoCoocurrencia::query()->count();

        $job->handle(app(CoocurrenciaService::class));
        $segundoConteo = ProductoCoocurrencia::query()->count();

        $this->assertSame($primerConteo, $segundoConteo,
            'Ejecutar el job dos veces seguidas debe dar exactamente el mismo conjunto.');
    }
}
