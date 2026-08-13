<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use Modules\Rbac\Application\Services\AuthorizationService;
use Modules\Rbac\Application\Services\RbacService;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Tests\Feature\RbacTestCase;

/**
 * The `authorization.permission` capability must resolve to the public
 * RBAC contracts, never to implementation details.
 */
class RbacContractResolutionTest extends RbacTestCase
{
    public function test_capability_resolves_to_public_contracts(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $this->assertTrue(
            app(CapabilityRegistryContract::class)->has('authorization.permission'),
        );
        $this->assertInstanceOf(
            RoleManagementContract::class,
            app(RoleManagementContract::class),
        );
        $this->assertInstanceOf(
            AuthorizationContract::class,
            app(AuthorizationContract::class),
        );
    }

    public function test_contracts_resolve_to_rbac_service_implementations(): void
    {
        $this->assertInstanceOf(
            RbacService::class,
            app(RoleManagementContract::class),
        );
        $this->assertInstanceOf(
            AuthorizationService::class,
            app(AuthorizationContract::class),
        );
    }
}
