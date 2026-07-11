<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_reports_application_ready(): void
    {
        $this->getJson(route('readiness.show'))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.0.name', 'database')
            ->assertJsonPath('checks.0.ok', true)
            ->assertJsonMissing(['message' => 'Banco de dados acessivel.']);
    }

    public function test_readiness_command_reports_application_ready(): void
    {
        $this->artisan('app:readiness-check')
            ->expectsOutputToContain('[OK] Banco de dados acessivel.')
            ->expectsOutputToContain('[OK] Cache acessivel.')
            ->expectsOutputToContain('Aplicacao pronta para receber trafego.')
            ->assertExitCode(0);
    }
}
