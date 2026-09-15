<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Application\Services;

use InvalidArgumentException;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Domain\DTOs\TenantLandingData;
use Modules\TenantLanding\Domain\DTOs\TenantLandingRead;
use Modules\TenantLanding\Domain\Models\TenantLandingPage;

/**
 * Minimal per-tenant landing config. Validates tenant via TenantContract only.
 */
final class TenantLandingService implements TenantLandingContract
{
    public function __construct(
        private readonly TenantContract $tenants,
    ) {}

    public function forTenant(int $tenantId): TenantLandingRead
    {
        $this->assertKnownTenant($tenantId);
        $row = $this->findRow($tenantId);

        if ($row === null) {
            return new TenantLandingRead(
                tenantId: $tenantId,
                headline: null,
                summary: null,
                showLoginCta: true,
                configured: false,
            );
        }

        return $this->toRead($row);
    }

    public function update(int $tenantId, TenantLandingData $data): TenantLandingRead
    {
        $this->assertActiveTenant($tenantId);

        $row = $this->findRow($tenantId) ?? new TenantLandingPage(['tenant_id' => $tenantId]);
        $row->tenant_id = $tenantId;
        $row->headline = $this->nullableTrim($data->headline);
        $row->summary = $this->nullableTrim($data->summary);
        $row->show_login_cta = $data->showLoginCta;
        $row->save();

        return $this->toRead($row);
    }

    public function allConfigured(): array
    {
        return TenantLandingPage::query()
            ->orderBy('tenant_id')
            ->get()
            ->map(fn (TenantLandingPage $row) => $this->toRead($row))
            ->all();
    }

    private function findRow(int $tenantId): ?TenantLandingPage
    {
        return TenantLandingPage::query()->where('tenant_id', $tenantId)->first();
    }

    private function toRead(TenantLandingPage $row): TenantLandingRead
    {
        return new TenantLandingRead(
            tenantId: (int) $row->tenant_id,
            headline: $row->headline,
            summary: $row->summary,
            showLoginCta: (bool) $row->show_login_cta,
            configured: true,
        );
    }

    private function assertKnownTenant(int $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw new InvalidArgumentException("Tenant '{$tenantId}' was not found.");
        }
    }

    private function assertActiveTenant(int $tenantId): void
    {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null || ! $tenant->active) {
            throw new InvalidArgumentException("Tenant '{$tenantId}' was not found or is inactive.");
        }
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
