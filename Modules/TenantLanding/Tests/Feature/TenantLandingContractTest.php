<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Feature;

use InvalidArgumentException;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Domain\DTOs\TenantLandingData;

class TenantLandingContractTest extends TenantLandingTestCase
{
    public function test_for_tenant_defaults_without_row_and_update_is_unique_per_tenant(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant();
        $contract = app(TenantLandingContract::class);

        $defaults = $contract->forTenant($tenant->id);
        $this->assertFalse($defaults->configured);
        $this->assertNull($defaults->headline);
        $this->assertTrue($defaults->showLoginCta);
        $this->assertDatabaseCount('tenant_landing', 0);

        $saved = $contract->update($tenant->id, new TenantLandingData(
            headline: 'Welcome Acme',
            summary: 'Short blurb',
            showLoginCta: false,
        ));
        $this->assertTrue($saved->configured);
        $this->assertSame('Welcome Acme', $saved->headline);
        $this->assertSame('Short blurb', $saved->summary);
        $this->assertFalse($saved->showLoginCta);
        $this->assertDatabaseCount('tenant_landing', 1);

        $contract->update($tenant->id, new TenantLandingData(
            headline: 'Updated',
            summary: null,
            showLoginCta: true,
        ));
        $this->assertDatabaseCount('tenant_landing', 1);
        $this->assertDatabaseHas('tenant_landing', [
            'tenant_id' => $tenant->id,
            'headline' => 'Updated',
            'summary' => null,
            'show_login_cta' => 1,
        ]);
    }

    public function test_update_rejects_unknown_or_inactive_tenant(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant();
        app(\Modules\Tenancy\Domain\Contracts\TenantContract::class)->deactivate($tenant->id);
        $contract = app(TenantLandingContract::class);

        try {
            $contract->update(999999, new TenantLandingData(headline: 'Nope'));
            $this->fail('Expected unknown tenant rejection');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            $contract->update($tenant->id, new TenantLandingData(headline: 'Nope'));
            $this->fail('Expected inactive tenant rejection');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseCount('tenant_landing', 0);
    }
}
