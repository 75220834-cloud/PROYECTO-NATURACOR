<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Enfermedad;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RecetarioUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function enfermedad_puede_tener_multiples_productos(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $p1 = Producto::factory()->create(['tipo' => 'natural', 'activo' => true]);
        $p2 = Producto::factory()->create(['tipo' => 'natural', 'activo' => true]);
        $p3 = Producto::factory()->create(['tipo' => 'natural', 'activo' => true]);

        $enfermedad->productos()->sync([
            $p1->id => ['instrucciones' => 'Tomar en ayunas', 'orden' => 1],
            $p2->id => ['instrucciones' => 'Con agua tibia', 'orden' => 2],
            $p3->id => ['instrucciones' => 'Después de comer', 'orden' => 3],
        ]);

        $this->assertCount(3, $enfermedad->productos);
    }

    #[Test]
    public function producto_puede_estar_en_multiples_enfermedades(): void
    {
        $producto = Producto::factory()->create(['tipo' => 'natural', 'activo' => true]);
        $e1 = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $e2 = Enfermedad::create(['nombre' => 'Hipertensión', 'activa' => true]);
        $e3 = Enfermedad::create(['nombre' => 'Artritis', 'activa' => true]);

        $e1->productos()->attach($producto->id, ['instrucciones' => 'Instrucción 1', 'orden' => 1]);
        $e2->productos()->attach($producto->id, ['instrucciones' => 'Instrucción 2', 'orden' => 1]);
        $e3->productos()->attach($producto->id, ['instrucciones' => 'Instrucción 3', 'orden' => 1]);

        // Contar desde la tabla pivot
        $count = \Illuminate\Support\Facades\DB::table('enfermedad_producto')
            ->where('producto_id', $producto->id)
            ->count();

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function instrucciones_del_pivot_se_guardan_correctamente(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Anemia', 'activa' => true]);
        $producto   = Producto::factory()->create(['tipo' => 'natural']);

        $enfermedad->productos()->attach($producto->id, [
            'instrucciones' => 'Tomar 2 cucharadas diarias',
            'orden'         => 1,
        ]);

        $pivotInstrucciones = $enfermedad->productos()->first()->pivot->instrucciones;

        $this->assertEquals('Tomar 2 cucharadas diarias', $pivotInstrucciones);
    }

    #[Test]
    public function orden_del_pivot_se_guarda_correctamente(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Colesterol', 'activa' => true]);
        $p1 = Producto::factory()->create(['tipo' => 'natural']);
        $p2 = Producto::factory()->create(['tipo' => 'natural']);

        $enfermedad->productos()->sync([
            $p1->id => ['instrucciones' => null, 'orden' => 1],
            $p2->id => ['instrucciones' => null, 'orden' => 2],
        ]);

        $productos = $enfermedad->productos()->get();
        $this->assertEquals(1, $productos->first()->pivot->orden);
    }

    #[Test]
    public function sync_reemplaza_productos_anteriores(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Gastritis', 'activa' => true]);
        $p1 = Producto::factory()->create(['tipo' => 'natural']);
        $p2 = Producto::factory()->create(['tipo' => 'natural']);
        $p3 = Producto::factory()->create(['tipo' => 'natural']);

        $enfermedad->productos()->sync([
            $p1->id => ['instrucciones' => null, 'orden' => 1],
            $p2->id => ['instrucciones' => null, 'orden' => 2],
        ]);
        $this->assertCount(2, $enfermedad->productos);

        // Hacer sync con solo p3 → los anteriores se eliminan
        $enfermedad->productos()->sync([
            $p3->id => ['instrucciones' => null, 'orden' => 1],
        ]);
        $enfermedad->refresh();
        $this->assertCount(1, $enfermedad->productos);
        $this->assertEquals($p3->id, $enfermedad->productos->first()->id);
    }

    #[Test]
    public function enfermedad_inactiva_no_aparece_en_consulta_activa(): void
    {
        Enfermedad::create(['nombre' => 'Activa', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Inactiva', 'activa' => false]);

        $activas = Enfermedad::where('activa', true)->get();

        $this->assertCount(1, $activas);
        $this->assertEquals('Activa', $activas->first()->nombre);
    }

    #[Test]
    public function relacion_productos_usa_pivot_correcto(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Artritis', 'activa' => true]);

        $relation = $enfermedad->productos();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $relation
        );
    }
}
