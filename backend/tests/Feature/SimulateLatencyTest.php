<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SimulateLatency;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SimulateLatencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        config(['app.simulated_latency_ms' => 500, 'database.default' => 'sqlite']);
    }

    public function test_local_sqlite_requests_are_delayed(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->handleRequest();

        Sleep::assertSequence([Sleep::for(500)->milliseconds()]);
    }

    public function test_other_environments_are_not_delayed(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->handleRequest();

        Sleep::assertNeverSlept();
    }

    public function test_database_servers_are_not_delayed(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.default' => 'pgsql']);

        $this->handleRequest();

        Sleep::assertNeverSlept();
    }

    public function test_zero_latency_disables_the_delay(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['app.simulated_latency_ms' => 0]);

        $this->handleRequest();

        Sleep::assertNeverSlept();
    }

    private function handleRequest(): void
    {
        (new SimulateLatency)->handle(Request::create('/api/v1/items'), fn () => new Response);
    }
}
