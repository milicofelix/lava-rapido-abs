<?php

namespace Tests\Feature\App;

use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReprocessWashLocationCoordinatesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reprocesses_pending_location_coordinates(): void
    {
        Http::fakeSequence()
            ->push([
                [
                    'lat' => '-23.5191405',
                    'lon' => '-46.4207678',
                ],
            ], 200);

        $location = WashLocation::factory()->create([
            'name' => 'Lava Rapido Sem Mapa',
            'address' => 'Av. Nordestina',
            'address_number' => '4660',
            'district' => 'Vila Nova Curuca',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->artisan('app:reprocess-location-coordinates', ['--limit' => 5])
            ->expectsOutputToContain('[OK] Lava Rapido Sem Mapa: -23.5191405, -46.4207678')
            ->expectsOutputToContain('Resumo: 1 atualizada(s), 0 pendente(s), 0 ignorada(s).')
            ->assertExitCode(0);

        $location->refresh();

        $this->assertSame('-23.5191405', (string) $location->latitude);
        $this->assertSame('-46.4207678', (string) $location->longitude);
    }

    public function test_command_dry_run_does_not_persist_coordinates(): void
    {
        Http::fakeSequence()
            ->push([
                [
                    'lat' => '-23.5489100',
                    'lon' => '-46.6341200',
                ],
            ], 200);

        $location = WashLocation::factory()->create([
            'name' => 'Lava Rapido Simulado',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->artisan('app:reprocess-location-coordinates', ['--dry-run' => true])
            ->expectsOutputToContain('[OK] Lava Rapido Simulado: -23.5489100, -46.6341200 (simulacao)')
            ->assertExitCode(0);

        $location->refresh();

        $this->assertNull($location->latitude);
        $this->assertNull($location->longitude);
    }

    public function test_command_reports_when_there_are_no_pending_coordinates(): void
    {
        Http::fake();

        WashLocation::factory()->create([
            'latitude' => -23.54891,
            'longitude' => -46.63412,
        ]);

        $this->artisan('app:reprocess-location-coordinates')
            ->expectsOutput('Nenhuma unidade com coordenadas pendentes encontrada.')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }
}
