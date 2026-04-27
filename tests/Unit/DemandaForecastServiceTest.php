<?php

namespace Tests\Unit;

use App\Services\Forecasting\DemandaForecastService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloque 5 — Tests unitarios del DemandaForecastService.
 *
 * Cubre la matemática del SES contra valores conocidos calculados a mano,
 * cobertura de MAE/MAPE, manejo de bordes (serie vacía, constante, ceros)
 * y clamping del parámetro α al rango (0,1).
 */
class DemandaForecastServiceTest extends TestCase
{
    private DemandaForecastService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DemandaForecastService;
    }

    #[Test]
    public function ses_con_valores_conocidos_produce_la_prediccion_esperada(): void
    {
        // Serie: [10, 12, 13, 12, 14], α=0.5.
        //   S_0 = 10
        //   S_1 = 0.5·12 + 0.5·10 = 11
        //   S_2 = 0.5·13 + 0.5·11 = 12
        //   S_3 = 0.5·12 + 0.5·12 = 12
        //   S_4 = 0.5·14 + 0.5·12 = 13   ← predicción
        $r = $this->service->ajustarSes([10.0, 12.0, 13.0, 12.0, 14.0], 0.5);

        $this->assertEqualsWithDelta(13.0, $r['prediccion'], 1e-9);
    }

    #[Test]
    public function mae_se_calcula_sobre_residuos_in_sample(): void
    {
        // Mismos datos que el test anterior:
        //   ŷ_t = S_{t-1} → ŷ = [10, 11, 12, 12]
        //   y     = [12, 13, 12, 14]  (omitido y_0 porque no hay forecast en t=0)
        //   residuos: [2, 2, 0, 2]
        //   MAE = 6/4 = 1.5
        $r = $this->service->ajustarSes([10.0, 12.0, 13.0, 12.0, 14.0], 0.5);

        $this->assertNotNull($r['mae']);
        $this->assertEqualsWithDelta(1.5, $r['mae'], 1e-9);
    }

    #[Test]
    public function mape_descarta_observaciones_con_y_cero(): void
    {
        // Serie con un cero: y = [0, 10, 12, 14]; α = 0.5.
        //   S_0 = 0;       ŷ_1 = 0,  e_1 = 10  → MAPE descartaría div/0 si y=0,
        //                                       pero acá y_1=10 OK.
        //   S_1 = 0.5·10 + 0.5·0   = 5;  ŷ_2 = 5,  e_2 = 12-5 = 7
        //   S_2 = 0.5·12 + 0.5·5   = 8.5; ŷ_3 = 8.5, e_3 = 14-8.5 = 5.5
        //
        //   MAPE = (|10/10| + |7/12| + |5.5/14|) / 3
        //        = (1.0 + 0.5833 + 0.3929) / 3
        //        = 1.9762 / 3 ≈ 0.6587
        $r = $this->service->ajustarSes([0.0, 10.0, 12.0, 14.0], 0.5);

        $this->assertNotNull($r['mape']);
        $this->assertEqualsWithDelta(0.6587, $r['mape'], 0.001);
    }

    #[Test]
    public function mape_es_null_si_todas_las_observaciones_son_cero(): void
    {
        // Sin denominadores válidos para MAPE → null (no INF).
        $r = $this->service->ajustarSes([0.0, 0.0, 0.0, 0.0], 0.4);

        $this->assertNull($r['mape']);
        $this->assertNotNull($r['mae']); // MAE sí se puede calcular (0)
        $this->assertEqualsWithDelta(0.0, $r['mae'], 1e-9);
    }

    #[Test]
    public function serie_constante_da_prediccion_constante_y_error_cero(): void
    {
        $r = $this->service->ajustarSes([10.0, 10.0, 10.0, 10.0, 10.0], 0.4);

        $this->assertEqualsWithDelta(10.0, $r['prediccion'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $r['mae'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $r['mape'], 1e-9);
        // Sin variabilidad: el intervalo es degenerado (inf=sup=prediccion).
        $this->assertEqualsWithDelta(10.0, $r['intervalo_inf'], 1e-9);
        $this->assertEqualsWithDelta(10.0, $r['intervalo_sup'], 1e-9);
    }

    #[Test]
    public function intervalo_inf_no_es_negativo(): void
    {
        // Serie ruidosa centrada en valores bajos: el intervalo inferior naive
        // podría caer < 0 sin clamp. Verificamos que se clampea a 0.
        $r = $this->service->ajustarSes([2.0, 0.0, 3.0, 1.0, 0.0, 4.0, 0.0, 2.0], 0.5);

        $this->assertGreaterThanOrEqual(0.0, $r['intervalo_inf']);
        $this->assertGreaterThan($r['prediccion'] - 0.01, $r['intervalo_sup']);
    }

    #[Test]
    public function serie_vacia_devuelve_estructura_default(): void
    {
        $r = $this->service->ajustarSes([], 0.4);

        $this->assertSame(0.0, $r['prediccion']);
        $this->assertSame(0.0, $r['intervalo_inf']);
        $this->assertSame(0.0, $r['intervalo_sup']);
        $this->assertNull($r['mae']);
        $this->assertNull($r['mape']);
    }

    #[Test]
    public function serie_con_un_solo_punto_no_calcula_residuos(): void
    {
        // n=1 → no hay residuos posibles → mae = null, mape = null.
        $r = $this->service->ajustarSes([42.0], 0.5);

        $this->assertEqualsWithDelta(42.0, $r['prediccion'], 1e-9);
        $this->assertNull($r['mae']);
        $this->assertNull($r['mape']);
    }

    #[Test]
    public function alpha_se_clampea_al_rango_abierto_0_1(): void
    {
        // α = 0 (no aprende) o α = 1 (random walk) son patológicos en SES.
        // El service los trae a (0.01, 0.99) sin lanzar excepción.
        $serie = [5.0, 7.0, 6.0, 9.0];

        $rNeg = $this->service->ajustarSes($serie, -0.5);
        $rCero = $this->service->ajustarSes($serie, 0.0);
        $rUno = $this->service->ajustarSes($serie, 1.0);
        $rGrande = $this->service->ajustarSes($serie, 5.0);

        // No deben explotar y deben dar predicciones finitas no negativas.
        foreach ([$rNeg, $rCero, $rUno, $rGrande] as $r) {
            $this->assertIsFloat($r['prediccion']);
            $this->assertGreaterThanOrEqual(0.0, $r['prediccion']);
            $this->assertLessThan(INF, $r['prediccion']);
        }

        // α=0 → 0.01: la predicción ≈ S_0 = 5 (casi no aprende).
        $this->assertEqualsWithDelta(5.0, $rCero['prediccion'], 0.5);

        // α=1 → 0.99: la predicción ≈ último valor = 9 (casi naive).
        $this->assertEqualsWithDelta(9.0, $rUno['prediccion'], 0.3);
    }

    #[Test]
    public function alpha_alto_es_mas_reactivo_que_alpha_bajo(): void
    {
        // Comprueba la propiedad cualitativa de SES: con α grande, la predicción
        // se acerca más al último valor; con α pequeño, se acerca más al valor inicial.
        $serie = [10.0, 10.0, 10.0, 10.0, 50.0]; // shock al final

        $rBajo = $this->service->ajustarSes($serie, 0.1);
        $rAlto = $this->service->ajustarSes($serie, 0.8);

        $this->assertGreaterThan($rBajo['prediccion'], $rAlto['prediccion']);
    }
}
