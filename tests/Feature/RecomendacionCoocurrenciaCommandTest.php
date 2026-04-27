<?php

namespace Tests\Feature;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\ProductoCoocurrencia;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecomendacionCoocurrenciaCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function comando_recomputa_y_persiste_pares(): void
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

        $this->artisan('recomendaciones:cooccurrencia', [
            '--dias' => 30,
            '--min' => 2,
            '--metrica' => 'jaccard',
            '--quiet-log' => true,
        ])
            ->assertExitCode(0);

        $this->assertSame(1, ProductoCoocurrencia::query()->count());
        $par = ProductoCoocurrencia::query()->first();
        $this->assertEqualsWithDelta(1.0, (float) $par->score, 1e-5,
            'A y B siempre juntos → Jaccard = 1');
    }

    #[Test]
    public function comando_rechaza_metrica_invalida(): void
    {
        $this->artisan('recomendaciones:cooccurrencia', [
            '--metrica' => 'cosine',
            '--quiet-log' => true,
        ])->assertExitCode(2); // self::INVALID
    }
}
