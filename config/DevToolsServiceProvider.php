<?php

declare(strict_types=1);

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Fruitcake\LaravelDebugbar\Facades\Debugbar;
use Illuminate\Support\ServiceProvider;

/**
 * Centralizes registration and hard environment guards for local-only
 * developer tooling (Debugbar, IDE Helper).
 *
 * Telescope is intentionally NOT registered here — it has its own
 * provider with tenant-aware gating. See telescope.notes.md.
 *
 * Register this provider in bootstrap/providers.php.
 */
final class DevToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        // IDE Helper: only meaningful in local dev.
        $this->app->register(IdeHelperServiceProvider::class);
    }

    public function boot(): void
    {
        // Defense in depth: even if config or .env is misconfigured in a
        // non-local environment, Debugbar is forcibly disabled. This is
        // critical for a headless multi-tenant API where the toolbar /
        // JSON collector could leak cross-tenant data.
        if (! $this->app->environment('local')) {
            if (class_exists(Debugbar::class)) {
                Debugbar::disable();
            }

            return;
        }

        // Local only: never auto-EXPLAIN queries against a shared dev DB.
        if (class_exists(Debugbar::class)) {
            Debugbar::enable();
        }
    }
}
