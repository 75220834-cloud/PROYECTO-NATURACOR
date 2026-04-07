<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CordialVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CordialVentaUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function precios_incluye_tienda_s3_correctamente(): void
    {
        $this->assertEquals(3, CordialVenta::$precios['tienda_s3']);
    }

    #[Test]
    public function precios_incluye_tienda_s5_correctamente(): void
    {
        $this->assertEquals(5, CordialVenta::$precios['tienda_s5']);
    }

    #[Test]
    public function precios_incluye_litro_especial_s40(): void
    {
        $this->assertEquals(40, CordialVenta::$precios['litro_especial_s40']);
    }

    #[Test]
    public function precios_incluye_nuevo_medio_litro_especial_s20(): void
    {
        $this->assertArrayHasKey('medio_litro_especial', CordialVenta::$precios);
        $this->assertEquals(20, CordialVenta::$precios['medio_litro_especial']);
    }

    #[Test]
    public function precios_incluye_litro_puro_s80(): void
    {
        $this->assertEquals(80, CordialVenta::$precios['litro_puro_s80']);
    }

    #[Test]
    public function precios_incluye_nuevo_medio_litro_puro_s40(): void
    {
        $this->assertArrayHasKey('medio_litro_puro', CordialVenta::$precios);
        $this->assertEquals(40, CordialVenta::$precios['medio_litro_puro']);
    }

    #[Test]
    public function precios_invitado_es_cero(): void
    {
        $this->assertEquals(0, CordialVenta::$precios['invitado']);
    }

    #[Test]
    public function labels_incluye_todos_los_tipos(): void
    {
        $tiposEsperados = [
            'tienda_s3', 'tienda_s5',
            'llevar_s3', 'llevar_s5',
            'litro_especial_s40', 'medio_litro_especial',
            'litro_puro_s80', 'medio_litro_puro',
            'invitado',
        ];

        foreach ($tiposEsperados as $tipo) {
            $this->assertArrayHasKey($tipo, CordialVenta::$labels, "Falta label para: {$tipo}");
        }
    }

    #[Test]
    public function label_medio_litro_especial_es_correcto(): void
    {
        $this->assertEquals('Medio Litro Especial S/20', CordialVenta::$labels['medio_litro_especial']);
    }

    #[Test]
    public function label_medio_litro_puro_es_correcto(): void
    {
        $this->assertEquals('Medio Litro Puro S/40', CordialVenta::$labels['medio_litro_puro']);
    }

    #[Test]
    public function tipos_que_acumulan_cordiales_incluyen_tienda_llevar_y_litro_especial(): void
    {
        $this->assertContains('tienda_s3', CordialVenta::$tiposAcumulanCordiales);
        $this->assertContains('tienda_s5', CordialVenta::$tiposAcumulanCordiales);
        $this->assertContains('llevar_s3', CordialVenta::$tiposAcumulanCordiales);
        $this->assertContains('llevar_s5', CordialVenta::$tiposAcumulanCordiales);
        $this->assertContains('litro_especial_s40', CordialVenta::$tiposAcumulanCordiales);
    }

    #[Test]
    public function litros_puros_y_medios_no_acumulan_en_tipos_de_fidelizacion_cordial(): void
    {
        $this->assertNotContains('litro_puro_s80',       CordialVenta::$tiposAcumulanCordiales);
        $this->assertNotContains('medio_litro_especial', CordialVenta::$tiposAcumulanCordiales);
        $this->assertNotContains('medio_litro_puro',     CordialVenta::$tiposAcumulanCordiales);
        $this->assertNotContains('invitado',             CordialVenta::$tiposAcumulanCordiales);
    }

    #[Test]
    public function precios_y_labels_tienen_mismas_claves(): void
    {
        $claves_precios = array_keys(CordialVenta::$precios);
        $claves_labels  = array_keys(CordialVenta::$labels);

        sort($claves_precios);
        sort($claves_labels);

        $this->assertEquals($claves_precios, $claves_labels);
    }
}
