<?php

declare(strict_types=1);

namespace Modules\Branding\Application\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;
use Modules\Branding\Domain\DTOs\BrandingRead;
use Modules\Branding\Domain\Models\BrandingApplication;

/**
 * Database-backed BrandingContract.
 *
 * No seed: current() returns defaults until the first update() creates
 * the singleton row. Asset paths stay module-owned on the public disk.
 */
final class ApplicationBranding implements BrandingContract
{
    public const ASSET_DIRECTORY = 'branding';

    private const COLOR_PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    public function current(): BrandingRead
    {
        $row = BrandingApplication::query()->first();

        if ($row === null) {
            return $this->defaults();
        }

        return $this->toRead($row, configured: true);
    }

    public function update(BrandingData $data): BrandingRead
    {
        $name = trim($data->name);
        if ($name === '') {
            throw new InvalidArgumentException('Branding name must be a non-empty string.');
        }

        $primary = $this->normalizeColor($data->primaryColor);
        $accent = $this->normalizeColor($data->accentColor);

        $row = BrandingApplication::query()->first();

        if ($row === null) {
            $row = new BrandingApplication;
        }

        $row->name = $name;
        $row->primary_color = $primary;
        $row->accent_color = $accent;
        $row->login_title = $this->nullableTrim($data->loginTitle);
        $row->login_subtitle = $this->nullableTrim($data->loginSubtitle);

        $row->logo_path = $this->resolveAssetPath(
            $row->logo_path,
            $data->logoPath,
            $data->clearLogo,
        );
        $row->logo_dark_path = $this->resolveAssetPath(
            $row->logo_dark_path,
            $data->logoDarkPath,
            $data->clearLogoDark,
        );
        $row->favicon_path = $this->resolveAssetPath(
            $row->favicon_path,
            $data->faviconPath,
            $data->clearFavicon,
        );

        $row->save();

        return $this->toRead($row, configured: true);
    }

    private function defaults(): BrandingRead
    {
        return new BrandingRead(
            name: (string) config('app.name', 'ModMon'),
            logoUrl: null,
            logoDarkUrl: null,
            faviconUrl: null,
            primaryColor: null,
            accentColor: null,
            loginTitle: null,
            loginSubtitle: null,
            configured: false,
        );
    }

    private function toRead(BrandingApplication $row, bool $configured): BrandingRead
    {
        $name = is_string($row->name) && trim($row->name) !== ''
            ? trim($row->name)
            : (string) config('app.name', 'ModMon');

        return new BrandingRead(
            name: $name,
            logoUrl: $this->publicUrl($row->logo_path),
            logoDarkUrl: $this->publicUrl($row->logo_dark_path),
            faviconUrl: $this->publicUrl($row->favicon_path),
            primaryColor: $row->primary_color,
            accentColor: $row->accent_color,
            loginTitle: $row->login_title,
            loginSubtitle: $row->login_subtitle,
            configured: $configured,
        );
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

    private function resolveAssetPath(?string $current, ?string $incoming, bool $clear): ?string
    {
        if ($clear) {
            $this->deleteOwnedAsset($current);

            return null;
        }

        if ($incoming !== null && $incoming !== '') {
            $this->assertOwnedAssetPath($incoming);
            if ($current !== null && $current !== $incoming) {
                $this->deleteOwnedAsset($current);
            }

            return $incoming;
        }

        return $current;
    }

    private function assertOwnedAssetPath(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);

        if (str_contains($normalized, '..')
            || ! str_starts_with($normalized, self::ASSET_DIRECTORY.'/')) {
            throw new InvalidArgumentException(
                'Branding asset paths must stay under the module-owned branding/ directory.',
            );
        }
    }

    private function deleteOwnedAsset(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        try {
            $this->assertOwnedAssetPath($path);
        } catch (InvalidArgumentException) {
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
