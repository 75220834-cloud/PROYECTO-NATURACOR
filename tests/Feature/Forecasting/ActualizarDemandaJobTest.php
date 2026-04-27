<?php

namespace Tests\Feature\Forecasting;

use App\Jobs\Recommendation\ActualizarDemandaJob;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\ProductoDemandaSemana;
use App\Models\ProductoPrediccionDemanda;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\Forecasting\DemandaForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloque 5 — Feature tests del job semanal de pronóstico SES.
 *
 * Cubre la integración end-to-end del pipeline:
 *   ventas reales → producto_demanda_semana → SES → producto_prediccion_demanda.
 */
class ActualizarDemandaJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // restaura el reloj real entre tests
        parent::tearDown();
    }

    /**
     * Crea una venta con created_at backdateado y un detalle del producto.
     *
     * Usamos `Carbon::setTestNow($fecha)` antes de `Venta::create()` porque
     * el modelo NO incluye `created_at` en $fillable (lo cual es correcto
     * por seguridad), por lo que pasarlo como atributo no surte efecto.
     * Eloquent fija el timestamp con Carbon::now(), así que controlando el
     * reloj global obtenemos el efecto que queremos.
     */
    private function ventaConDetalleEnFecha(
        Sucursal $sucursal,
        User $user,
        Producto $producto,
        Carbon $fecha,
        int $cantidad,
    ): Venta {
        Carbon::setTestNow($fecha);

        $v = Venta::create([
            'cliente_id' => null, 'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
            'subtotal' => 0, 'igv' => 0, 'total' => 0, 'descuento_total' => 0,
            'metodo_pago' => 'efectivo', 'estado' => 'completada', 'incluir_igv' => true,
        ]);
        $v->update(['numero_boleta' => $v->generarNumeroBoleta()]);

        DetalleVenta::create([
            'venta_id' => $v->id, 'producto_id' => $producto->id,
            'nombre_producto' => $producto->nombre,
            'precio_unitario' => 1, 'cantidad' => $cantidad,
            'descuento' => 0, 'subtotal' => $cantidad,
        ]);

        Carbon::setTestNow();

        return $v;
    }

    #[Test]
    public function el_job_implementa_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new ActualizarDemandaJob,
            'ActualizarDemandaJob debe ser encolable para no bloquear schedule:run.'
        );
    }

    #[Test]
    public function dispatch_encola_el_job(): void
    {
        Bus::fake();

        ActualizarDemandaJob::dispatch();

        Bus::assertDispatched(ActualizarDemandaJob::class);
    }

    #[Test]
    public function materializa_historico_y_predice_para_producto_con_historia_suficiente(): void
    {
        // Bajamos el umbral para no necesitar 8 semanas reales en CI.
        config()->set('recommendaciones.forecast.min_observaciones', 4);
        config()->set('recommendaciones.forecast.historia_semanas', 12);

        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $producto = Producto::factory()->create([
            'stock' => 100, 'sucursal_id' => $sucursal->id,
        ]);

        // Generamos 6 semanas de ventas, una venta por semana, distintas cantidades.
        $cantidades = [10, 12, 11, 13, 12, 14];
        foreach ($cantidades as $i => $cant) {
            $fecha = Carbon::now()->subWeeks(count($cantidades) - $i)->startOfWeek()->addDays(2);
            $this->ventaConDetalleEnFecha($sucursal, $user, $producto, $fecha, $cant);
        }

        (new ActualizarDemandaJob)->handle(app(DemandaForecastService::class));

        // 6 semanas distintas → 6 filas en el histórico.
        $this->assertSame(6, ProductoDemandaSemana::query()
            ->where('producto_id', $producto->id)->count(),
            'Debe haber una fila por (producto, sucursal, semana ISO).');

        // Una predicción para la próxima semana del par (producto, sucursal).
        $pred = ProductoPrediccionDemanda::query()
            ->where('producto_id', $producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->first();

        $this->assertNotNull($pred, 'El job debe persistir una predicción.');
        $this->assertSame('SES', $pred->modelo);
        $this->assertSame(6, $pred->n_observaciones);
        $this->assertGreaterThan(0, (float) $pred->prediccion);
        $this->assertNotNull($pred->mae);
        $this->assertNotNull($pred->mape);
    }

    #[Test]
    public function omite_productos_sin_historia_suficiente(): void
    {
        config()->set('recommendaciones.forecast.min_observaciones', 8);

        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $sucursal->id]);

        // Solo 3 semanas de ventas → por debajo del umbral (8).
        for ($i = 0; $i < 3; $i++) {
            $fecha = Carbon::now()->subWeeks(3 - $i)->startOfWeek()->addDays(1);
            $this->ventaConDetalleEnFecha($sucursal, $user, $producto, $fecha, 5);
        }

        (new ActualizarDemandaJob)->handle(app(DemandaForecastService::class));

        $this->assertSame(0, ProductoPrediccionDemanda::query()->count(),
            'No debe predecir cuando faltan observaciones.');
        $this->assertSame(3, ProductoDemandaSemana::query()->count(),
            'Pero sí debe materializar el histórico parcial.');
    }

    #[Test]
    public function el_job_es_idempotente_no_duplica_filas(): void
    {
        config()->set('recommendaciones.forecast.min_observaciones', 4);

        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 50, 'sucursal_id' => $sucursal->id]);

        for ($i = 0; $i < 5; $i++) {
            $fecha = Carbon::now()->subWeeks(5 - $i)->startOfWeek()->addDays(2);
            $this->ventaConDetalleEnFecha($sucursal, $user, $producto, $fecha, 8);
        }

        $job = new ActualizarDemandaJob;
        $job->handle(app(DemandaForecastService::class));
        $hist1 = ProductoDemandaSemana::query()->count();
        $pred1 = ProductoPrediccionDemanda::query()->count();

        $job->handle(app(DemandaForecastService::class));
        $hist2 = ProductoDemandaSemana::query()->count();
        $pred2 = ProductoPrediccionDemanda::query()->count();

        $this->assertSame($hist1, $hist2, 'El histórico no debe duplicarse al re-correr el job.');
        $this->assertSame($pred1, $pred2, 'Las predicciones tampoco deben duplicarse.');
    }

    #[Test]
    public function productos_en_riesgo_se_listan_cuando_prediccion_excede_stock(): void
    {
        config()->set('recommendaciones.forecast.min_observaciones', 3);

        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        // Stock muy bajo: la predicción inevitablemente lo excederá.
        $productoRiesgo = Producto::factory()->create([
            'nombre' => 'NopalEnRiesgo', 'stock' => 1, 'sucursal_id' => $sucursal->id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $fecha = Carbon::now()->subWeeks(5 - $i)->startOfWeek()->addDays(2);
            $this->ventaConDetalleEnFecha($sucursal, $user, $productoRiesgo, $fecha, 20);
        }

        $service = app(DemandaForecastService::class);
        (new ActualizarDemandaJob)->handle($service);

        $rows = $service->productosEnRiesgoStock($sucursal->id, 10);

        $this->assertNotEmpty($rows, 'El widget debe listar al menos un producto en riesgo.');
        $this->assertSame('NopalEnRiesgo', $rows[0]['nombre']);
        $this->assertGreaterThan(0, $rows[0]['deficit'],
            'El déficit (predicción − stock) debe ser positivo.');
    }
}
