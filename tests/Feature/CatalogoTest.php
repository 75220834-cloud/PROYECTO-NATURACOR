<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function catalogo_es_accesible_sin_autenticacion(): void
    {
        $response = $this->get('/catalogo');
        $response->assertSuccessful();
    }

    #[Test]
    public function catalogo_muestra_productos_activos(): void
    {
        $sucursal = Sucursal::factory()->create();
        Producto::factory()->create([
            'sucursal_id' => $sucursal->id,
            'activo'      => true,
            'stock'       => 10,
            'tipo'        => 'natural',
            'nombre'      => 'Producto Visible',
        ]);
        Producto::factory()->create([
            'sucursal_id' => $sucursal->id,
            'activo'      => false,
            'stock'       => 10,
            'tipo'        => 'natural',
            'nombre'      => 'Producto Oculto',
        ]);
        $response = $this->get('/catalogo');
        $response->assertSuccessful();
        $response->assertSee('Producto Visible');
        $response->assertDontSee('Producto Oculto');
    }

    #[Test]
    public function catalogo_no_muestra_productos_sin_stock(): void
    {
        $sucursal = Sucursal::factory()->create();
        Producto::factory()->create([
            'sucursal_id' => $sucursal->id,
            'activo'      => true,
            'stock'       => 0,
            'tipo'        => 'natural',
            'nombre'      => 'Producto Sin Stock',
        ]);
        $response = $this->get('/catalogo');
        $response->assertSuccessful();
        $response->assertDontSee('Producto Sin Stock');
    }

    #[Test]
    public function catalogo_carga_sin_productos(): void
    {
        $response = $this->get('/catalogo');
        $response->assertSuccessful();
    }

    #[Test]
    public function raiz_redirige_al_catalogo(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/catalogo');
    }
}
