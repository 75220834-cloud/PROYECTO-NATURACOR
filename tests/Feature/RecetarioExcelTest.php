<?php

namespace Tests\Feature;

use App\Imports\EnfermedadesImport;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecetarioExcelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'empleado']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function ruta_exportar_descarga_excel_de_recetario(): void
    {
        Enfermedad::create(['nombre' => 'Gastritis', 'categoria' => 'Digestivo', 'activa' => true]);

        $response = $this->actingAs($this->admin)->get(route('recetario.exportar'));

        $response->assertOk();
        $this->assertStringContainsString('recetario_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function ruta_plantilla_descarga_archivo_excel(): void
    {
        $response = $this->actingAs($this->admin)->get(route('recetario.plantilla'));

        $response->assertOk();
        $this->assertStringContainsString('plantilla_recetario.xlsx', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function importar_crea_enfermedad_nueva_con_productos(): void
    {
        $producto = Producto::factory()->create(['nombre' => 'Manzanilla Test', 'tipo' => 'natural']);

        $import = new EnfermedadesImport();
        $import->collection(collect([
            [
                'nombre_enfermedad' => 'Insomnio Test',
                'categoria' => 'Sueño',
                'descripcion' => 'Dificultad para dormir',
                'productos_recomendados_separados_por' => 'Manzanilla Test',
            ],
        ]));

        $this->assertSame(1, $import->creadas);
        $this->assertSame(0, $import->actualizadas);
        $this->assertSame([], $import->errores);

        $enfermedad = Enfermedad::where('nombre', 'Insomnio Test')->first();
        $this->assertNotNull($enfermedad);
        $this->assertSame('Sueño', $enfermedad->categoria);
        $this->assertTrue($enfermedad->productos->contains($producto->id));
    }

    #[Test]
    public function importar_actualiza_enfermedad_existente_sin_pisar_campos_vacios(): void
    {
        $enfermedad = Enfermedad::create([
            'nombre' => 'Gastritis Test',
            'categoria' => 'Digestivo Original',
            'descripcion' => 'Descripción original',
            'activa' => true,
        ]);

        $import = new EnfermedadesImport();
        $import->collection(collect([
            [
                'nombre_enfermedad' => 'Gastritis Test',
                'categoria' => 'Digestivo Actualizado',
                'descripcion' => '', // vacío → no debe pisar
                'productos_recomendados_separados_por' => '',
            ],
        ]));

        $this->assertSame(0, $import->creadas);
        $this->assertSame(1, $import->actualizadas);

        $enfermedad->refresh();
        $this->assertSame('Digestivo Actualizado', $enfermedad->categoria);
        $this->assertSame('Descripción original', $enfermedad->descripcion); // no se pisó
    }

    #[Test]
    public function importar_usa_sync_without_detaching_y_no_borra_productos_existentes(): void
    {
        $productoExistente = Producto::factory()->create(['nombre' => 'Aloe Vera Test', 'tipo' => 'natural']);
        $productoNuevo     = Producto::factory()->create(['nombre' => 'Romero Test', 'tipo' => 'natural']);

        $enfermedad = Enfermedad::create(['nombre' => 'Acidez Test', 'activa' => true]);
        $enfermedad->productos()->attach($productoExistente->id, ['orden' => 0]);

        $import = new EnfermedadesImport();
        $import->collection(collect([
            [
                'nombre_enfermedad' => 'Acidez Test',
                'productos_recomendados_separados_por' => 'Romero Test',
            ],
        ]));

        $enfermedad->refresh();
        $this->assertCount(2, $enfermedad->productos);
        $this->assertTrue($enfermedad->productos->contains($productoExistente->id));
        $this->assertTrue($enfermedad->productos->contains($productoNuevo->id));
    }

    #[Test]
    public function importar_acepta_separadores_pipe_y_punto_y_coma(): void
    {
        Producto::factory()->create(['nombre' => 'Producto A', 'tipo' => 'natural']);
        Producto::factory()->create(['nombre' => 'Producto B', 'tipo' => 'natural']);
        Producto::factory()->create(['nombre' => 'Producto C', 'tipo' => 'natural']);

        $import = new EnfermedadesImport();
        $import->collection(collect([
            [
                'nombre_enfermedad' => 'Mixto Test',
                'productos_recomendados_separados_por' => 'Producto A | Producto B ; Producto C',
            ],
        ]));

        $enfermedad = Enfermedad::where('nombre', 'Mixto Test')->first();
        $this->assertCount(3, $enfermedad->productos);
        $this->assertSame([], $import->errores);
    }

    #[Test]
    public function importar_registra_error_para_producto_no_encontrado_pero_crea_enfermedad(): void
    {
        $import = new EnfermedadesImport();
        $import->collection(collect([
            [
                'nombre_enfermedad' => 'Migraña Test',
                'productos_recomendados_separados_por' => 'Producto Inexistente XYZ',
            ],
        ]));

        $this->assertSame(1, $import->creadas);
        $this->assertCount(1, $import->errores);
        $this->assertStringContainsString('Producto Inexistente XYZ', $import->errores[0]);
        $this->assertStringContainsString('Migraña Test', $import->errores[0]);

        // La enfermedad SÍ se creó aunque el producto haya fallado
        $this->assertNotNull(Enfermedad::where('nombre', 'Migraña Test')->first());
    }

    #[Test]
    public function importar_ignora_filas_con_nombre_vacio(): void
    {
        $import = new EnfermedadesImport();
        $import->collection(collect([
            ['nombre_enfermedad' => '', 'categoria' => 'Cualquiera'],
            ['nombre_enfermedad' => '   ', 'categoria' => 'Otra'],
            ['nombre_enfermedad' => 'Real Test', 'categoria' => 'Test'],
        ]));

        $this->assertSame(1, $import->creadas);
        $this->assertSame(0, $import->actualizadas);
        $this->assertCount(0, $import->errores);
    }

    #[Test]
    public function importar_match_es_case_insensitive(): void
    {
        Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);

        $import = new EnfermedadesImport();
        $import->collection(collect([
            ['nombre_enfermedad' => 'DIABETES', 'categoria' => 'Endocrino'],
            ['nombre_enfermedad' => 'diabetes', 'descripcion' => 'Algo'],
        ]));

        // Las dos filas matchean con el mismo registro existente → 0 creadas, 2 actualizadas
        $this->assertSame(0, $import->creadas);
        $this->assertSame(2, $import->actualizadas);
        $this->assertSame(1, Enfermedad::where('nombre', 'Diabetes')->count());
    }

    #[Test]
    public function endpoint_importar_responde_con_redirect_y_mensaje_de_resumen(): void
    {
        Producto::factory()->create(['nombre' => 'Lavanda Test', 'tipo' => 'natural']);

        $contenido = "Nombre enfermedad,Categoría,Descripción,\"Productos Recomendados (separados por |)\"\n"
            . "Estrés Test,Nervioso,Tensión,Lavanda Test\n";

        $archivo = UploadedFile::fake()->createWithContent('recetario.csv', $contenido);

        $response = $this->actingAs($this->admin)
            ->post(route('recetario.importar'), ['archivo' => $archivo]);

        $response->assertRedirect(route('recetario.index'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Creadas: 1', session('success'));

        $this->assertNotNull(Enfermedad::where('nombre', 'Estrés Test')->first());
    }

    #[Test]
    public function endpoint_importar_rechaza_archivo_sin_extension_valida(): void
    {
        $archivo = UploadedFile::fake()->create('malicioso.exe', 100);

        $response = $this->actingAs($this->admin)
            ->post(route('recetario.importar'), ['archivo' => $archivo]);

        $response->assertSessionHasErrors('archivo');
    }
}
