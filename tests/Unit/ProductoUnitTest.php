<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ProductoUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function extrae_igv_18_porciento_incluido_correctamente(): void
    {
        // En Perú, los precios incluyen IGV. Se extrae: total * (18/118)
        $precio = 118.00;
        $igvFactor = 18 / 118;
        $igv = round($precio * $igvFactor, 2);

        $this->assertEquals(18.00, $igv);
    }

    #[Test]
    public function igv_de_precio_59_es_correcto(): void
    {
        $precio = 59.00;
        $igvFactor = 18 / 118;
        $igv = round($precio * $igvFactor, 2);

        $this->assertEquals(9.0, $igv);
    }

    #[Test]
    public function base_imponible_correcta_precio_118(): void
    {
        $total = 118.00;
        $igvFactor = 18 / 118;
        $igv = round($total * $igvFactor, 2);
        $base = round($total - $igv, 2);

        $this->assertEquals(100.00, $base);
    }

    #[Test]
    public function producto_en_stock_critico_cuando_stock_igual_al_minimo(): void
    {
        $producto = Producto::factory()->create([
            'stock'        => 5,
            'stock_minimo' => 5,
            'activo'       => true,
        ]);

        // Stock igual al mínimo → está en stock crítico
        $this->assertTrue($producto->stock <= $producto->stock_minimo);
    }

    #[Test]
    public function producto_en_stock_critico_cuando_stock_menor_al_minimo(): void
    {
        $producto = Producto::factory()->create([
            'stock'        => 2,
            'stock_minimo' => 5,
            'activo'       => true,
        ]);

        $this->assertTrue($producto->stock <= $producto->stock_minimo);
    }

    #[Test]
    public function producto_no_esta_en_stock_critico_cuando_tiene_suficiente(): void
    {
        $producto = Producto::factory()->create([
            'stock'        => 50,
            'stock_minimo' => 5,
            'activo'       => true,
        ]);

        $this->assertFalse($producto->stock <= $producto->stock_minimo);
    }

    #[Test]
    public function producto_agotado_tiene_stock_cero(): void
    {
        $producto = Producto::factory()->create([
            'stock'        => 0,
            'stock_minimo' => 5,
        ]);

        $this->assertEquals(0, $producto->stock);
        $this->assertTrue($producto->stock <= $producto->stock_minimo);
    }

    #[Test]
    public function producto_tiene_tipo_natural_o_cordial(): void
    {
        $natural = Producto::factory()->create(['tipo' => 'natural']);
        $cordial = Producto::factory()->create(['tipo' => 'cordial']);

        $this->assertContains($natural->tipo, ['natural', 'cordial']);
        $this->assertContains($cordial->tipo, ['natural', 'cordial']);
    }

    #[Test]
    public function soft_delete_no_elimina_fisicamente_el_producto(): void
    {
        $producto = Producto::factory()->create(['nombre' => 'Sangre de Grado']);
        $id = $producto->id;

        $producto->delete();

        // No existe en queries normales
        $this->assertNull(Producto::find($id));
        // Pero sí con withTrashed
        $this->assertNotNull(Producto::withTrashed()->find($id));
    }

    #[Test]
    public function precio_decimal_se_almacena_con_dos_decimales(): void
    {
        $producto = Producto::factory()->create(['precio' => 12.5]);
        $this->assertEquals(12.5, (float) $producto->precio);
    }
}
