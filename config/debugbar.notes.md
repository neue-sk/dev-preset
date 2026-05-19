# POZOR: balík je teraz fruitcake/laravel-debugbar (v4+), nie barryvdh. Namespace: Fruitcake\LaravelDebugbar.
# Laravel Debugbar — boilerplate guidance
#
# Debugbar renders an HTML toolbar. In a HEADLESS API it has limited value
# and is a data-exposure risk if ever enabled in production. The boilerplate
# policy is:
#
#   1. Install as require-dev ONLY (never in `require`).
#   2. DEBUGBAR_ENABLED is hard-driven by APP_DEBUG and APP_ENV.
#   3. For API responses, Debugbar can inject into JSON via the
#      `Debugbar` data collector — keep that DISABLED by default; enable
#      per-developer locally via .env, never committed.
#
# --- .env.example entries (add these) ---------------------------------
#
#   DEBUGBAR_ENABLED=false
#
# --- config/debugbar.php overrides (publish then apply) ---------------
#   php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
#
# Then set in config/debugbar.php:
#
#   'enabled' => env('DEBUGBAR_ENABLED', null),   // null = follow APP_DEBUG
#   'except'  => [
#       'telescope*',
#       'horizon*',
#       'api/health*',
#   ],
#   'collectors' => [
#       'auth'        => true,   // shows tenant/user — keep local-only
#       'db'          => true,
#       'models'      => true,
#       'mail'        => false,  // avoid rendering mail in API context
#   ],
#   'options' => [
#       'db' => [
#           'with_params'      => true,
#           'backtrace'        => true,
#           'timeline'         => true,
#           'explain' => ['enabled' => false], // never auto-EXPLAIN in shared DB
#       ],
#   ],
#
# --- Hard guard (add to AppServiceProvider::boot) ---------------------
#
#   if (! $this->app->environment('local')) {
#       \Debugbar::disable();
#   }
#
# This guard is also generated in DevToolsServiceProvider.php in this
# boilerplate — prefer that single source of truth.
