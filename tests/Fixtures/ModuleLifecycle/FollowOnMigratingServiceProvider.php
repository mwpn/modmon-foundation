<?php

declare(strict_types=1);

namespace Tests\Fixtures\ModuleLifecycle;

use Illuminate\Support\ServiceProvider;

final class FollowOnMigratingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
}
