<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\ClientePadecimiento;
use App\Models\ClientePerfilAfinidad;
use App\Models\Enfermedad;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\Analytics\HeatmapEnfermedadesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloque 6 — Tests del HeatmapEnfermedadesService.
 *
 * Cubre las tres fuentes de evidencia, deduplicación cliente-único, clustering
 * determinista, ordenamientos y export CSV.
 */
class HeatmapEnfermedadesServiceTest extends TestCase
{
    use RefreshDatabase;

    private HeatmapEnfermedadesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HeatmapEnfermedadesService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Helper: crea una venta backdateada usando setTestNow porque
     * Venta::$fillable no incluye created_at (igual patrón que Bloque 5).
     */
    private function ventaEnFecha(Sucursal $sucursal, Cliente $cliente, Carbon $fecha): Venta
    {
        Carbon::setTestNow($fecha);
        $u = User::factory()->create(['sucursal_id' => $sucursal->id, 'activo' => true]);
        $v = Venta::create([
            'cliente_id'  => $cliente->id,
            'user_id'     => $u->id,
            'sucursal_id' => $sucursal->id,
            'subtotal'    => 0, 'igv' => 0, 'total' => 0, 'descuento_total' => 0,
            'metodo_pago' => 'efectivo', 'estado' => 'completada', 'incluir_igv' => true,
        ]);
        $v->update(['numero_boleta' => $v->generarNumeroBoleta()]);
        Carbon::setTestNow();

        return $v;
    }

    #[Test]
    public function matriz_vacia_cuando_no_hay_enfermedades_activas(): void
    {
        Sucursal::factory()->create(['activa' => true]);

        $m = $this->service->construirMatriz();

        $this->assertSame([], $m['enfermedades']);
        $this->assertSame(0, $m['max_celda']);
        $this->assertSame(0, $m['meta']['total_clientes_unicos']);
    }

    #[Test]
    public function matriz_vacia_cuando_no_hay_sucursales_activas(): void
    {
        Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);

        $m = $this->service->construirMatriz();

        $this->assertSame([], $m['sucursales']);
        $this->assertSame(0, $m['max_celda']);
    }

    #[Test]
    public function declarada_cuenta_un_cliente_unico_por_sucursal_aunque_compre_varias_veces(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $diab = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $cli = Cliente::factory()->create();
        ClientePadecimiento::create([
            'cliente_id'    => $cli->id,
            'enfermedad_id' => $diab->id,
        ]);
        // Tres ventas del mismo cliente en la misma sucursal
        $this->ventaEnFecha($jauja, $cli, now()->subDays(2));
        $this->ventaEnFecha($jauja, $cli, now()->subDays(5));
        $this->ventaEnFecha($jauja, $cli, now()->subDays(7));

        $m = $this->service->construirMatriz(fuente: 'declarada', dias: 30);

        $this->assertSame(1, $m['celdas'][$diab->id][$jauja->id],
            'Cliente único debe contar 1, no 3 (la celda mide CLIENTES no ventas).');
        $this->assertSame(1, $m['meta']['total_clientes_unicos']);
    }

    #[Test]
    public function cliente_con_padecimiento_declarado_y_observado_cuenta_una_vez_en_combinada(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $diab = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $cli = Cliente::factory()->create();

        ClientePadecimiento::create([
            'cliente_id' => $cli->id, 'enfermedad_id' => $diab->id,
        ]);
        ClientePerfilAfinidad::create([
            'cliente_id' => $cli->id, 'enfermedad_id' => $diab->id,
            'score' => 0.95, 'evidencia_count' => 5,
        ]);
        $this->ventaEnFecha($jauja, $cli, now()->subDays(1));

        $declarada = $this->service->construirMatriz(fuente: 'declarada', dias: 30);
        $observada = $this->service->construirMatriz(fuente: 'observada', dias: 30);
        $combinada = $this->service->construirMatriz(fuente: 'combinada', dias: 30);

        $this->assertSame(1, $declarada['celdas'][$diab->id][$jauja->id]);
        $this->assertSame(1, $observada['celdas'][$diab->id][$jauja->id]);
        $this->assertSame(1, $combinada['celdas'][$diab->id][$jauja->id],
            'El mismo cliente NO se cuenta dos veces si aparece en ambas fuentes.');
    }

    #[Test]
    public function umbral_score_filtra_perfiles_observados_de_baja_evidencia(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $hipo = Enfermedad::create(['nombre' => 'Hipertension', 'activa' => true]);
        $alto = Cliente::factory()->create();
        $bajo = Cliente::factory()->create();

        ClientePerfilAfinidad::create([
            'cliente_id' => $alto->id, 'enfermedad_id' => $hipo->id,
            'score' => 0.50, 'evidencia_count' => 4,
        ]);
        ClientePerfilAfinidad::create([
            'cliente_id' => $bajo->id, 'enfermedad_id' => $hipo->id,
            'score' => 0.05, 'evidencia_count' => 1, // por debajo del umbral 0.20
        ]);
        $this->ventaEnFecha($jauja, $alto, now()->subDay());
        $this->ventaEnFecha($jauja, $bajo, now()->subDay());

        $m = $this->service->construirMatriz(fuente: 'observada', dias: 30, umbralScore: 0.20);

        $this->assertSame(1, $m['celdas'][$hipo->id][$jauja->id],
            'Solo el cliente con score >= umbral debe contar.');
    }

    #[Test]
    public function cliente_sin_venta_en_la_ventana_no_aparece(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $estr = Enfermedad::create(['nombre' => 'Estreñimiento', 'activa' => true]);
        $cli = Cliente::factory()->create();
        ClientePadecimiento::create([
            'cliente_id' => $cli->id, 'enfermedad_id' => $estr->id,
        ]);
        // Venta hace 200 días, ventana de 30
        $this->ventaEnFecha($jauja, $cli, now()->subDays(200));

        $m = $this->service->construirMatriz(fuente: 'declarada', dias: 30);

        $this->assertSame(0, $m['celdas'][$estr->id][$jauja->id]);
        $this->assertSame(0, $m['meta']['total_clientes_unicos']);
    }

    #[Test]
    public function diferentes_clientes_en_diferentes_sucursales_se_distribuyen_correctamente(): void
    {
        $jauja  = Sucursal::factory()->create(['nombre' => 'Jauja',  'activa' => true]);
        $huanc  = Sucursal::factory()->create(['nombre' => 'Huancayo', 'activa' => true]);
        $diab = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);

        $clientesJauja = Cliente::factory()->count(3)->create();
        $clientesHuanc = Cliente::factory()->count(2)->create();

        foreach ($clientesJauja as $c) {
            ClientePadecimiento::create(['cliente_id' => $c->id, 'enfermedad_id' => $diab->id]);
            $this->ventaEnFecha($jauja, $c, now()->subDay());
        }
        foreach ($clientesHuanc as $c) {
            ClientePadecimiento::create(['cliente_id' => $c->id, 'enfermedad_id' => $diab->id]);
            $this->ventaEnFecha($huanc, $c, now()->subDay());
        }

        $m = $this->service->construirMatriz(fuente: 'declarada', dias: 30);

        $this->assertSame(3, $m['celdas'][$diab->id][$jauja->id]);
        $this->assertSame(2, $m['celdas'][$diab->id][$huanc->id]);
        $this->assertSame(5, $m['fila_total'][$diab->id]);
        $this->assertSame(3, $m['col_total'][$jauja->id]);
        $this->assertSame(2, $m['col_total'][$huanc->id]);
    }

    #[Test]
    public function top_por_sucursal_devuelve_los_k_mas_frecuentes(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $a = Enfermedad::create(['nombre' => 'A', 'activa' => true]);
        $b = Enfermedad::create(['nombre' => 'B', 'activa' => true]);
        $c = Enfermedad::create(['nombre' => 'C', 'activa' => true]);

        // A: 3 clientes, B: 2 clientes, C: 1 cliente.
        foreach ([[$a, 3], [$b, 2], [$c, 1]] as [$enf, $n]) {
            for ($i = 0; $i < $n; $i++) {
                $cli = Cliente::factory()->create();
                ClientePadecimiento::create(['cliente_id' => $cli->id, 'enfermedad_id' => $enf->id]);
                $this->ventaEnFecha($jauja, $cli, now()->subDay());
            }
        }

        $m = $this->service->construirMatriz(fuente: 'declarada', dias: 30, topPorSucursal: 2);
        $top = $m['top_por_sucursal'][$jauja->id];

        $this->assertCount(2, $top);
        $this->assertSame('A', $top[0]['nombre']);
        $this->assertSame(3, $top[0]['total']);
        $this->assertSame('B', $top[1]['nombre']);
        $this->assertSame(2, $top[1]['total']);
    }

    #[Test]
    public function orden_total_descendente_pone_enfermedades_de_mayor_count_primero(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja', 'activa' => true]);
        $rara = Enfermedad::create(['nombre' => 'Rara', 'activa' => true]);
        $comun = Enfermedad::create(['nombre' => 'Comun', 'activa' => true]);

        for ($i = 0; $i < 5; $i++) {
            $cli = Cliente::factory()->create();
            ClientePadecimiento::create(['cliente_id' => $cli->id, 'enfermedad_id' => $comun->id]);
            $this->ventaEnFecha($jauja, $cli, now()->subDay());
        }
        $cliRara = Cliente::factory()->create();
        ClientePadecimiento::create(['cliente_id' => $cliRara->id, 'enfermedad_id' => $rara->id]);
        $this->ventaEnFecha($jauja, $cliRara, now()->subDay());

        $m = $this->service->construirMatriz(fuente: 'declarada', dias: 30, orden: 'total');

        $this->assertSame($comun->id, $m['orden_filas'][0]);
        $this->assertSame($rara->id, $m['orden_filas'][1]);
    }

    #[Test]
    public function clustering_filas_identicas_quedan_juntas_y_filas_vacias_al_final(): void
    {
        // 3 enfermedades: A y B con vectores idénticos, C vacía.
        $celdas = [
            10 => [1 => 5, 2 => 0, 3 => 5], // A
            20 => [1 => 0, 2 => 0, 3 => 0], // C (vacía)
            30 => [1 => 5, 2 => 0, 3 => 5], // B (igual a A)
            40 => [1 => 1, 2 => 1, 3 => 1], // D (parecida pero distinta)
        ];

        $orden = $this->service->ordenarPorCluster($celdas, [10, 20, 30, 40]);

        $posC = array_search(20, $orden, true);
        $this->assertSame(3, $posC, 'La fila vacía (C) debe quedar al final.');

        // A y B son idénticas → distancia 0 → deben aparecer adyacentes.
        $posA = array_search(10, $orden, true);
        $posB = array_search(30, $orden, true);
        $this->assertSame(1, abs($posA - $posB),
            'Filas idénticas deben quedar adyacentes en el dendrograma.');
    }

    #[Test]
    public function exportar_csv_respeta_orden_filas_y_escapa_comas(): void
    {
        $jauja = Sucursal::factory()->create(['nombre' => 'Jauja, Centro', 'activa' => true]);
        $diab = Enfermedad::create(['nombre' => 'Diabetes', 'activa' => true]);
        $cli = Cliente::factory()->create();
        ClientePadecimiento::create(['cliente_id' => $cli->id, 'enfermedad_id' => $diab->id]);
        $this->ventaEnFecha($jauja, $cli, now()->subDay());

        $m = $this->service->construirMatriz(fuente: 'declarada', dias: 30);
        $csv = $this->service->exportarCsv($m);

        $this->assertStringContainsString('"Jauja, Centro"', $csv,
            'Comas en nombre de sucursal deben quedar entre comillas.');
        $this->assertStringContainsString('Diabetes', $csv);
        $lineas = explode("\n", trim($csv));
        $this->assertSame('enfermedad,categoria,"Jauja, Centro",total', $lineas[0]);
    }

    #[Test]
    public function fuente_invalida_se_normaliza_a_combinada(): void
    {
        Sucursal::factory()->create(['activa' => true]);
        Enfermedad::create(['nombre' => 'X', 'activa' => true]);

        $m = $this->service->construirMatriz(fuente: 'inventada');

        $this->assertSame('combinada', $m['meta']['fuente']);
    }
}
