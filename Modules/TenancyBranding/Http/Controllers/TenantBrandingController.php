<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenancyBranding\Application\Services\TenantBrandingService;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData;

final class TenantBrandingController extends Controller
{
    public function __construct(
        private readonly TenantBrandingContract $tenantBranding,
        private readonly TenantContract $tenants,
    ) {}

    public function index(): View
    {
        return view('tenancy-branding::index', [
            'tenants' => $this->tenants->allActive(),
        ]);
    }

    public function edit(int $tenantId): View|RedirectResponse
    {
        try {
            $override = $this->tenantBranding->overrideFor($tenantId);
            $effective = $this->tenantBranding->forTenant($tenantId);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('tenancy-branding.index')
                ->withErrors(['tenant' => $e->getMessage()]);
        }

        $tenant = $this->tenants->findById($tenantId);

        return view('tenancy-branding::edit', [
            'tenant' => $tenant,
            'override' => $override,
            'effective' => $effective,
        ]);
    }

    public function update(Request $request, int $tenantId): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'login_title' => ['nullable', 'string', 'max:255'],
            'login_subtitle' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico,jpg,jpeg,gif,webp', 'max:512'],
            'clear_logo' => ['sometimes', 'boolean'],
            'clear_logo_dark' => ['sometimes', 'boolean'],
            'clear_favicon' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->tenantBranding->updateOverride($tenantId, new TenantBrandingOverrideData(
                name: $data['name'] ?? null,
                logoPath: $this->storeUpload($request->file('logo'), $tenantId, 'logo'),
                logoDarkPath: $this->storeUpload($request->file('logo_dark'), $tenantId, 'logo-dark'),
                faviconPath: $this->storeUpload($request->file('favicon'), $tenantId, 'favicon'),
                primaryColor: $data['primary_color'] ?? null,
                accentColor: $data['accent_color'] ?? null,
                loginTitle: $data['login_title'] ?? null,
                loginSubtitle: $data['login_subtitle'] ?? null,
                clearLogo: $request->boolean('clear_logo'),
                clearLogoDark: $request->boolean('clear_logo_dark'),
                clearFavicon: $request->boolean('clear_favicon'),
            ));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['tenant' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy-branding.edit', ['tenantId' => $tenantId])
            ->with('status', 'Tenant branding override saved.');
    }

    public function destroy(int $tenantId): RedirectResponse
    {
        try {
            $this->tenantBranding->clearOverride($tenantId);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('tenancy-branding.index')
                ->withErrors(['tenant' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenancy-branding.edit', ['tenantId' => $tenantId])
            ->with('status', 'Tenant branding override cleared.');
    }

    private function storeUpload(?UploadedFile $file, int $tenantId, string $basename): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $directory = TenantBrandingService::ASSET_DIRECTORY.'/'.$tenantId;
        Storage::disk('public')->makeDirectory($directory);

        $path = $file->storeAs($directory, $basename.'.'.$extension, 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                $basename === 'logo-dark' ? 'logo_dark' : $basename => 'Failed to store uploaded tenant branding asset.',
            ]);
        }

        return $path;
    }
}
