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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecomendacionMetricasFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_recomendaciones_registra_eventos_mostrada_y_meta_sesion(): void
    {
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $user->assignRole('empleado');

        $cliente = Cliente::factory()->create();
        $enf = Enfermedad::create(['nombre' => 'Métricas', 'activa' => true]);
        $p = Producto::factory()->create([
            'tipo' => 'natural',
            'stock' => 10,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);
        $p->enfermedades()->attach($enf->id, ['instrucciones' => null, 'orden' => 0]);

        $v = Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
            'subtotal' => 1,
            'igv' => 0,
            'total' => 1,
            'descuento_total' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'incluir_igv' => true,
        ]);
        $v->update(['numero_boleta' => $v->generarNumeroBoleta()]);
        DetalleVenta::create([
            'venta_id' => $v->id,
            'producto_id' => $p->id,
            'nombre_producto' => $p->nombre,
            'precio_unitario' => 1,
            'cantidad' => 1,
            'descuento' => 0,
            'subtotal' => 1,
        ]);

        $res = $this->actingAs($user)->getJson("/api/recomendaciones/{$cliente->id}?limite=5&refresh=1");
        $res->assertOk();
        $res->assertJsonStructure(['meta' => ['reco_sesion_id']]);
        $this->assertNotNull($res->json('meta.reco_sesion_id'));

        $this->assertGreaterThan(0, RecomendacionEvento::where('accion', RecomendacionEvento::ACCION_MOSTRADA)->count());
    }

    #[Test]
    public function venta_con_producto_recomendado_previo_registra_comprada(): void
    {
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $user->assignRole('empleado');

        $cliente = Cliente::factory()->create();
        $p = Producto::factory()->create(['tipo' => 'natural', 'stock' => 50, 'sucursal_id' => $sucursal->id, 'activo' => true]);
        $sid = (string) Str::uuid();

        RecomendacionEvento::create([
            'reco_sesion_id' => $sid,
            'cliente_id' => $cliente->id,
            'producto_id' => $p->id,
            'score' => 10,
            'razones' => ['prueba'],
            'accion' => RecomendacionEvento::ACCION_MOSTRADA,
            'posicion' => 1,
            'venta_id' => null,
            'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
        ]);

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
            'producto_id' => $p->id,
            'nombre_producto' => $p->nombre,
            'precio_unitario' => 10,
            'cantidad' => 1,
            'descuento' => 0,
            'subtotal' => 10,
        ]);

        $this->assertTrue(
            RecomendacionEvento::where('accion', RecomendacionEvento::ACCION_COMPRADA)
                ->where('venta_id', $venta->id)
                ->where('producto_id', $p->id)
                ->exists()
        );
    }

    #[Test]
    public function post_evento_agregada_registra_fila(): void
    {
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $sucursal = Sucursal::factory()->create(['activa' => true]);
        $user = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $user->assignRole('empleado');

        $cliente = Cliente::factory()->create();
        $p = Producto::factory()->create(['tipo' => 'natural', 'stock' => 5, 'sucursal_id' => $sucursal->id, 'activo' => true]);
        $sid = (string) Str::uuid();

        RecomendacionEvento::create([
            'reco_sesion_id' => $sid,
            'cliente_id' => $cliente->id,
            'producto_id' => $p->id,
            'score' => 5,
            'razones' => ['x'],
            'accion' => RecomendacionEvento::ACCION_MOSTRADA,
            'posicion' => 1,
            'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
        ]);

        $this->actingAs($user)->postJson('/api/recomendaciones/evento', [
            'reco_sesion_id' => $sid,
            'cliente_id' => $cliente->id,
            'producto_id' => $p->id,
            'accion' => 'agregada',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertTrue(
            RecomendacionEvento::where('accion', RecomendacionEvento::ACCION_AGREGADA)
                ->where('reco_sesion_id', $sid)
                ->exists()
        );
    }

    #[Test]
    public function dashboard_metricas_responde_ok(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('admin');

        $this->actingAs($user)->get('/metricas/recomendaciones')->assertOk();
    }
}
