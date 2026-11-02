<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use InvalidArgumentException;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantDomains\Application\HostnameNormalizer;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Http\Middleware\ResolveTenantDomain;

class TenantDomainsContractTest extends TenantDomainsTestCase
{
    public function test_hostname_normalization(): void
    {
        $n = new HostnameNormalizer;

        $this->assertSame('acme.example.com', $n->normalize('  ACME.Example.COM:443. '));
        $this->assertSame('acme.example.com', $n->normalize('acme.example.com.'));

        $this->expectException(InvalidArgumentException::class);
        $n->normalize('https://acme.example.com');
    }

    public function test_normalize_rejects_ip_and_path(): void
    {
        $n = new HostnameNormalizer;
        $this->assertNull($n->tryNormalize('127.0.0.1'));
        $this->assertNull($n->tryNormalize('acme.example.com/path'));
        $this->assertNull($n->tryNormalize(''));
    }

    public function test_attach_resolve_primary_and_inactive_semantics(): void
    {
        $this->installStack();
        $domains = app(TenantDomainContract::class);
        $tenant = $this->createActiveTenant();

        $a = $domains->attach($tenant->id, 'www.acme.test', primary: true);
        $b = $domains->attach($tenant->id, 'acme.test', primary: false);

        $this->assertTrue($a->isPrimary);
        $this->assertFalse($b->isPrimary);

        $resolved = $domains->resolve('WWW.Acme.Test:443');
        $this->assertNotNull($resolved);
        $this->assertSame($tenant->id, $resolved->tenantId);
        $this->assertSame('www.acme.test', $resolved->hostname);
        $this->assertTrue($resolved->isPrimary);
        $this->assertSame('acme', $resolved->tenant->code);

        $domains->setPrimary($tenant->id, $b->id);
        $list = $domains->listForTenant($tenant->id);
        $primaries = array_values(array_filter($list, fn ($d) => $d->isPrimary));
        $this->assertCount(1, $primaries);
        $this->assertSame('acme.test', $primaries[0]->hostname);

        $domains->deactivate($a->id);
        $this->assertNull($domains->resolve('www.acme.test'));
        $this->assertNotNull($domains->resolve('acme.test'));

        app(TenantContract::class)->deactivate($tenant->id);
        $this->assertNull($domains->resolve('acme.test'));
    }

    public function test_hostname_is_globally_unique(): void
    {
        $this->installStack();
        $domains = app(TenantDomainContract::class);
        $a = $this->createActiveTenant('a', 'A');
        $b = $this->createActiveTenant('b', 'B');
        $domains->attach($a->id, 'shared.example.com');

        $this->expectException(InvalidArgumentException::class);
        $domains->attach($b->id, 'shared.example.com');
    }

    public function test_resolve_and_middleware_never_mutate_tenant_context(): void
    {
        $this->installStack();
        $domains = app(TenantDomainContract::class);
        $context = app(TenantContextContract::class);
        $memberships = app(MembershipContract::class);

        $tenantA = $this->createActiveTenant('alpha', 'Alpha');
        $tenantB = $this->createActiveTenant('beta', 'Beta');
        $user = $this->createUser();

        $memberships->add($user->id, $tenantA->id);
        $memberships->add($user->id, $tenantB->id);
        $context->setCurrent($user->id, $tenantA->id);
        $this->assertSame($tenantA->id, $context->currentTenantId($user->id));

        $domains->attach($tenantB->id, 'beta.example.com', primary: true);

        $resolved = $domains->resolve('beta.example.com');
        $this->assertNotNull($resolved);
        $this->assertSame($tenantB->id, $resolved->tenantId);
        // Context must remain Alpha — hostname resolve must not call setCurrent/clear.
        $this->assertSame($tenantA->id, $context->currentTenantId($user->id));
        $this->assertSame('alpha', $context->current($user->id)->tenantCode);

        $request = \Illuminate\Http\Request::create('https://beta.example.com/tenant-domains', 'GET');
        $middleware = app(ResolveTenantDomain::class);
        $middleware->handle($request, function ($req) {
            $this->assertSame(
                app(TenantDomainContract::class)->resolve('beta.example.com')?->tenantId,
                $req->attributes->get(ResolveTenantDomain::ATTR_TENANT_ID),
            );
            $this->assertInstanceOf(
                \Modules\TenantDomains\Domain\DTOs\TenantDomainResolution::class,
                $req->attributes->get(ResolveTenantDomain::ATTR_RESOLUTION),
            );

            return response('ok');
        });

        $this->assertSame($tenantA->id, $context->currentTenantId($user->id));
    }
}
