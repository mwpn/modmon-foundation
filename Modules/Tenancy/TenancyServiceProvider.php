<?php

declare(strict_types=1);

namespace Modules\Tenancy;

use Illuminate\Support\ServiceProvider;

/**
 * Tenancy module service provider.
 *
 * Phase 0: scaffold + locked public contracts only. No bindings,
 * migrations, routes, or Experience contributions yet.
 */
class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phase 1: bind TenantContract, MembershipContract,
        // TenantContextContract to module-owned implementations.
    }

    public function boot(): void
    {
        //
    }
}
