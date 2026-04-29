<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\CajaSesion;
use App\Models\CajaMovimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class CajaTest2 extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $empleado;
    protected Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sucursal = Sucursal::factory()->create();
        Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true, 'sucursal_id' => $this->sucursal->id]);
        $this->admin->assignRole('admin');
        $this->empleado = User::factory()->create(['activo' => true, 'sucursal_id' => $this->sucursal->id]);
        $this->empleado->assignRole('empleado');
    }

    private function abrirCaja(User $user, float $monto = 100.00): CajaSesion
    {
        CajaSesion::create([
            'user_id'       => $user->id,
            'sucursal_id'   => $user->sucursal_id,
            'monto_inicial' => $monto,
            'apertura_at'   => now(),
            'estado'        => 'abierta',
        ]);
        return CajaSesion::where('user_id', $user->id)->where('estado', 'abierta')->first();
    }

    #[Test]
    public function puede_ver_pagina_caja_sin_sesion_activa(): void
    {
        $response = $this->actingAs($this->empleado)->get('/caja');
        $response->assertSuccessful();
    }

    #[Test]
    public function puede_ver_pagina_caja_con_sesion_activa(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->get('/caja');
        $response->assertSuccessful();
    }

    #[Test]
    public function no_autenticado_redirige_login_en_caja(): void
    {
        $response = $this->get('/caja');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function puede_abrir_caja(): void
    {
        $response = $this->actingAs($this->empleado)->post('/caja/abrir', [
            'monto_inicial' => 200.00,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('caja_sesiones', [
            'user_id' => $this->empleado->id,
            'estado'  => 'abierta',
        ]);
    }

    #[Test]
    public function abrir_caja_requiere_monto_inicial(): void
    {
        $response = $this->actingAs($this->empleado)->post('/caja/abrir', []);
        $response->assertSessionHasErrors('monto_inicial');
    }

    #[Test]
    public function monto_inicial_debe_ser_numerico(): void
    {
        $response = $this->actingAs($this->empleado)->post('/caja/abrir', [
            'monto_inicial' => 'abc',
        ]);
        $response->assertSessionHasErrors('monto_inicial');
    }

    #[Test]
    public function no_puede_abrir_dos_cajas_al_mismo_tiempo(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/abrir', [
            'monto_inicial' => 100.00,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function puede_registrar_movimiento_ingreso(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', [
            'tipo'        => 'ingreso',
            'monto'       => 50.00,
            'descripcion' => 'Ingreso de prueba',
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('caja_movimientos', [
            'tipo'  => 'ingreso',
            'monto' => 50.00,
        ]);
    }

    #[Test]
    public function puede_registrar_movimiento_egreso(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', [
            'tipo'        => 'egreso',
            'monto'       => 30.00,
            'descripcion' => 'Egreso de prueba',
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('caja_movimientos', [
            'tipo'  => 'egreso',
            'monto' => 30.00,
        ]);
    }

    #[Test]
    public function movimiento_ingreso_con_yape(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', [
            'tipo'        => 'ingreso',
            'monto'       => 20.00,
            'descripcion' => 'Pago yape',
            'metodo_pago' => 'yape',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('caja_movimientos', ['metodo_pago' => 'yape']);
    }

    #[Test]
    public function movimiento_ingreso_con_plin(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', [
            'tipo'        => 'ingreso',
            'monto'       => 20.00,
            'descripcion' => 'Pago plin',
            'metodo_pago' => 'plin',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('caja_movimientos', ['metodo_pago' => 'plin']);
    }

    #[Test]
    public function movimiento_ingreso_con_otro_metodo(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', [
            'tipo'        => 'ingreso',
            'monto'       => 20.00,
            'descripcion' => 'Otro método de pago',
            'metodo_pago' => 'otro',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('caja_movimientos', ['metodo_pago' => 'otro']);
    }

    #[Test]
    public function movimiento_requiere_campos_obligatorios(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', []);
        $response->assertSessionHasErrors(['tipo', 'monto', 'descripcion', 'metodo_pago']);
    }

    #[Test]
    public function movimiento_requiere_tipo_valido(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/movimiento', [
            'tipo'        => 'invalido',
            'monto'       => 10.00,
            'descripcion' => 'Test',
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertSessionHasErrors('tipo');
    }

    #[Test]
    public function puede_cerrar_caja(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/cerrar', [
            'monto_real' => 150.00,
            'notas'      => 'Cierre normal',
        ]);
        $response->assertRedirect('/caja');
        $this->assertDatabaseHas('caja_sesiones', [
            'user_id' => $this->empleado->id,
            'estado'  => 'cerrada',
        ]);
    }

    #[Test]
    public function cerrar_caja_requiere_monto_real(): void
    {
        $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->post('/caja/cerrar', []);
        $response->assertSessionHasErrors('monto_real');
    }

    #[Test]
    public function puede_ver_detalle_caja_sesion(): void
    {
        $sesion = $this->abrirCaja($this->empleado);
        $response = $this->actingAs($this->empleado)->get("/caja/{$sesion->id}");
        $response->assertSuccessful();
    }

    #[Test]
    public function caja_muestra_sesiones_anteriores(): void
    {
        $sesion = $this->abrirCaja($this->empleado);
        $sesion->update(['estado' => 'cerrada', 'cierre_at' => now()]);
        $response = $this->actingAs($this->empleado)->get('/caja');
        $response->assertSuccessful();
    }
}
