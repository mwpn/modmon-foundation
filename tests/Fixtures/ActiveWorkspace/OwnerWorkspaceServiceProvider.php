<?php

declare(strict_types=1);

namespace Tests\Fixtures\ActiveWorkspace;

use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;
use Illuminate\Support\ServiceProvider;

/**
 * Fixture consumer provider: rebinds ActiveWorkspaceContract the same way
 * a workspace module/host would during normal Laravel provider boot.
 */
final class OwnerWorkspaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveWorkspaceContract::class, OwnerActiveWorkspace::class);
    }
}
