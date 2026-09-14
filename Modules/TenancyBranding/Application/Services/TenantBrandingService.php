<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Application\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingRead;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideRead;
use Modules\TenancyBranding\Domain\Models\TenantBrandingOverride;

/**
 * Tenant branding overrides composed over BrandingContract.
 * Does not bind or replace branding.application.
 */
final class TenantBrandingService implements TenantBrandingContract
{
    public const ASSET_DIRECTORY = 'tenancy-branding';

    private const COLOR_PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    public function __construct(
        private readonly BrandingContract $branding,
        private readonly TenantContract $tenants,
        private readonly TenantContextContract $context,
    ) {}

    public function forTenant(int $tenantId): BrandingRead
    {
        $this->assertActiveTenant($tenantId);

        return $this->merge($this->branding->current(), $this->findRow($tenantId));
    }

    public function forCurrentUser(int $userId): BrandingRead
    {
        $tenantId = $this->context->currentTenantId($userId);

        if ($tenantId === null) {
            return $this->branding->current();
        }

        return $this->forTenant($tenantId);
    }

    public function overrideFor(int $tenantId): TenantBrandingOverrideRead
    {
        $this->assertActiveTenant($tenantId);
        $row = $this->findRow($tenantId);

        if ($row === null) {
            return new TenantBrandingOverrideRead(
                tenantId: $tenantId,
                exists: false,
                name: null,
                logoUrl: null,
                logoDarkUrl: null,
                faviconUrl: null,
                primaryColor: null,
                accentColor: null,
                loginTitle: null,
                loginSubtitle: null,
            );
        }

        return new TenantBrandingOverrideRead(
            tenantId: $tenantId,
            exists: true,
            name: $row->name,
            logoUrl: $this->publicUrl($row->logo_path),
            logoDarkUrl: $this->publicUrl($row->logo_dark_path),
            faviconUrl: $this->publicUrl($row->favicon_path),
            primaryColor: $row->primary_color,
            accentColor: $row->accent_color,
            loginTitle: $row->login_title,
            loginSubtitle: $row->login_subtitle,
        );
    }

    public function updateOverride(int $tenantId, TenantBrandingOverrideData $data): BrandingRead
    {
        $this->assertActiveTenant($tenantId);

        $row = $this->findRow($tenantId) ?? new TenantBrandingOverride(['tenant_id' => $tenantId]);

        $row->tenant_id = $tenantId;
        $row->name = $this->nullableTrim($data->name);
        $row->primary_color = $this->normalizeColor($data->primaryColor);
        $row->accent_color = $this->normalizeColor($data->accentColor);
        $row->login_title = $this->nullableTrim($data->loginTitle);
        $row->login_subtitle = $this->nullableTrim($data->loginSubtitle);

        $row->logo_path = $this->resolveAssetPath(
            $tenantId,
            $row->logo_path,
            $data->logoPath,
            $data->clearLogo,
        );
        $row->logo_dark_path = $this->resolveAssetPath(
            $tenantId,
            $row->logo_dark_path,
            $data->logoDarkPath,
            $data->clearLogoDark,
        );
        $row->favicon_path = $this->resolveAssetPath(
            $tenantId,
            $row->favicon_path,
            $data->faviconPath,
            $data->clearFavicon,
        );

        $row->save();

        return $this->merge($this->branding->current(), $row);
    }

    public function clearOverride(int $tenantId): BrandingRead
    {
        $this->assertActiveTenant($tenantId);

        $row = $this->findRow($tenantId);
        if ($row !== null) {
            $this->deleteOwnedAsset($row->logo_path);
            $this->deleteOwnedAsset($row->logo_dark_path);
            $this->deleteOwnedAsset($row->favicon_path);
            $row->delete();
        }

        return $this->branding->current();
    }

    private function merge(BrandingRead $app, ?TenantBrandingOverride $row): BrandingRead
    {
        if ($row === null) {
            return $app;
        }

        $logoUrl = $row->logo_path !== null && $row->logo_path !== ''
            ? $this->publicUrl($row->logo_path)
            : $app->logoUrl;
        $logoDarkUrl = $row->logo_dark_path !== null && $row->logo_dark_path !== ''
            ? $this->publicUrl($row->logo_dark_path)
            : $app->logoDarkUrl;
        $faviconUrl = $row->favicon_path !== null && $row->favicon_path !== ''
            ? $this->publicUrl($row->favicon_path)
            : $app->faviconUrl;

        return new BrandingRead(
            name: $row->name ?? $app->name,
            logoUrl: $logoUrl,
            logoDarkUrl: $logoDarkUrl,
            faviconUrl: $faviconUrl,
            primaryColor: $row->primary_color ?? $app->primaryColor,
            accentColor: $row->accent_color ?? $app->accentColor,
            loginTitle: $row->login_title ?? $app->loginTitle,
            loginSubtitle: $row->login_subtitle ?? $app->loginSubtitle,
            configured: $app->configured || true,
        );
    }

    private function assertActiveTenant(int $tenantId): void
    {
        $tenant = $this->tenants->findById($tenantId);

        if ($tenant === null || ! $tenant->active) {
            throw new InvalidArgumentException(
                "Tenant '{$tenantId}' was not found or is inactive.",
            );
        }
    }

    private function findRow(int $tenantId): ?TenantBrandingOverride
    {
        return TenantBrandingOverride::query()->where('tenant_id', $tenantId)->first();
    }

    private function publicUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function resolveAssetPath(
        int $tenantId,
        ?string $current,
        ?string $incoming,
        bool $clear,
    ): ?string {
        if ($clear) {
            $this->deleteOwnedAsset($current);

            return null;
        }

        if ($incoming !== null && $incoming !== '') {
            $this->assertOwnedAssetPath($tenantId, $incoming);
            if ($current !== null && $current !== $incoming) {
                $this->deleteOwnedAsset($current);
            }

            return $incoming;
        }

        return $current;
    }

    private function assertOwnedAssetPath(int $tenantId, string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        $prefix = self::ASSET_DIRECTORY.'/'.$tenantId.'/';

        if (str_contains($normalized, '..') || ! str_starts_with($normalized, $prefix)) {
            throw new InvalidArgumentException(
                'Tenant branding asset paths must stay under '.$prefix,
            );
        }
    }

    private function deleteOwnedAsset(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $normalized = str_replace('\\', '/', $path);
        if (! str_starts_with($normalized, self::ASSET_DIRECTORY.'/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function normalizeColor(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        $color = trim($color);
        if ($color === '') {
            return null;
        }

        if (! preg_match(self::COLOR_PATTERN, $color)) {
            throw new InvalidArgumentException(
                'Branding colors must be hex values (#RGB or #RRGGBB).',
            );
        }

        return strtoupper($color);
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
