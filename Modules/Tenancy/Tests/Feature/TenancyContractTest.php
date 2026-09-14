<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\Exceptions\InvalidTenantAttributeException;
use Modules\Tenancy\Domain\Exceptions\InvalidTenantContextException;
use Modules\Tenancy\Domain\Exceptions\TenantCodeAlreadyExistsException;
use Modules\Tenancy\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenancy\Domain\Exceptions\UnknownUserException;

/**
 * Domain invariants for tenants, membership (no roles), and context.
 */
final class TenancyContractTest extends TenancyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->installIdentityAndTenancy();
    }

    public function test_tenant_create_find_rename_activate_deactivate(): void
    {
        $tenants = app(TenantContract::class);

        $created = $tenants->create('acme', 'Acme Corp');
        $this->assertSame('acme', $created->code);
        $this->assertTrue($created->active);
        $this->assertTrue($tenants->exists('acme'));
        $this->assertSame('acme', $tenants->findById($created->id)?->code);
        $this->assertSame('acme', $tenants->findByCode('ACME')?->code);

        $renamed = $tenants->rename($created->id, 'Acme Renamed');
        $this->assertSame('Acme Renamed', $renamed->name);

        $tenants->deactivate($created->id);
        $this->assertFalse($tenants->findById($created->id)?->active);
        $this->assertSame([], $tenants->allActive());
        $this->assertCount(1, $tenants->all());

        $tenants->activate($created->id);
        $this->assertTrue($tenants->findById($created->id)?->active);
        $this->assertCount(1, $tenants->allActive());
        $this->assertCount(1, $tenants->all());
    }

    public function test_tenant_rejects_duplicate_and_invalid_code(): void
    {
        $tenants = app(TenantContract::class);
        $tenants->create('acme', 'Acme');

        $this->expectException(TenantCodeAlreadyExistsException::class);
        $tenants->create('acme', 'Other');
    }

    public function test_tenant_rejects_empty_name(): void
    {
        $this->expectException(InvalidTenantAttributeException::class);
        app(TenantContract::class)->create('acme', '  ');
    }

    public function test_membership_add_list_remove_validates_identity_user(): void
    {
        $tenants = app(TenantContract::class);
        $memberships = app(MembershipContract::class);
        $user = $this->createUser();
        $tenant = $tenants->create('acme', 'Acme');

        $member = $memberships->add($user->id, $tenant->id);
        $this->assertSame($user->id, $member->userId);
        $this->assertTrue($memberships->isMember($user->id, $tenant->id));
        $this->assertCount(1, $memberships->membershipsForUser($user->id));
        $this->assertCount(1, $memberships->membersOfTenant($tenant->id));

        // Idempotent re-add
        $again = $memberships->add($user->id, $tenant->id);
        $this->assertSame($member->id, $again->id);

        $memberships->remove($user->id, $tenant->id);
        $this->assertFalse($memberships->isMember($user->id, $tenant->id));
    }

    public function test_membership_rejects_unknown_user(): void
    {
        $tenant = app(TenantContract::class)->create('acme', 'Acme');

        $this->expectException(UnknownUserException::class);
        app(MembershipContract::class)->add(999999, $tenant->id);
    }

    public function test_membership_rejects_unknown_tenant(): void
    {
        $user = $this->createUser();

        $this->expectException(TenantNotFoundException::class);
        app(MembershipContract::class)->add($user->id, 999999);
    }

    public function test_context_set_clear_and_fail_closed(): void
    {
        $tenants = app(TenantContract::class);
        $memberships = app(MembershipContract::class);
        $context = app(TenantContextContract::class);
        $user = $this->createUser();
        $tenant = $tenants->create('acme', 'Acme');
        $memberships->add($user->id, $tenant->id);

        $this->assertNull($context->currentTenantId($user->id));

        $context->setCurrent($user->id, $tenant->id);
        $this->assertSame($tenant->id, $context->currentTenantId($user->id));
        $this->assertSame('acme', $context->current($user->id)->tenantCode);
        $this->assertSame('acme', $context->currentTenant($user->id)?->code);

        $context->clear($user->id);
        $this->assertNull($context->currentTenantId($user->id));
        $this->assertTrue($memberships->isMember($user->id, $tenant->id));
        $this->assertNotNull($tenants->findById($tenant->id));
    }

    public function test_context_rejects_non_member(): void
    {
        $tenants = app(TenantContract::class);
        $context = app(TenantContextContract::class);
        $user = $this->createUser();
        $tenant = $tenants->create('acme', 'Acme');

        $this->expectException(InvalidTenantContextException::class);
        $context->setCurrent($user->id, $tenant->id);
    }

    public function test_context_rejects_inactive_tenant(): void
    {
        $tenants = app(TenantContract::class);
        $memberships = app(MembershipContract::class);
        $context = app(TenantContextContract::class);
        $user = $this->createUser();
        $tenant = $tenants->create('acme', 'Acme');
        $memberships->add($user->id, $tenant->id);
        $tenants->deactivate($tenant->id);

        $this->expectException(InvalidTenantContextException::class);
        $context->setCurrent($user->id, $tenant->id);
    }

    public function test_context_read_fail_closed_when_tenant_deactivated_or_membership_removed(): void
    {
        $tenants = app(TenantContract::class);
        $memberships = app(MembershipContract::class);
        $context = app(TenantContextContract::class);
        $user = $this->createUser();
        $tenant = $tenants->create('acme', 'Acme');
        $memberships->add($user->id, $tenant->id);
        $context->setCurrent($user->id, $tenant->id);

        $tenants->deactivate($tenant->id);
        $this->assertNull($context->currentTenantId($user->id));

        $tenants->activate($tenant->id);
        $this->assertSame($tenant->id, $context->currentTenantId($user->id));

        $memberships->remove($user->id, $tenant->id);
        $this->assertNull($context->currentTenantId($user->id));
        $this->assertDatabaseHas('tenancy_contexts', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_context_rejects_unknown_user(): void
    {
        $tenant = app(TenantContract::class)->create('acme', 'Acme');

        $this->expectException(UnknownUserException::class);
        app(TenantContextContract::class)->setCurrent(999999, $tenant->id);
    }
}
