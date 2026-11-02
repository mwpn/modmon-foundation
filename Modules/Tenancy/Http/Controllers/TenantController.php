<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\Exceptions\InvalidTenantAttributeException;
use Modules\Tenancy\Domain\Exceptions\TenantCodeAlreadyExistsException;
use Modules\Tenancy\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenancy\Domain\Exceptions\UnknownUserException;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantContract $tenants,
        private readonly MembershipContract $memberships,
    ) {}

    public function index(): View
    {
        return view('tenancy::tenants.index', [
            'tenants' => $this->tenants->all(),
        ]);
    }

    public function create(): View
    {
        return view('tenancy::tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $tenant = $this->tenants->create($data['code'], $data['name']);
        } catch (TenantCodeAlreadyExistsException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        } catch (InvalidTenantAttributeException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy.tenants.edit', ['tenant' => $tenant->id])
            ->with('status', 'Tenant created.');
    }

    public function edit(int $tenant): View|RedirectResponse
    {
        $read = $this->tenants->findById($tenant);

        if ($read === null) {
            return redirect()
                ->route('tenancy.tenants.index')
                ->withErrors(['tenant' => "Tenant {$tenant} was not found."]);
        }

        return view('tenancy::tenants.edit', [
            'tenant' => $read,
            'members' => $this->memberships->membersOfTenant($read->id),
        ]);
    }

    public function update(Request $request, int $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->tenants->rename($tenant, $data['name']);
        } catch (TenantNotFoundException $e) {
            return redirect()->route('tenancy.tenants.index')->withErrors(['tenant' => $e->getMessage()]);
        } catch (InvalidTenantAttributeException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy.tenants.edit', ['tenant' => $tenant])
            ->with('status', 'Tenant updated.');
    }

    public function activate(int $tenant): RedirectResponse
    {
        try {
            $this->tenants->activate($tenant);
        } catch (TenantNotFoundException $e) {
            return redirect()->route('tenancy.tenants.index')->withErrors(['tenant' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy.tenants.edit', ['tenant' => $tenant])
            ->with('status', 'Tenant activated.');
    }

    public function deactivate(int $tenant): RedirectResponse
    {
        try {
            $this->tenants->deactivate($tenant);
        } catch (TenantNotFoundException $e) {
            return redirect()->route('tenancy.tenants.index')->withErrors(['tenant' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy.tenants.edit', ['tenant' => $tenant])
            ->with('status', 'Tenant deactivated.');
    }

    public function addMember(Request $request, int $tenant): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->memberships->add((int) $data['user_id'], $tenant);
        } catch (TenantNotFoundException $e) {
            return redirect()->route('tenancy.tenants.index')->withErrors(['tenant' => $e->getMessage()]);
        } catch (UnknownUserException $e) {
            return back()->withInput()->withErrors(['user_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy.tenants.edit', ['tenant' => $tenant])
            ->with('status', 'Member added.');
    }

    public function removeMember(int $tenant, int $userId): RedirectResponse
    {
        $this->memberships->remove($userId, $tenant);

        return redirect()
            ->route('tenancy.tenants.edit', ['tenant' => $tenant])
            ->with('status', 'Member removed.');
    }
}
