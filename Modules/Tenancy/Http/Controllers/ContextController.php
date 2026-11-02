<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\Exceptions\InvalidTenantContextException;
use Modules\Tenancy\Domain\Exceptions\UnknownUserException;

/**
 * Admin context switch/clear by explicit user ID (not RBAC, not auth middleware).
 */
final class ContextController extends Controller
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly TenantContract $tenants,
        private readonly MembershipContract $memberships,
    ) {}

    public function show(Request $request): View
    {
        $userId = $request->integer('user_id') ?: null;
        $current = null;
        $memberships = [];

        if ($userId !== null && $userId > 0) {
            $current = $this->context->current($userId);
            $memberships = $this->memberships->membershipsForUser($userId);
        }

        return view('tenancy::context.show', [
            'userId' => $userId,
            'current' => $current,
            'memberships' => $memberships,
            'tenants' => $this->tenants->allActive(),
        ]);
    }

    public function set(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'tenant_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->context->setCurrent((int) $data['user_id'], (int) $data['tenant_id']);
        } catch (UnknownUserException|InvalidTenantContextException $e) {
            return back()->withInput()->withErrors(['tenant_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy.context.show', ['user_id' => $data['user_id']])
            ->with('status', 'Current tenant set.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $this->context->clear((int) $data['user_id']);

        return redirect()
            ->route('tenancy.context.show', ['user_id' => $data['user_id']])
            ->with('status', 'Current tenant cleared.');
    }
}
