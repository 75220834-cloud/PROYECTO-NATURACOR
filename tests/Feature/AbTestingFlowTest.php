<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\RecomendacionEvento;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\Recommendation\AbTestingService;
use App\Services\Recommendation\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bloque 4 — Feature tests del flujo A/B end-to-end.
 *
 * Verifica que:
 *  · Con A/B desactivado, el comportamiento previo del recomendador
 *    se preserva (sin regresión) y los eventos quedan etiquetados como 'sin_ab'.
 *  · Con A/B activo + porcentaje_control=100, TODO cliente cae en control
 *    y la API devuelve items=[] con meta.grupo_ab='control'.
 *  · Con A/B activo + porcentaje_control=0, TODO cliente cae en tratamiento
 *    y los eventos "mostrada" registrados quedan con grupo_ab='tratamiento'.
 *  · `MetricsService::comparativaAbTesting()` agrega correctamente las
 *    ventas por grupo y delega el t-test al AbTestingService.
 */
class AbTestingFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Helpers de seedeo común a varios tests. */
    private function seedUsuarioYProductos(): array
    {
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);

        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $user->assignRole('empleado');

        $cliente = Cliente::factory()->create();

        $enfermedad = Enfermedad::create([
            'nombre' => 'Digestión AB',
            'categoria' => 'digestivo',
            'activa' => true,
        ]);

        $producto = Producto::factory()->create([
            'nombre' => 'Producto AB',
            'tipo' => 'natural',
            'stock' => 50,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);
        $producto->enfermedades()->attach($enfermedad->id, ['instrucciones' => null, 'orden' => 0]);

        // Venta histórica del cliente para que tenga señal de perfil.
        $venta = Venta::create([
            'cliente_id'      => $cliente->id,
            'user_id'         => $user->id,
            'sucursal_id'     => $sucursal->id,
            'subtotal'        => 10,
            'igv'             => 0,
            'total'           => 10,
            'descuento_total' => 0,
            'metodo_pago'     => 'efectivo',
            'estado'          => 'completada',
            'incluir_igv'     => true,
        ]);
        $venta->update(['numero_boleta' => $venta->generarNumeroBoleta()]);
        DetalleVenta::create([
            'venta_id'        => $venta->id,
            'producto_id'     => $producto->id,
            'nombre_producto' => $producto->nombre,
            'precio_unitario' => 10,
            'cantidad'        => 1,
            'descuento'       => 0,
            'subtotal'        => 10,
        ]);

        return compact('sucursal', 'user', 'cliente', 'producto');
    }

    #[Test]
    public function ab_desactivado_devuelve_recomendaciones_y_eventos_sin_ab(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', false);
        Cache::flush();

        ['user' => $user, 'cliente' => $cliente] = $this->seedUsuarioYProductos();

        $res = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=5&refresh=1");
        $res->assertOk();

        $this->assertSame(AbTestingService::GRUPO_SIN_AB, $res->json('meta.grupo_ab'));
        $this->assertNotEmpty($res->json('items'), 'A/B desactivado debe devolver items normalmente.');

        // Las mostradas deben quedar marcadas como sin_ab.
        $eventos = RecomendacionEvento::where('accion', RecomendacionEvento::ACCION_MOSTRADA)->get();
        $this->assertGreaterThan(0, $eventos->count());
        foreach ($eventos as $e) {
            $this->assertSame(AbTestingService::GRUPO_SIN_AB, $e->grupo_ab);
        }
    }

    #[Test]
    public function ab_activo_con_100pct_control_devuelve_items_vacios(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 100);
        Cache::flush();

        ['user' => $user, 'cliente' => $cliente] = $this->seedUsuarioYProductos();

        $res = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=5&refresh=1");
        $res->assertOk();

        $this->assertSame(AbTestingService::GRUPO_CONTROL, $res->json('meta.grupo_ab'));
        $this->assertSame([], $res->json('items'));
        $this->assertNull($res->json('meta.reco_sesion_id'));
        // El controller corta antes del engine: NO hay eventos registrados.
        $this->assertSame(0, RecomendacionEvento::count());
    }

    #[Test]
    public function ab_activo_con_0pct_control_devuelve_items_y_etiqueta_tratamiento(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 0);
        Cache::flush();

        ['user' => $user, 'cliente' => $cliente] = $this->seedUsuarioYProductos();

        $res = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=5&refresh=1");
        $res->assertOk();

        $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $res->json('meta.grupo_ab'));
        $this->assertNotEmpty($res->json('items'));
        $this->assertNotNull($res->json('meta.reco_sesion_id'));

        // Las mostradas deben quedar etiquetadas como tratamiento.
        $eventos = RecomendacionEvento::where('accion', RecomendacionEvento::ACCION_MOSTRADA)->get();
        $this->assertGreaterThan(0, $eventos->count());
        foreach ($eventos as $e) {
            $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $e->grupo_ab,
                "evento mostrada producto={$e->producto_id} debería ser 'tratamiento'");
        }
    }

    #[Test]
    public function comparativa_ab_testing_agrega_correctamente_por_grupo(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.tamano_muestra_minimo', 2);

        ['user' => $user, 'sucursal' => $sucursal, 'cliente' => $cliente] = $this->seedUsuarioYProductos();

        // Sembramos 6 ventas: 3 control (S/100, 105, 95) y 3 tratamiento (S/150, 160, 140).
        $totalesControl = [100.00, 105.00, 95.00];
        $totalesTrat = [150.00, 160.00, 140.00];

        foreach ($totalesControl as $i => $total) {
            $v = Venta::create([
                'cliente_id'      => $cliente->id,
                'user_id'         => $user->id,
                'sucursal_id'     => $sucursal->id,
                'subtotal'        => $total,
                'igv'             => 0,
                'total'           => $total,
                'descuento_total' => 0,
                'metodo_pago'     => 'efectivo',
                'estado'          => 'completada',
                'incluir_igv'     => true,
                'grupo_ab'        => AbTestingService::GRUPO_CONTROL,
            ]);
            $v->update(['numero_boleta' => 'BC-'.($i + 1)]);
        }
        foreach ($totalesTrat as $i => $total) {
            $v = Venta::create([
                'cliente_id'      => $cliente->id,
                'user_id'         => $user->id,
                'sucursal_id'     => $sucursal->id,
                'subtotal'        => $total,
                'igv'             => 0,
                'total'           => $total,
                'descuento_total' => 0,
                'metodo_pago'     => 'efectivo',
                'estado'          => 'completada',
                'incluir_igv'     => true,
                'grupo_ab'        => AbTestingService::GRUPO_TRATAMIENTO,
            ]);
            $v->update(['numero_boleta' => 'BT-'.($i + 1)]);
        }

        /** @var MetricsService $metrics */
        $metrics = app(MetricsService::class);
        $ab = app(AbTestingService::class);

        $comp = $metrics->comparativaAbTesting(30, $sucursal->id, $ab);

        $this->assertTrue($comp['activo']);
        $this->assertSame(3, $comp['ticket']['control']['n']);
        $this->assertSame(3, $comp['ticket']['tratamiento']['n']);
        $this->assertEqualsWithDelta(100.0, $comp['ticket']['control']['media'], 0.01);
        $this->assertEqualsWithDelta(150.0, $comp['ticket']['tratamiento']['media'], 0.01);

        $test = $comp['ticket']['test'];
        $this->assertNotNull($test['t_statistic']);
        $this->assertGreaterThan(0.0, $test['t_statistic']); // tratamiento > control
        $this->assertNotNull($test['p_value_aprox']);
        $this->assertNotNull($test['cohens_d']);
        $this->assertGreaterThan(0.0, $test['cohens_d']);
    }

    #[Test]
    public function venta_post_estampa_grupo_ab_segun_cliente(): void
    {
        // Test directo sobre el AbTestingService (sin pasar por POS):
        // verifica que con A/B activo y 100% control, una venta del cliente
        // queda etiquetada como 'control' al persistirla con stamp manual.
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 100);

        ['user' => $user, 'sucursal' => $sucursal, 'cliente' => $cliente] = $this->seedUsuarioYProductos();
        $ab = app(AbTestingService::class);

        $venta = Venta::create([
            'cliente_id'      => $cliente->id,
            'user_id'         => $user->id,
            'sucursal_id'     => $sucursal->id,
            'subtotal'        => 50,
            'igv'             => 0,
            'total'           => 50,
            'descuento_total' => 0,
            'metodo_pago'     => 'efectivo',
            'estado'          => 'completada',
            'incluir_igv'     => true,
            'grupo_ab'        => $ab->asignarGrupo($cliente->id),
        ]);

        $this->assertSame(AbTestingService::GRUPO_CONTROL, $venta->fresh()->grupo_ab);
    }

    #[Test]
    public function venta_sin_cliente_queda_marcada_como_tratamiento(): void
    {
        // Walk-in (sin cliente_id): no podemos hacer asignación estable,
        // así que el contrato es "tratamiento" (recibe recos).
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 50);

        ['user' => $user, 'sucursal' => $sucursal] = $this->seedUsuarioYProductos();
        $ab = app(AbTestingService::class);

        $venta = Venta::create([
            'cliente_id'      => null,
            'user_id'         => $user->id,
            'sucursal_id'     => $sucursal->id,
            'subtotal'        => 30,
            'igv'             => 0,
            'total'           => 30,
            'descuento_total' => 0,
            'metodo_pago'     => 'efectivo',
            'estado'          => 'completada',
            'incluir_igv'     => true,
            'grupo_ab'        => $ab->asignarGrupo(null),
        ]);

        $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $venta->fresh()->grupo_ab);
    }
}
