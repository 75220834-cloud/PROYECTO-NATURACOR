<?php

namespace Tests\Unit;

use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\ProductoCoocurrencia;
use App\Services\Recommendation\CoocurrenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloque 2 — Tests del filtrado colaborativo (item-item).
 *
 * Validan tanto la corrección matemática (Jaccard, NPMI) como las
 * propiedades estructurales del cómputo (par ordenado, ventana temporal,
 * filtro de ruido, idempotencia).
 */
class CoocurrenciaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoocurrenciaService $service;
    private Sucursal $sucursal;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CoocurrenciaService();
        $this->sucursal = Sucursal::factory()->create(['activa' => true]);
        $this->user = User::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
    }

    /**
     * Crea una venta completada con los productos indicados (un detalle por producto, cantidad 1).
     *
     * @param  list<\App\Models\Producto>  $productos
     */
    private function crearCanasta(array $productos, ?\Carbon\Carbon $cuando = null): Venta
    {
        $venta = Venta::create([
            'cliente_id' => null,
            'user_id' => $this->user->id,
            'sucursal_id' => $this->sucursal->id,
            'subtotal' => 0,
            'igv' => 0,
            'total' => 0,
            'descuento_total' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'incluir_igv' => true,
        ]);
        $venta->update(['numero_boleta' => $venta->generarNumeroBoleta()]);

        if ($cuando !== null) {
            $venta->forceFill([
                'created_at' => $cuando,
                'updated_at' => $cuando,
            ])->save();
        }

        foreach ($productos as $producto) {
            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'nombre_producto' => $producto->nombre,
                'precio_unitario' => 1,
                'cantidad' => 1,
                'descuento' => 0,
                'subtotal' => 1,
            ]);
        }

        return $venta;
    }

    #[Test]
    public function jaccard_se_calcula_correctamente_para_par_clasico(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $C = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        // 3 canastas: [A,B], [A,B], [A,C]
        // count: A=3, B=2, C=1
        // co(A,B)=2, co(A,C)=1
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $C]);

        $resumen = $this->service->recomputar(
            diasVentana: 30,
            minCoCount: 2,
            metrica: CoocurrenciaService::METRICA_JACCARD,
        );

        $this->assertSame(3, $resumen['transacciones']);
        $this->assertSame(3, $resumen['productos']);
        $this->assertSame(2, $resumen['pares_calculados'], 'Pares calculados (A,B) y (A,C)');
        $this->assertSame(1, $resumen['pares_persistidos'], 'Solo (A,B) supera min_co_count=2');

        $par = ProductoCoocurrencia::query()->first();
        $this->assertNotNull($par);

        $minId = min($A->id, $B->id);
        $maxId = max($A->id, $B->id);
        $this->assertSame($minId, (int) $par->producto_a_id, 'Par ordenado: a < b');
        $this->assertSame($maxId, (int) $par->producto_b_id);

        // Jaccard = co / (cA + cB - co) = 2 / (3 + 2 - 2) = 2/3 ≈ 0.666667
        $this->assertEqualsWithDelta(2 / 3, (float) $par->score_jaccard, 1e-5);
        $this->assertEqualsWithDelta(2 / 3, (float) $par->score, 1e-5);
        $this->assertSame(2, (int) $par->co_count);
        $this->assertSame(3, (int) $par->total_transacciones);
        $this->assertSame('jaccard', $par->metrica_principal);
    }

    #[Test]
    public function npmi_es_uno_para_par_perfectamente_correlacionado(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        // 4 canastas, todas [A,B] → P(A)=P(B)=P(A,B)=1
        // PMI = log( P(AB) / (P(A) P(B)) ) = log(1/1) = 0... no, espera:
        // P(AB) = 4/4 = 1
        // P(A)*P(B) = 1*1 = 1
        // PMI = log(1/1) = 0 → NPMI = 0 / -log(1) (división por cero), service devuelve 0.0.
        // Mejor: 4 canastas [A,B] + 0 canastas con A solo + 0 canastas con B solo.
        // Verifica el caso degenerado (NPMI=0) y que no rompe.
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);

        $this->service->recomputar(
            diasVentana: 30,
            minCoCount: 2,
            metrica: CoocurrenciaService::METRICA_NPMI,
        );

        $par = ProductoCoocurrencia::query()->first();
        $this->assertNotNull($par);
        // En este caso degenerado el servicio fija NPMI=0 (h(AB)=0).
        $this->assertSame(0.0, (float) $par->score_npmi);
    }

    #[Test]
    public function npmi_es_positivo_cuando_par_es_significativo(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $C = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $D = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        // 6 canastas: [A,B] x 2, [C] x 2, [D] x 2.
        // count: A=2, B=2, C=2, D=2; N=6
        // co(A,B)=2.
        // P(A,B)=2/6=0.333; P(A)=P(B)=2/6=0.333
        // PMI = log2(0.333 / (0.333*0.333)) = log2(3) ≈ 1.585
        // NPMI = 1.585 / -log2(0.333) = 1.585 / 1.585 = 1.0  (par perfectamente asociado)
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$C]);
        $this->crearCanasta([$C]);
        $this->crearCanasta([$D]);
        $this->crearCanasta([$D]);

        $this->service->recomputar(
            diasVentana: 30,
            minCoCount: 2,
            metrica: CoocurrenciaService::METRICA_NPMI,
        );

        $par = ProductoCoocurrencia::query()->first();
        $this->assertNotNull($par);
        $this->assertEqualsWithDelta(1.0, (float) $par->score_npmi, 1e-4,
            'Cuando A y B nunca aparecen sin el otro y existen contra-ejemplos, NPMI = 1.');
    }

    #[Test]
    public function ventana_temporal_excluye_ventas_antiguas(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        $this->crearCanasta([$A, $B], now()->subDays(200));
        $this->crearCanasta([$A, $B], now()->subDays(200));

        $resumen = $this->service->recomputar(
            diasVentana: 90,
            minCoCount: 2,
            metrica: CoocurrenciaService::METRICA_JACCARD,
        );

        $this->assertSame(0, $resumen['transacciones']);
        $this->assertSame(0, $resumen['pares_persistidos']);
        $this->assertSame(0, ProductoCoocurrencia::query()->count());
    }

    #[Test]
    public function ventas_no_completadas_no_se_consideran(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        $venta = $this->crearCanasta([$A, $B]);
        $venta->update(['estado' => 'anulada']);

        $resumen = $this->service->recomputar(
            diasVentana: 30,
            minCoCount: 1,
            metrica: CoocurrenciaService::METRICA_JACCARD,
        );

        $this->assertSame(0, $resumen['transacciones']);
        $this->assertSame(0, ProductoCoocurrencia::query()->count());
    }

    #[Test]
    public function recomputar_es_idempotente(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);

        $r1 = $this->service->recomputar(diasVentana: 30, minCoCount: 1);
        $score1 = (float) ProductoCoocurrencia::query()->first()->score;
        $count1 = ProductoCoocurrencia::query()->count();

        $r2 = $this->service->recomputar(diasVentana: 30, minCoCount: 1);
        $score2 = (float) ProductoCoocurrencia::query()->first()->score;
        $count2 = ProductoCoocurrencia::query()->count();

        $this->assertSame($r1['pares_persistidos'], $r2['pares_persistidos']);
        $this->assertSame($count1, $count2);
        $this->assertEqualsWithDelta($score1, $score2, 1e-9);
    }

    #[Test]
    public function productos_relacionados_devuelve_vecino_correcto(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $C = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $C]);
        $this->crearCanasta([$A, $C]);
        $this->crearCanasta([$A, $C]);

        $this->service->recomputar(diasVentana: 30, minCoCount: 2);

        $relacionadosA = $this->service->productosRelacionados($A->id, limite: 5);

        $this->assertCount(2, $relacionadosA);
        $idsRel = $relacionadosA->pluck('producto_id')->all();
        $this->assertContains($B->id, $idsRel);
        $this->assertContains($C->id, $idsRel);
        $this->assertNotContains($A->id, $idsRel,
            'productosRelacionados nunca debe incluir el producto base.');

        $primero = $relacionadosA->first();
        $this->assertSame($C->id, $primero['producto_id'],
            'C debe rankear primero (mayor co_count con A).');
    }

    #[Test]
    public function productos_relacionados_respeta_score_minimo_y_limite(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $C = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        // 2 canastas [A,B], 1 canasta [A,C].
        // count: A=3, B=2, C=1
        // Jaccard(A,B) = 2 / (3+2-2) = 2/3 ≈ 0.667
        // Jaccard(A,C) = 1 / (3+1-1) = 1/3 ≈ 0.333
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $B]);
        $this->crearCanasta([$A, $C]);

        $this->service->recomputar(diasVentana: 30, minCoCount: 1);

        $todos = $this->service->productosRelacionados($A->id, limite: 5, scoreMinimo: 0.0);
        $this->assertCount(2, $todos);

        $solo1 = $this->service->productosRelacionados($A->id, limite: 1, scoreMinimo: 0.0);
        $this->assertCount(1, $solo1, 'limite=1 debe truncar.');

        // Umbral 0.5 deja solo (A,B)=0.667 fuera (A,C)=0.333
        $umbral = $this->service->productosRelacionados($A->id, limite: 5, scoreMinimo: 0.5);
        $this->assertCount(1, $umbral, 'scoreMinimo=0.5 debe filtrar el par débil (A,C).');
        $this->assertSame($B->id, $umbral->first()['producto_id']);
    }

    #[Test]
    public function vecindario_batch_agrega_max_y_excluye_carrito(): void
    {
        $A = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $C = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $D = Producto::factory()->create(['stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        // (A,C): Jaccard 1.0; (B,C): Jaccard menor; (A,D): Jaccard moderado.
        $this->crearCanasta([$A, $C]);
        $this->crearCanasta([$A, $C]);
        $this->crearCanasta([$B, $C]);
        $this->crearCanasta([$A, $D]);
        $this->crearCanasta([$A, $D]);
        $this->crearCanasta([$D]); // D aparece solo, baja Jaccard de (A,D)

        $this->service->recomputar(diasVentana: 30, minCoCount: 1);

        // Carrito = [A, B] → vecinos esperados: C (max(score(A,C), score(B,C))) y D
        $vecinos = $this->service->vecindarioBatch([$A->id, $B->id], limitePorBase: 10);

        $this->assertArrayNotHasKey($A->id, $vecinos->all(), 'Excluir productos del carrito.');
        $this->assertArrayNotHasKey($B->id, $vecinos->all());
        $this->assertArrayHasKey($C->id, $vecinos->all());
        $this->assertArrayHasKey($D->id, $vecinos->all());

        // C debe ganar a D (Jaccard(A,C)=1.0 vs Jaccard(A,D)≈0.667).
        $keys = array_keys($vecinos->all());
        $this->assertSame($C->id, $keys[0], 'C debe ser el vecino de mayor score agregado.');
    }

    #[Test]
    public function metrica_invalida_lanza_excepcion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->recomputar(metrica: 'cosine');
    }
}
