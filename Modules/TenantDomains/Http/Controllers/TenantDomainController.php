<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;

final class TenantDomainController extends Controller
{
    public function __construct(
        private readonly TenantDomainContract $domains,
        private readonly TenantContract $tenants,
    ) {}

    public function index(): View
    {
        $tenants = $this->tenants->allActive();
        $byTenant = [];
        foreach ($tenants as $tenant) {
            $byTenant[$tenant->id] = $this->domains->listForTenant($tenant->id);
        }

        return view('tenant-domains::index', [
            'tenants' => $tenants,
            'domainsByTenant' => $byTenant,
        ]);
    }

    public function show(int $tenantId): View|RedirectResponse
    {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null || ! $tenant->active) {
            return redirect()
                ->route('tenant-domains.index')
                ->withErrors(['tenant' => 'Tenant was not found or is inactive.']);
        }

        return view('tenant-domains::show', [
            'tenant' => $tenant,
            'domains' => $this->domains->listForTenant($tenantId),
        ]);
    }

    public function store(Request $request, int $tenantId): RedirectResponse
    {
        $data = $request->validate([
            'hostname' => ['required', 'string', 'max:255'],
            'primary' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->domains->attach(
                $tenantId,
                $data['hostname'],
                $request->boolean('primary'),
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['hostname' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant-domains.show', ['tenantId' => $tenantId])
            ->with('status', 'Domain attached.');
    }

    public function setPrimary(int $tenantId, int $domainId): RedirectResponse
    {
        try {
            $this->domains->setPrimary($tenantId, $domainId);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('tenant-domains.show', ['tenantId' => $tenantId])
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant-domains.show', ['tenantId' => $tenantId])
            ->with('status', 'Primary domain updated.');
    }

    public function activate(int $tenantId, int $domainId): RedirectResponse
    {
        try {
            $this->domains->activate($domainId);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant-domains.show', ['tenantId' => $tenantId])
            ->with('status', 'Domain activated.');
    }

    public function deactivate(int $tenantId, int $domainId): RedirectResponse
    {
        try {
            $this->domains->deactivate($domainId);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant-domains.show', ['tenantId' => $tenantId])
            ->with('status', 'Domain deactivated.');
    }

    public function destroy(int $tenantId, int $domainId): RedirectResponse
    {
        try {
            $this->domains->detach($domainId);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant-domains.show', ['tenantId' => $tenantId])
            ->with('status', 'Domain detached.');
    }
}
