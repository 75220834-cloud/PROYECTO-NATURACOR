<?php

namespace Tests\Unit;

use App\Services\Recommendation\AbTestingService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloque 4 — Tests del servicio A/B testing del recomendador.
 *
 * Cubre:
 *  · Asignación de grupos por estrategia (hash, día par/impar, aleatorio).
 *  · Comportamiento ante A/B desactivado y cliente null.
 *  · Estabilidad determinística del hash.
 *  · Welch's t-test validado contra valores conocidos.
 *  · Cohen's d con signo correcto.
 */
class AbTestingServiceTest extends TestCase
{
    private AbTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AbTestingService;
    }

    // ===================================================================
    //  Asignación de grupos
    // ===================================================================

    #[Test]
    public function ab_desactivado_devuelve_sin_ab(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', false);

        $this->assertSame(AbTestingService::GRUPO_SIN_AB, $this->service->asignarGrupo(1));
        $this->assertSame(AbTestingService::GRUPO_SIN_AB, $this->service->asignarGrupo(99999));
        $this->assertSame(AbTestingService::GRUPO_SIN_AB, $this->service->asignarGrupo(null));
    }

    #[Test]
    public function ab_activo_sin_cliente_devuelve_tratamiento(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 50);

        // Sin cliente_id no podemos estabilizar la asignación → tratamiento por defecto.
        $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $this->service->asignarGrupo(null));
    }

    #[Test]
    public function hash_es_determinista_para_el_mismo_cliente(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 50);

        $primera = $this->service->asignarGrupo(42);
        for ($i = 0; $i < 50; $i++) {
            $this->assertSame($primera, $this->service->asignarGrupo(42),
                "asignarGrupo(42) debe ser estable entre llamadas (iter $i)");
        }
    }

    #[Test]
    public function porcentaje_control_100_pone_a_todos_en_control(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 100);

        for ($i = 1; $i <= 200; $i++) {
            $this->assertSame(AbTestingService::GRUPO_CONTROL, $this->service->asignarGrupo($i),
                "cliente $i debería ser control con porcentaje=100");
        }
    }

    #[Test]
    public function porcentaje_control_0_pone_a_todos_en_tratamiento(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 0);

        for ($i = 1; $i <= 200; $i++) {
            $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $this->service->asignarGrupo($i),
                "cliente $i debería ser tratamiento con porcentaje=0");
        }
    }

    #[Test]
    public function hash_split_50_50_es_aproximadamente_balanceado_en_muestra_grande(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_HASH);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 50);

        $controles = 0;
        $n = 2000;
        for ($i = 1; $i <= $n; $i++) {
            if ($this->service->asignarGrupo($i) === AbTestingService::GRUPO_CONTROL) {
                $controles++;
            }
        }
        // Tolerancia ±5% para variabilidad esperable de md5 hash uniforme.
        $proporcion = $controles / $n;
        $this->assertGreaterThanOrEqual(0.45, $proporcion);
        $this->assertLessThanOrEqual(0.55, $proporcion);
    }

    #[Test]
    public function estrategia_dia_par_impar_alterna_segun_calendario(): void
    {
        config()->set('recommendaciones.ab_testing.enabled', true);
        config()->set('recommendaciones.ab_testing.estrategia', AbTestingService::ESTRATEGIA_DIA_PAR_IMPAR);
        config()->set('recommendaciones.ab_testing.porcentaje_control', 50);

        // Día 14 (par) → bucket 0 → control (0 < 50).
        Carbon::setTestNow(Carbon::create(2026, 4, 14, 10));
        $this->assertSame(AbTestingService::GRUPO_CONTROL, $this->service->asignarGrupo(1));
        $this->assertSame(AbTestingService::GRUPO_CONTROL, $this->service->asignarGrupo(99));

        // Día 15 (impar) → bucket 99 → tratamiento (99 ≥ 50).
        Carbon::setTestNow(Carbon::create(2026, 4, 15, 10));
        $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $this->service->asignarGrupo(1));
        $this->assertSame(AbTestingService::GRUPO_TRATAMIENTO, $this->service->asignarGrupo(99));

        Carbon::setTestNow();
    }

    #[Test]
    public function helper_es_grupo_control_distingue_correctamente(): void
    {
        $this->assertTrue($this->service->esGrupoControl(AbTestingService::GRUPO_CONTROL));
        $this->assertFalse($this->service->esGrupoControl(AbTestingService::GRUPO_TRATAMIENTO));
        $this->assertFalse($this->service->esGrupoControl(AbTestingService::GRUPO_SIN_AB));
    }

    // ===================================================================
    //  Welch's t-test
    // ===================================================================

    #[Test]
    public function welch_t_test_devuelve_nulls_si_n_es_insuficiente(): void
    {
        $r = $this->service->welchTTest([10.0], [20.0]);

        $this->assertSame(1, $r['n_a']);
        $this->assertSame(1, $r['n_b']);
        $this->assertNull($r['t_statistic']);
        $this->assertNull($r['p_value_aprox']);
        $this->assertNull($r['cohens_d']);
        $this->assertFalse($r['muestra_suficiente']);
        $this->assertNotEmpty($r['nota']);
    }

    #[Test]
    public function welch_t_test_con_valores_conocidos_t_eq_2_df_eq_8(): void
    {
        // Caso clásico:
        //   A = [1,2,3,4,5] → mediaA=3, varA=2.5
        //   B = [3,4,5,6,7] → mediaB=5, varB=2.5
        //   t = (5-3) / sqrt(2.5/5 + 2.5/5) = 2/sqrt(1) = 2.0
        //   df = (1)^2 / ((0.5)^2/4 + (0.5)^2/4) = 8
        //   En R: 2 * pt(-2, 8) ≈ 0.0808
        $r = $this->service->welchTTest(
            [1.0, 2.0, 3.0, 4.0, 5.0],
            [3.0, 4.0, 5.0, 6.0, 7.0]
        );

        $this->assertEqualsWithDelta(2.0, $r['t_statistic'], 0.0001);
        $this->assertEqualsWithDelta(8.0, $r['df'], 0.01);
        $this->assertEqualsWithDelta(0.0808, $r['p_value_aprox'], 0.005);
        $this->assertEqualsWithDelta(2.0, $r['diferencia_medias'], 0.0001);

        // Cohen's d con SD pooled = sqrt(2.5) ≈ 1.5811 → d = 2/1.5811 ≈ 1.2649
        $this->assertEqualsWithDelta(1.2649, $r['cohens_d'], 0.001);
        // p ≈ 0.08 > 0.05: NO significativo al 5%.
        $this->assertFalse($r['significativo_5pct']);
    }

    #[Test]
    public function welch_t_test_detecta_diferencia_significativa_en_muestras_grandes(): void
    {
        // Genera dos muestras grandes con medias claramente distintas y df grande
        // para que la rama "df ≥ 30" (aprox normal) sea la que se ejerce.
        $a = $this->generarMuestraDeterminista(media: 100.0, sd: 10.0, n: 100, seed: 11);
        $b = $this->generarMuestraDeterminista(media: 110.0, sd: 10.0, n: 100, seed: 22);

        $r = $this->service->welchTTest($a, $b);

        $this->assertSame(100, $r['n_a']);
        $this->assertSame(100, $r['n_b']);
        $this->assertGreaterThan(0.0, $r['t_statistic']); // B > A
        $this->assertLessThan(0.001, $r['p_value_aprox']);
        $this->assertTrue($r['significativo_5pct']);
        $this->assertGreaterThan(0.5, $r['cohens_d']); // efecto medio-grande
        $this->assertTrue($r['muestra_suficiente']);
    }

    #[Test]
    public function welch_t_test_p_alto_si_no_hay_diferencia(): void
    {
        // Dos muestras casi idénticas: p debe ser alto, no significativo.
        $a = $this->generarMuestraDeterminista(media: 50.0, sd: 5.0, n: 50, seed: 7);
        $b = $this->generarMuestraDeterminista(media: 50.05, sd: 5.0, n: 50, seed: 8);

        $r = $this->service->welchTTest($a, $b);

        $this->assertNotNull($r['p_value_aprox']);
        $this->assertGreaterThan(0.05, $r['p_value_aprox']);
        $this->assertFalse($r['significativo_5pct']);
    }

    #[Test]
    public function welch_t_test_con_varianza_nula_devuelve_p_uno(): void
    {
        // Muestras constantes (varianza cero en ambas).
        $r = $this->service->welchTTest(
            [10.0, 10.0, 10.0, 10.0],
            [10.0, 10.0, 10.0, 10.0]
        );

        $this->assertSame(0.0, $r['t_statistic']);
        $this->assertEqualsWithDelta(1.0, $r['p_value_aprox'], 0.0001);
        $this->assertFalse($r['significativo_5pct']);
        $this->assertSame(0.0, $r['cohens_d']);
    }

    #[Test]
    public function welch_t_test_signo_de_cohens_d_indica_direccion(): void
    {
        // Tratamiento (B) MENOR que control (A) → d negativo.
        $r1 = $this->service->welchTTest([20.0, 21.0, 22.0, 23.0, 24.0], [10.0, 11.0, 12.0, 13.0, 14.0]);
        $this->assertLessThan(0.0, $r1['cohens_d']);
        $this->assertLessThan(0.0, $r1['t_statistic']);

        // Tratamiento (B) MAYOR que control (A) → d positivo.
        $r2 = $this->service->welchTTest([10.0, 11.0, 12.0, 13.0, 14.0], [20.0, 21.0, 22.0, 23.0, 24.0]);
        $this->assertGreaterThan(0.0, $r2['cohens_d']);
        $this->assertGreaterThan(0.0, $r2['t_statistic']);
    }

    /**
     * Genera una muestra "pseudo-gaussiana" determinística (Box–Muller con
     * mt_rand sembrado) para tests reproducibles sin depender de la lib
     * estadística externa. No es estadísticamente perfecta pero suficiente
     * para validar que el t-test decide bien casos claros.
     *
     * @return list<float>
     */
    private function generarMuestraDeterminista(float $media, float $sd, int $n, int $seed): array
    {
        mt_srand($seed);
        $out = [];
        for ($i = 0; $i < $n; $i += 2) {
            $u1 = max(1e-10, mt_rand() / mt_getrandmax());
            $u2 = mt_rand() / mt_getrandmax();
            $z0 = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
            $z1 = sqrt(-2.0 * log($u1)) * sin(2.0 * M_PI * $u2);
            $out[] = $media + $sd * $z0;
            if (count($out) < $n) {
                $out[] = $media + $sd * $z1;
            }
        }

        return array_slice($out, 0, $n);
    }
}
