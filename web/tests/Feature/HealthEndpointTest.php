<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_endpoint_remains_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_diagnostic_endpoint_reports_an_incomplete_schema_without_leaking_secrets(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('database.status', 'schema_incomplete')
            ->assertJsonStructure([
                'application' => ['name', 'environment', 'laravel_version'],
                'database' => ['status', 'connection', 'database', 'latency_ms', 'missing_tables'],
                'integrations',
                'timestamp',
            ]);

        $this->assertStringNotContainsString('password', strtolower($response->getContent()));
        $this->assertContains('attendance', $response->json('database.missing_tables'));
    }

    public function test_diagnostic_endpoint_handles_an_unavailable_database_without_crashing(): void
    {
        $originalConnection = config('database.default');

        try {
            config([
                'database.default' => 'unavailable',
                'database.connections.unavailable' => [
                    'driver' => 'sqlite',
                    'database' => database_path('missing/database.sqlite'),
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
            ]);
            DB::purge('unavailable');

            $this->getJson('/api/health')
                ->assertServiceUnavailable()
                ->assertJsonPath('status', 'degraded')
                ->assertJsonPath('database.status', 'unavailable')
                ->assertJsonPath(
                    'database.message',
                    'The database connection is unavailable. Check the configured DB_* variables.',
                );
        } finally {
            config(['database.default' => $originalConnection]);
            DB::purge('unavailable');
        }
    }
}
