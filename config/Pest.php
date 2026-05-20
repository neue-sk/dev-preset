<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case Binding
|--------------------------------------------------------------------------
| Bind the base TestCase to Feature/Unit. RefreshDatabase is applied to
| Feature only — Unit tests should not touch the database.
*/

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Architecture Tests
|--------------------------------------------------------------------------
| These enforce structural rules across the modular, headless codebase.
| They run as part of the normal Pest suite (composer test).
*/

arch('strict types everywhere')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die', 'ddd'])
    ->not->toBeUsed();

arch('models are well-formed')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend('Model');

arch('data objects extend Spatie Data')
    ->expect('App\Data')
    ->toExtend('Spatie\LaravelData\Data');

arch('controllers are suffixed and final')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('actions are invokable or single-purpose')
    ->expect('App\Actions')
    ->toBeFinal();

arch('no facades inside Domain modules')
    ->expect('App\Modules')
    ->not->toUse('Illuminate\Support\Facades');
