# Laravel IDE Helper — boilerplate guidance
#
# Purpose: generates _ide_helper.php, _ide_helper_models.php and
# .phpstorm.meta.php so the IDE + Larastan understand facades, macros,
# and Eloquent model attributes. These files are GENERATED ARTIFACTS —
# they are .gitignore'd in this boilerplate and regenerated on demand.
#
# --- Install ----------------------------------------------------------
#   composer require --dev barryvdh/laravel-ide-helper
#
# Registration is handled by App\Providers\DevToolsServiceProvider
# (local env only).
#
# --- Publish config ---------------------------------------------------
#   php artisan vendor:publish --provider="Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider" --tag=config
#
# --- Recommended config/ide-helper.php overrides ----------------------
#
#   'include_fluent'        => true,
#   'write_model_magic_where' => true,
#   'write_model_external_builder_methods' => true,
#   'write_model_relation_count_properties' => true,
#   'post_migrate' => [
#       'ide-helper:models --nowrite --write-mixin',
#   ],
#   'model_locations' => [
#       'app/Models',
#       'app/Modules/*/Models',   // align with modular layout
#   ],
#
# --- Model annotation strategy ----------------------------------------
# Use the MIXIN strategy (not inline PHPDoc injection) so model files
# stay clean and Pint/Rector don't fight generated docblocks:
#
#   php artisan ide-helper:models --nowrite --write-mixin
#
# This writes a thin _ide_helper_models.php with @mixin classes and adds
# one `/** @mixin IdeHelperModelName */` line per model — compatible with
# Larastan level 9 and `declare_strict_types`.
#
# --- Regeneration (already wired in composer.partial.json) ------------
#   composer ide-helper
#
# Runs automatically on post-update-cmd. Also add to CI cache-warm if
# Larastan needs the model mixins (recommended for level 9).
