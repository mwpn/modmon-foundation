<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Application\Services\DatabaseTenantContext;
use Modules\Tenancy\Application\Services\MembershipService;
use Modules\Tenancy\Application\Services\TenantService;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;

/**
 * Discovery does not migrate; explicit install owns schema + capabilities.
 */
final class TenancyInstallTest extends TenancyTestCase
{
    public function test_discovered_alone_does_not_create_tenancy_tables(): void
    {
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy'));
        $this->assertFalse(Schema::hasTable('tenancy_tenants'));
        $this->assertFalse(Schema::hasTable('tenancy_memberships'));
        $this->assertFalse(Schema::hasTable('tenancy_contexts'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.tenant'));
    }

    public function test_doctor_fails_without_identity(): void
    {
        $diagnostics = app(\App\Foundation\Runtime\ModuleManager::class)->diagnose('tenancy');
        $capabilities = collect($diagnostics)->first(
            fn ($d) => $d->check === 'capabilities',
        );

        $this->assertNotNull($capabilities);
        $this->assertFalse($capabilities->passed);
        $this->assertStringContainsString('identity.user', $capabilities->message);
    }

    public function test_doctor_passes_with_identity_installed(): void
    {
        $this->installIdentity();

        $diagnostics = app(\App\Foundation\Runtime\ModuleManager::class)->diagnose('tenancy');

        foreach ($diagnostics as $diagnostic) {
            $this->assertTrue(
                $diagnostic->passed,
                "{$diagnostic->check}: {$diagnostic->message}",
            );
        }
    }

    public function test_install_creates_owned_tables_and_registers_capabilities(): void
    {
        $this->installIdentityAndTenancy();

        $this->assertTrue(Schema::hasTable('tenancy_tenants'));
        $this->assertTrue(Schema::hasTable('tenancy_memberships'));
        $this->assertTrue(Schema::hasTable('tenancy_contexts'));
        $this->assertTrue(Schema::hasColumns('tenancy_tenants', ['id', 'code', 'name', 'active', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('tenancy_memberships', ['id', 'tenant_id', 'user_id', 'created_at', 'updated_at']));
        $this->assertFalse(
            Schema::hasColumn('tenancy_memberships', 'role'),
            'Membership must not have a role column',
        );

        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('tenancy'),
        );

        $caps = app(CapabilityRegistryContract::class);
        $this->assertTrue($caps->has('tenancy.tenant'));
        $this->assertTrue($caps->has('tenancy.membership'));
        $this->assertTrue($caps->has('tenancy.context'));
    }

    public function test_contracts_are_bound_after_install(): void
    {
        $this->installIdentityAndTenancy();

        $this->assertInstanceOf(TenantService::class, app(TenantContract::class));
        $this->assertInstanceOf(MembershipService::class, app(MembershipContract::class));
        $this->assertInstanceOf(DatabaseTenantContext::class, app(TenantContextContract::class));
    }

    public function test_install_fails_without_identity(): void
    {
        $result = app(\App\Foundation\Runtime\ModuleManager::class)->install('tenancy');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('identity.user', implode(' ', $result['messages']));
        $this->assertFalse(Schema::hasTable('tenancy_tenants'));
    }
}
