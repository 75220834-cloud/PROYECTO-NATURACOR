<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloque 3 — Verifica que el schedule nocturno del motor de recomendación
 * esté registrado correctamente en `routes/console.php`.
 *
 * Estos tests son la red de seguridad contra el clásico bug de "alguien
 * borró el schedule por error y nadie se entera hasta que el primer request
 * del día empieza a tardar 8 segundos".
 */
class ScheduleRecomendacionesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_schedule_de_perfiles_esta_registrado(): void
    {
        $eventos = $this->eventosPorNombre('recomendaciones-perfiles');

        $this->assertNotEmpty($eventos,
            'No se encontró ningún evento de schedule llamado "recomendaciones-perfiles".');

        $evento = $eventos[0];
        $this->assertSame(
            (string) config('recommendaciones.jobs.perfiles_hora', '02:00'),
            $this->extraerHoraDelCron($evento->expression),
            'El cron expression debe corresponder a la hora configurada.'
        );
    }

    #[Test]
    public function el_schedule_de_cooccurrencia_esta_registrado(): void
    {
        $eventos = $this->eventosPorNombre('recomendaciones-cooccurrencia');

        $this->assertNotEmpty($eventos,
            'No se encontró ningún evento de schedule llamado "recomendaciones-cooccurrencia".');

        $evento = $eventos[0];
        $this->assertSame(
            (string) config('recommendaciones.jobs.cooccurrencia_hora', '02:30'),
            $this->extraerHoraDelCron($evento->expression),
        );
    }

    #[Test]
    public function ambos_jobs_se_disparan_solo_una_vez_al_dia(): void
    {
        $perfiles = $this->eventosPorNombre('recomendaciones-perfiles');
        $cooc = $this->eventosPorNombre('recomendaciones-cooccurrencia');

        $this->assertCount(1, $perfiles, 'Solo debe haber 1 entrada de schedule para perfiles.');
        $this->assertCount(1, $cooc, 'Solo debe haber 1 entrada de schedule para co-ocurrencia.');

        $cronDailyPattern = '/^\d+\s\d+\s\*\s\*\s\*$/';
        $this->assertMatchesRegularExpression($cronDailyPattern, $perfiles[0]->expression,
            'El schedule de perfiles debe ser diario (cron "M H * * *").');
        $this->assertMatchesRegularExpression($cronDailyPattern, $cooc[0]->expression,
            'El schedule de co-ocurrencia debe ser diario (cron "M H * * *").');
    }

    #[Test]
    public function la_config_jobs_expone_todas_las_keys_documentadas(): void
    {
        $jobs = config('recommendaciones.jobs');

        $this->assertIsArray($jobs);
        foreach (['perfiles_enabled', 'perfiles_hora', 'perfiles_chunk',
                  'cooccurrencia_enabled', 'cooccurrencia_hora', 'cola'] as $key) {
            $this->assertArrayHasKey($key, $jobs, "Falta key de config recommendaciones.jobs.{$key}");
        }
    }

    /**
     * @return array<int, \Illuminate\Console\Scheduling\Event>
     */
    private function eventosPorNombre(string $nombre): array
    {
        $schedule = $this->app->make(Schedule::class);

        return collect($schedule->events())
            ->filter(fn ($evento) => $evento->description === $nombre)
            ->values()
            ->all();
    }

    /**
     * Convierte un cron expression diario "M H * * *" a "HH:MM".
     */
    private function extraerHoraDelCron(string $expression): string
    {
        $partes = preg_split('/\s+/', trim($expression));
        if (count($partes) < 2) {
            return $expression;
        }
        [$min, $hora] = $partes;

        return str_pad($hora, 2, '0', STR_PAD_LEFT).':'.str_pad($min, 2, '0', STR_PAD_LEFT);
    }
}
