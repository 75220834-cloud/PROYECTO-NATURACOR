<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecomendacionApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function endpoint_recomendaciones_devuelve_json_estructurado(): void
    {
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);

        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $user->assignRole('empleado');

        $cliente = Cliente::factory()->create();

        $enfermedad = Enfermedad::create([
            'nombre' => 'Digestión',
            'descripcion' => 'Condición de prueba',
            'categoria' => 'digestivo',
            'activa' => true,
        ]);

        $p1 = Producto::factory()->create([
            'nombre' => 'Producto Perfil',
            'tipo' => 'natural',
            'stock' => 20,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);
        $p2 = Producto::factory()->create([
            'nombre' => 'Producto Trending',
            'tipo' => 'natural',
            'stock' => 20,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);
        $p1->enfermedades()->attach($enfermedad->id, ['instrucciones' => null, 'orden' => 0]);

        $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
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
            'producto_id' => $p1->id,
            'nombre_producto' => $p1->nombre,
            'precio_unitario' => 10,
            'cantidad' => 2,
            'descuento' => 0,
            'subtotal' => 20,
        ]);

        $v2 = Venta::create([
            'cliente_id' => null,
            'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
            'subtotal' => 5,
            'igv' => 0,
            'total' => 5,
            'descuento_total' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'incluir_igv' => true,
        ]);
        $v2->update(['numero_boleta' => $v2->generarNumeroBoleta()]);
        DetalleVenta::create([
            'venta_id' => $v2->id,
            'producto_id' => $p2->id,
            'nombre_producto' => $p2->nombre,
            'precio_unitario' => 5,
            'cantidad' => 50,
            'descuento' => 0,
            'subtotal' => 250,
        ]);

        $response = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=5");

        $response->assertOk()
            ->assertJsonPath('cliente_id', $cliente->id)
            ->assertJsonStructure([
                'cliente_id',
                'perfil_filas',
                'items' => [
                    '*' => ['producto', 'score_final', 'componente_perfil', 'componente_trending', 'razones'],
                ],
                'meta' => ['respuesta_desde_cache', 'perfil_recalculado', 'reco_sesion_id'],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('perfil_filas'));
        $this->assertNotEmpty($response->json('items'));
        $this->assertFalse($response->json('meta.respuesta_desde_cache'));
        $this->assertTrue($response->json('meta.perfil_recalculado'));

        $cached = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=5");
        $cached->assertOk();
        $this->assertTrue($cached->json('meta.respuesta_desde_cache'));
        $this->assertNull($cached->json('meta.perfil_recalculado'));
    }

    #[Test]
    public function trending_respeta_sucursal_del_usuario(): void
    {
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $sucA = Sucursal::factory()->create(['activa' => true]);
        $sucB = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucA->id, 'activo' => true]);
        $user->assignRole('empleado');

        $cliente = Cliente::factory()->create();
        $enf = Enfermedad::create(['nombre' => 'Test', 'activa' => true]);

        $pA = Producto::factory()->create(['tipo' => 'natural', 'stock' => 10, 'sucursal_id' => $sucA->id, 'activo' => true]);
        $pB = Producto::factory()->create(['tipo' => 'natural', 'stock' => 10, 'sucursal_id' => $sucA->id, 'activo' => true]);
        $pA->enfermedades()->attach($enf->id, ['instrucciones' => null, 'orden' => 0]);

        $vCliente = Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => $user->id,
            'sucursal_id' => $sucA->id,
            'subtotal' => 1,
            'igv' => 0,
            'total' => 1,
            'descuento_total' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'incluir_igv' => true,
        ]);
        $vCliente->update(['numero_boleta' => $vCliente->generarNumeroBoleta()]);
        DetalleVenta::create([
            'venta_id' => $vCliente->id,
            'producto_id' => $pA->id,
            'nombre_producto' => $pA->nombre,
            'precio_unitario' => 1,
            'cantidad' => 1,
            'descuento' => 0,
            'subtotal' => 1,
        ]);

        $userOtro = User::factory()->create(['sucursal_id' => $sucB->id, 'activo' => true]);
        $userOtro->assignRole('empleado');
        $vB = Venta::create([
            'cliente_id' => null,
            'user_id' => $userOtro->id,
            'sucursal_id' => $sucB->id,
            'subtotal' => 1,
            'igv' => 0,
            'total' => 1,
            'descuento_total' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'incluir_igv' => true,
        ]);
        $vB->update(['numero_boleta' => $vB->generarNumeroBoleta()]);
        DetalleVenta::create([
            'venta_id' => $vB->id,
            'producto_id' => $pB->id,
            'nombre_producto' => $pB->nombre,
            'precio_unitario' => 1,
            'cantidad' => 999,
            'descuento' => 0,
            'subtotal' => 999,
        ]);

        Cache::flush();

        $res = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=10&refresh=1");
        $res->assertOk();
        $ids = collect($res->json('items'))->pluck('producto.id')->all();
        $this->assertContains($pA->id, $ids);
        $this->assertNotContains($pB->id, $ids, 'pB solo vendido en sucursal B no debe entrar en trending de sucursal A');
    }
}
