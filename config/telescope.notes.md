# Laravel Telescope — boilerplate guidance (multi-tenant, headless)
#
# Telescope is valuable for a headless API (request/job/query inspection)
# but is HIGH RISK in multi-tenant production: it records request payloads,
# which may contain cross-tenant PII. Boilerplate policy:
#
#   1. require-dev only. Use the `--dev` install:
#        php artisan telescope:install
#      Then move the service provider registration so it is dev-gated.
#
#   2. In bootstrap/providers.php DO NOT register TelescopeServiceProvider
#      unconditionally. Instead register the local-only provider
#      conditionally inside App\Providers\TelescopeServiceProvider::register
#      using:  if ($this->app->environment('local')) { ... }
#
#   3. composer.json — prevent prod autoload discovery:
#
#        "extra": {
#          "laravel": {
#            "dont-discover": ["laravel/telescope"]
#          }
#        }
#
#   4. Gate the dashboard by tenant-aware authorization.
#      In App\Providers\TelescopeServiceProvider::gate():
#
#        Gate::define('viewTelescope', function ($user) {
#            // Restrict to platform super-admins only — NEVER tenant users.
#            return $user->is_platform_admin === true;
#        });
#
#   5. Always enable filtering + pruning to bound storage:
#
#        Telescope::filter(function (IncomingEntry $entry) {
#            if (app()->environment('local')) {
#                return true;
#            }
#            return $entry->isReportableException()
#                || $entry->isFailedRequest()
#                || $entry->isFailedJob()
#                || $entry->isScheduledTask()
#                || $entry->hasMonitoredTag();
#        });
#
#      Schedule pruning in routes/console.php (Laravel 11+ style):
#
#        Schedule::command('telescope:prune --hours=48')->daily();
#
#   6. Redact sensitive request fields globally:
#
#        Telescope::hideRequestParameters(['_token']);
#        Telescope::hideRequestHeaders([
#            'cookie', 'x-csrf-token', 'x-xsrf-token', 'authorization',
#        ]);
#
# --- .env.example entries ---------------------------------------------
#
#   TELESCOPE_ENABLED=false
#
# --- config/telescope.php override ------------------------------------
#
#   'enabled' => env('TELESCOPE_ENABLED', false),
