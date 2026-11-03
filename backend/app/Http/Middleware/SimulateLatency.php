<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local SQLite answers in a few milliseconds, so the frontend's loading states flash by
 * unseen during development. A short pause makes them visible. It only runs locally on
 * SQLite, so a production environment or a real database server never pays for it.
 */
final class SimulateLatency
{
    public function handle(Request $request, Closure $next): Response
    {
        $latencyInMilliseconds = (int) config('app.simulated_latency_ms');

        if ($latencyInMilliseconds > 0 && $this->isLocalSqlite()) {
            Sleep::for($latencyInMilliseconds)->milliseconds();
        }

        return $next($request);
    }

    private function isLocalSqlite(): bool
    {
        return app()->isLocal() && config('database.default') === 'sqlite';
    }
}
