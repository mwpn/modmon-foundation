<?php

declare(strict_types=1);

namespace Modules\Settings;

use Illuminate\Support\ServiceProvider;
use Modules\Settings\Application\Services\DatabaseRuntimeSettings;
use Modules\Settings\Domain\Contracts\RuntimeSettingsContract;

/**
 * Settings module service provider.
 *
 * Phase 1: binds RuntimeSettingsContract to the database-backed store.
 * Experience contributions (routes/nav/permissions) are deferred.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuntimeSettingsContract::class, DatabaseRuntimeSettings::class);
    }

    public function boot(): void
    {
        //
    }
}
