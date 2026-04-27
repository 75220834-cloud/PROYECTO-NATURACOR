<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\Recommendation\CoocurrenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bloque 2 Fase B — Tests del componente colaborativo integrado en /api/recomendaciones.
 *
 * Verifica:
 *  - estructura del payload extendido (componente_coocurrencia, meta.coocurrencia_activa);
 *  - bypass de caché cuando hay cesta;
 *  - cross-sell: producto co-comprado con el carrito aparece aunque no esté en perfil/trending;
 *  - razón explicable contiene 🛒.
 */
class RecomendacionCarritoIntegracionTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $sucursal;
    private User $user;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->sucursal = Sucursal::factory()->create(['activa' => true]);
        $this->user = User::factory()->create(['sucursal_id' => $this->sucursal->id, 'activo' => true]);
        $this->user->assignRole('empleado');
        $this->cliente = Cliente::factory()->create();
    }

    /**
     * @param  list<\App\Models\Producto>  $productos
     */
    private function crearVentaCanasta(array $productos, ?int $clienteId = null): Venta
    {
        $venta = Venta::create([
            'cliente_id' => $clienteId,
            'user_id' => $this->user->id,
            'sucursal_id' => $this->sucursal->id,
            'subtotal' => 0, 'igv' => 0, 'total' => 0, 'descuento_total' => 0,
            'metodo_pago' => 'efectivo', 'estado' => 'completada', 'incluir_igv' => true,
        ]);
        $venta->update(['numero_boleta' => $venta->generarNumeroBoleta()]);

        foreach ($productos as $p) {
            DetalleVenta::create([
                'venta_id' => $venta->id, 'producto_id' => $p->id,
                'nombre_producto' => $p->nombre, 'precio_unitario' => 1,
                'cantidad' => 1, 'descuento' => 0, 'subtotal' => 1,
            ]);
        }

        return $venta;
    }

    #[Test]
    public function endpoint_devuelve_componente_coocurrencia_en_estructura(): void
    {
        $producto = Producto::factory()->create([
            'tipo' => 'natural', 'stock' => 10,
            'sucursal_id' => $this->sucursal->id, 'activo' => true,
        ]);
        $enf = Enfermedad::create(['nombre' => 'Test', 'activa' => true]);
        $producto->enfermedades()->attach($enf->id, ['instrucciones' => null, 'orden' => 0]);

        $this->crearVentaCanasta([$producto], $this->cliente->id);

        $res = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=5");

        $res->assertOk()->assertJsonStructure([
            'items' => [
                '*' => [
                    'producto', 'score_final',
                    'componente_perfil', 'componente_trending', 'componente_coocurrencia',
                    'razones',
                ],
            ],
            'meta' => [
                'respuesta_desde_cache', 'perfil_recalculado',
                'reco_sesion_id', 'cesta_size', 'coocurrencia_activa',
            ],
        ]);

        $this->assertSame(0, $res->json('meta.cesta_size'),
            'Sin parámetro producto_ids, cesta_size debe ser 0.');
        $this->assertFalse($res->json('meta.coocurrencia_activa'),
            'Sin cesta, coocurrencia_activa = false.');
    }

    #[Test]
    public function carrito_actua_como_cross_sell_y_eleva_score_de_producto_relacionado(): void
    {
        // Setup: 3 productos. A y B siempre se compran juntos (alta co-ocurrencia).
        // C es un producto "bystander" sin asociación con A ni B.
        $A = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $C = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        // 5 canastas [A,B] → Jaccard(A,B) alto
        for ($i = 0; $i < 5; $i++) {
            $this->crearVentaCanasta([$A, $B]);
        }
        // 5 canastas [C] solo → C tiene trending pero NO co-ocurrencia con A
        for ($i = 0; $i < 5; $i++) {
            $this->crearVentaCanasta([$C]);
        }

        // Computar la matriz
        app(CoocurrenciaService::class)->recomputar(diasVentana: 30, minCoCount: 2);

        // Sin carrito: B podría aparecer por trending, pero sin "boost" de co-ocurrencia.
        $sinCarrito = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=10&refresh=1");
        $sinCarrito->assertOk();
        $itemsSin = collect($sinCarrito->json('items'))->keyBy(fn ($i) => $i['producto']['id']);

        // Con carrito = [A]: B debe aparecer y tener componente_coocurrencia > 0
        $conCarrito = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=10&producto_ids={$A->id}");
        $conCarrito->assertOk();

        $this->assertSame(1, $conCarrito->json('meta.cesta_size'));
        $this->assertTrue($conCarrito->json('meta.coocurrencia_activa'));

        $itemsCon = collect($conCarrito->json('items'))->keyBy(fn ($i) => $i['producto']['id']);
        $this->assertTrue($itemsCon->has($B->id), 'B debe aparecer como cross-sell de A.');

        $itemB = $itemsCon[$B->id];
        $this->assertGreaterThan(0, $itemB['componente_coocurrencia'],
            'componente_coocurrencia de B debe ser > 0 cuando A está en el carrito.');

        $razonesB = implode(' ', $itemB['razones']);
        $this->assertStringContainsString('🛒', $razonesB,
            'Razón del cross-sell debe incluir el ícono 🛒.');

        // Score final de B debe ser estrictamente mayor con A en carrito que sin él.
        if ($itemsSin->has($B->id)) {
            $this->assertGreaterThan(
                (float) $itemsSin[$B->id]['score_final'],
                (float) $itemB['score_final'],
                'El boost colaborativo debe elevar el score de B.'
            );
        }
    }

    #[Test]
    public function bypass_cache_cuando_hay_cesta(): void
    {
        $A = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);

        for ($i = 0; $i < 3; $i++) {
            $this->crearVentaCanasta([$A, $B]);
        }
        app(CoocurrenciaService::class)->recomputar(diasVentana: 30, minCoCount: 2);

        // Primera llamada con cesta
        $r1 = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=5&producto_ids={$A->id}");
        $r1->assertOk();
        $this->assertFalse($r1->json('meta.respuesta_desde_cache'));

        // Segunda llamada idéntica con cesta → debe seguir siendo no-cache (bypass)
        $r2 = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=5&producto_ids={$A->id}");
        $r2->assertOk();
        $this->assertFalse($r2->json('meta.respuesta_desde_cache'),
            'Con cesta activa, NUNCA se sirve desde caché.');
    }

    #[Test]
    public function controller_descarta_ids_invalidos_en_carrito(): void
    {
        $A = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        $B = Producto::factory()->create(['tipo' => 'natural', 'stock' => 100, 'sucursal_id' => $this->sucursal->id]);
        for ($i = 0; $i < 3; $i++) {
            $this->crearVentaCanasta([$A, $B]);
        }
        app(CoocurrenciaService::class)->recomputar(diasVentana: 30, minCoCount: 2);

        // IDs basura: 'foo', '', '-3', '0' deben filtrarse y dejar solo {A->id}
        $url = "/api/recomendaciones/{$this->cliente->id}?limite=5&producto_ids=foo,,-3,0,{$A->id}";
        $res = $this->actingAs($this->user)->getJson($url);
        $res->assertOk();

        $this->assertSame(1, $res->json('meta.cesta_size'),
            'IDs no positivos deben descartarse silenciosamente.');
    }

    #[Test]
    public function sin_cesta_la_respuesta_se_cachea_normalmente(): void
    {
        $A = Producto::factory()->create([
            'tipo' => 'natural', 'stock' => 100,
            'sucursal_id' => $this->sucursal->id, 'activo' => true,
        ]);
        $enf = Enfermedad::create(['nombre' => 'Test', 'activa' => true]);
        $A->enfermedades()->attach($enf->id, ['instrucciones' => null, 'orden' => 0]);
        $this->crearVentaCanasta([$A], $this->cliente->id);

        $r1 = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=5");
        $r1->assertOk();
        $this->assertFalse($r1->json('meta.respuesta_desde_cache'));

        $r2 = $this->actingAs($this->user)
            ->getJson("/api/recomendaciones/{$this->cliente->id}?limite=5");
        $r2->assertOk();
        $this->assertTrue($r2->json('meta.respuesta_desde_cache'),
            'Sin cesta, la segunda llamada debe venir del caché (comportamiento Bloque 1).');
    }
}
