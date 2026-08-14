<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Rbac\Application\Services\AuthorizationService;
use Modules\Rbac\Application\Services\RbacService;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;

/**
 * RBAC lifecycle test: install → disabled → re-enabled, with data
 * preserved. Runs the RBAC-owned migrations through the Foundation
 * `module:install` lifecycle (explicit install semantics), exactly as
 * a portable module would on a real host.
 *
 * @property FakeUserQuery $userQuery
 */
abstract class RbacTestCase extends BaseTestCase
{
    protected FakeUserQuery $userQuery;

    /**
     * Install Identity through the Foundation lifecycle so `identity.user`
     * is available. Compliance tests that prove the missing-dependency
     * failure path set this to false.
     */
    protected bool $installIdentity = true;

    /**
     * Pre-run RBAC migrations outside `module:install`. Lifecycle and
     * compliance tests set this to false so install itself is what
     * creates the owned tables.
     */
    protected bool $preMigrateRbac = true;

    private string $modulesJsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesJsonPath = storage_path('app/modules.json');

        // Clean module registry state per test so module:install is
        // repeatable regardless of what the dev host's modules.json
        // contains (same isolation as ModuleLifecycleTest).
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->app->forgetInstance(\App\Foundation\SDK\Contracts\ModuleRegistrarContract::class);
        $this->app->forgetInstance(\App\Foundation\Runtime\ModuleManager::class);
        $this->app->forgetInstance(\App\Foundation\SDK\Contracts\CapabilityRegistryContract::class);
        $this->app->forgetInstance(\App\Foundation\SDK\Contracts\NavigationRegistryContract::class);
        $this->app->forgetInstance(\App\Foundation\SDK\Contracts\WorkspaceRegistryContract::class);
        $this->app->forgetInstance(\App\Foundation\SDK\Contracts\PermissionRegistryContract::class);

        $this->userQuery = new FakeUserQuery;
        $this->userQuery->seed(1);

        if ($this->installIdentity) {
            // Phase 1 prerequisite: Identity must be available on the host as
            // a portable module, installed through the Foundation lifecycle,
            // so `identity.user` is registered before RBAC installs. Without
            // this the capability check in ModuleManager::install fails.
            $this->artisan('module:install', ['code' => 'identity'])->assertSuccessful();
        }

        $this->app->instance(UserQueryContract::class, $this->userQuery);
        $this->app->instance(
            RoleManagementContract::class,
            $this->app->make(RbacService::class),
        );
        $this->app->instance(
            AuthorizationContract::class,
            new AuthorizationService(
                $this->app->make(RoleManagementContract::class),
                $this->userQuery,
            ),
        );

        $this->registerFixturePermission('rbac-test.permission');

        if ($this->preMigrateRbac) {
            // --realpath is required on Windows: without it Laravel prepends
            // basePath() to the already-absolute path, doubling it and
            // producing "Nothing to migrate" (see ModuleManager::install).
            $this->artisan('migrate', [
                '--path' => realpath(base_path('Modules/Rbac/Database/Migrations')),
                '--realpath' => true,
                '--force' => true,
            ])->assertSuccessful();
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->userQuery = new FakeUserQuery;

        parent::tearDown();
    }

    protected function registerFixturePermission(string $permissionId): void
    {
        $this->app->make(PermissionRegistryContract::class)->register(
            'rbac-test-fixture',
            [
                new PermissionDefinition(
                    id: $permissionId,
                    moduleCode: 'rbac-test-fixture',
                    label: "Fixture {$permissionId}",
                ),
            ],
        );
    }
}
