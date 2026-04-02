<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class RecetarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $sucursal = Sucursal::factory()->create();

        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true, 'sucursal_id' => $sucursal->id]);
        $this->admin->assignRole($roleAdmin);

        $roleEmp = Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->empleado = User::factory()->create(['activo' => true, 'sucursal_id' => $sucursal->id]);
        $this->empleado->assignRole($roleEmp);
    }

    #[Test]
    public function admin_puede_ver_el_recetario(): void
    {
        Enfermedad::create(['nombre' => 'Diabetes', 'descripcion' => 'Azúcar alta', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Hipertensión', 'descripcion' => 'Presión alta', 'activa' => true]);

        $response = $this->actingAs($this->admin)->get('/recetario');

        $response->assertStatus(200);
        $response->assertViewIs('recetario.index');
        $response->assertViewHas('enfermedades');
        $this->assertCount(2, $response->viewData('enfermedades'));
    }

    #[Test]
    public function empleado_puede_ver_el_recetario(): void
    {
        $response = $this->actingAs($this->empleado)->get('/recetario');
        $response->assertStatus(200);
    }

    #[Test]
    public function puede_buscar_enfermedad_por_nombre(): void
    {
        Enfermedad::create(['nombre' => 'Diabetes', 'descripcion' => 'Azúcar alta', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Hipertensión', 'descripcion' => 'Presión alta', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Artritis', 'descripcion' => 'Inflamación articulaciones', 'activa' => true]);

        $response = $this->actingAs($this->admin)->get('/recetario?search=Diabetes');

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('enfermedades'));
        $this->assertEquals('Diabetes', $response->viewData('enfermedades')->first()->nombre);
    }

    #[Test]
    public function puede_crear_enfermedad_sin_productos(): void
    {
        $response = $this->actingAs($this->admin)->post('/recetario', [
            'nombre'      => 'Gastritis',
            'descripcion' => 'Inflamación del estómago',
            'categoria'   => 'Digestivo',
            'productos'   => [],
        ]);

        $response->assertRedirect(route('recetario.index'));
        $this->assertDatabaseHas('enfermedades', ['nombre' => 'Gastritis']);
    }

    #[Test]
    public function puede_crear_enfermedad_con_productos_asociados(): void
    {
        $producto = Producto::factory()->create(['tipo' => 'natural', 'activo' => true]);

        $response = $this->actingAs($this->admin)->post('/recetario', [
            'nombre'    => 'Anemia',
            'categoria' => 'Hematológico',
            'productos' => [
                ['id' => $producto->id, 'instrucciones' => 'Tomar en ayunas'],
            ],
        ]);

        $response->assertRedirect(route('recetario.index'));

        $enfermedad = Enfermedad::where('nombre', 'Anemia')->first();
        $this->assertNotNull($enfermedad);
        $this->assertDatabaseHas('enfermedad_producto', [
            'enfermedad_id' => $enfermedad->id,
            'producto_id'   => $producto->id,
        ]);
    }

    #[Test]
    public function nombre_es_obligatorio_al_crear_enfermedad(): void
    {
        $response = $this->actingAs($this->admin)->post('/recetario', [
            'nombre'    => '',
            'categoria' => 'Digestivo',
        ]);

        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('enfermedades', 0);
    }

    #[Test]
    public function puede_ver_detalle_de_enfermedad(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Colesterol', 'activa' => true]);

        $response = $this->actingAs($this->admin)->get("/recetario/{$enfermedad->id}");

        $response->assertStatus(200);
        $response->assertViewIs('recetario.show');
        $response->assertViewHas('recetario');
    }

    #[Test]
    public function puede_actualizar_una_enfermedad(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Colesterol', 'activa' => true]);

        $response = $this->actingAs($this->admin)->put("/recetario/{$enfermedad->id}", [
            'nombre'      => 'Colesterol Alto',
            'descripcion' => 'Nivel elevado de colesterol LDL',
            'categoria'   => 'Cardiovascular',
        ]);

        $response->assertRedirect(route('recetario.index'));
        $this->assertDatabaseHas('enfermedades', ['nombre' => 'Colesterol Alto']);
    }

    #[Test]
    public function puede_eliminar_una_enfermedad(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Sinusitis', 'activa' => true]);

        $response = $this->actingAs($this->admin)->delete("/recetario/{$enfermedad->id}");

        $response->assertRedirect(route('recetario.index'));
        $this->assertDatabaseMissing('enfermedades', ['nombre' => 'Sinusitis', 'deleted_at' => null]);
    }

    #[Test]
    public function puede_ver_formulario_de_creacion(): void
    {
        $response = $this->actingAs($this->admin)->get('/recetario/create');
        $response->assertStatus(200);
        $response->assertViewIs('recetario.create');
    }

    #[Test]
    public function puede_ver_formulario_de_edicion(): void
    {
        $enfermedad = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);

        $response = $this->actingAs($this->admin)->get("/recetario/{$enfermedad->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('recetario.edit');
    }

    #[Test]
    public function solo_muestra_enfermedades_activas_en_listado(): void
    {
        Enfermedad::create(['nombre' => 'Activa', 'activa' => true]);
        Enfermedad::create(['nombre' => 'Inactiva', 'activa' => false]);

        $response = $this->actingAs($this->admin)->get('/recetario');

        $this->assertCount(1, $response->viewData('enfermedades'));
        $this->assertEquals('Activa', $response->viewData('enfermedades')->first()->nombre);
    }
}
