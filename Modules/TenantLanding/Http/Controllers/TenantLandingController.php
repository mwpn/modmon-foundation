<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Domain\DTOs\TenantLandingData;

final class TenantLandingController extends Controller
{
    public function __construct(
        private readonly TenantLandingContract $landings,
        private readonly TenantContract $tenants,
    ) {}

    public function index(): View
    {
        $tenants = $this->tenants->allActive();
        $byTenant = [];
        foreach ($this->landings->allConfigured() as $row) {
            $byTenant[$row->tenantId] = $row;
        }

        return view('tenant-landing::index', [
            'tenants' => $tenants,
            'configsByTenant' => $byTenant,
        ]);
    }

    public function edit(int $tenantId): View|RedirectResponse
    {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null || ! $tenant->active) {
            return redirect()
                ->route('tenant-landing.index')
                ->withErrors(['tenant' => 'Tenant was not found or is inactive.']);
        }

        return view('tenant-landing::edit', [
            'tenant' => $tenant,
            'config' => $this->landings->forTenant($tenantId),
        ]);
    }

    public function update(Request $request, int $tenantId): RedirectResponse
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'show_login_cta' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->landings->update($tenantId, new TenantLandingData(
                headline: $data['headline'] ?? null,
                summary: $data['summary'] ?? null,
                showLoginCta: $request->boolean('show_login_cta', true),
            ));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['tenant' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant-landing.edit', ['tenantId' => $tenantId])
            ->with('status', 'Landing saved.');
    }
}
