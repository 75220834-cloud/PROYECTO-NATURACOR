<?php

namespace Tests\Unit;

use App\Models\Producto;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests del helper global producto_image_url().
 *
 * Cubre los tres escenarios que la lógica de Cloudinary + fallback local debe respetar:
 *   1. URL absoluta (Cloudinary u otro CDN) → devuelta tal cual, sin prefijo.
 *   2. Ruta relativa (modo local) → prefijada con asset('storage/...').
 *   3. Imagen vacía o nula → devuelve null.
 */
class ImageHelperTest extends TestCase
{
    #[Test]
    public function devuelve_url_cloudinary_tal_cual(): void
    {
        $url = 'https://res.cloudinary.com/demo/image/upload/v1/naturacor/productos/abc.jpg';

        $this->assertSame($url, producto_image_url($url));
    }

    #[Test]
    public function devuelve_url_http_tal_cual(): void
    {
        $url = 'http://otro-cdn.example.com/imagen.png';

        $this->assertSame($url, producto_image_url($url));
    }

    #[Test]
    public function prefija_storage_para_rutas_relativas(): void
    {
        $resultado = producto_image_url('productos/abc123.jpg');

        $this->assertStringEndsWith('/storage/productos/abc123.jpg', $resultado);
        $this->assertStringStartsWith('http', $resultado);
    }

    #[Test]
    public function devuelve_null_cuando_la_imagen_es_vacia(): void
    {
        $this->assertNull(producto_image_url(null));
        $this->assertNull(producto_image_url(''));
        $this->assertNull(producto_image_url('   '));
    }

    #[Test]
    public function acepta_modelo_producto_con_imagen_local(): void
    {
        $producto = new Producto(['imagen' => 'productos/foo.jpg']);

        $resultado = producto_image_url($producto);

        $this->assertStringEndsWith('/storage/productos/foo.jpg', $resultado);
    }

    #[Test]
    public function acepta_modelo_producto_con_url_cloudinary(): void
    {
        $cloudinaryUrl = 'https://res.cloudinary.com/demo/image/upload/v1/abc.jpg';
        $producto = new Producto(['imagen' => $cloudinaryUrl]);

        $this->assertSame($cloudinaryUrl, producto_image_url($producto));
    }

    #[Test]
    public function acepta_modelo_producto_sin_imagen(): void
    {
        $producto = new Producto(['imagen' => null]);

        $this->assertNull(producto_image_url($producto));
    }

    #[Test]
    public function helper_tiene_imagen_devuelve_booleano_correcto(): void
    {
        $this->assertTrue(producto_tiene_imagen('productos/x.jpg'));
        $this->assertTrue(producto_tiene_imagen('https://res.cloudinary.com/x/abc.jpg'));
        $this->assertFalse(producto_tiene_imagen(null));
        $this->assertFalse(producto_tiene_imagen(''));
    }
}
