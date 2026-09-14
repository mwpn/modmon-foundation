<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Services;

use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\DTOs\TenantRead;
use Modules\Tenancy\Domain\Exceptions\InvalidTenantAttributeException;
use Modules\Tenancy\Domain\Exceptions\TenantCodeAlreadyExistsException;
use Modules\Tenancy\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenancy\Domain\Models\Tenant;

/**
 * Database-backed TenantContract. Internal Eloquent never crosses the boundary.
 */
final class TenantService implements TenantContract
{
    public function findById(int $id): ?TenantRead
    {
        $tenant = Tenant::query()->find($id);

        return $tenant ? $this->toRead($tenant) : null;
    }

    public function findByCode(string $code): ?TenantRead
    {
        $code = $this->normalizeCode($code);
        $tenant = Tenant::query()->where('code', $code)->first();

        return $tenant ? $this->toRead($tenant) : null;
    }

    public function exists(string $code): bool
    {
        $code = $this->normalizeCode($code);

        return Tenant::query()->where('code', $code)->exists();
    }

    public function all(): array
    {
        return Tenant::query()
            ->orderBy('code')
            ->get()
            ->map(fn (Tenant $tenant) => $this->toRead($tenant))
            ->all();
    }

    public function allActive(): array
    {
        return Tenant::query()
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Tenant $tenant) => $this->toRead($tenant))
            ->all();
    }

    public function create(string $code, string $name): TenantRead
    {
        $code = $this->normalizeCode($code);
        $name = $this->normalizeName($name);

        if (Tenant::query()->where('code', $code)->exists()) {
            throw new TenantCodeAlreadyExistsException("Tenant code '{$code}' already exists.");
        }

        $tenant = Tenant::query()->create([
            'code' => $code,
            'name' => $name,
            'active' => true,
        ]);

        return $this->toRead($tenant);
    }

    public function rename(int $id, string $name): TenantRead
    {
        $tenant = Tenant::query()->find($id)
            ?? throw new TenantNotFoundException("Tenant {$id} not found.");

        $tenant->update(['name' => $this->normalizeName($name)]);

        return $this->toRead($tenant->fresh());
    }

    public function deactivate(int $id): void
    {
        $tenant = Tenant::query()->find($id)
            ?? throw new TenantNotFoundException("Tenant {$id} not found.");

        $tenant->update(['active' => false]);
    }

    public function activate(int $id): void
    {
        $tenant = Tenant::query()->find($id)
            ?? throw new TenantNotFoundException("Tenant {$id} not found.");

        $tenant->update(['active' => true]);
    }

    private function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));

        if ($code === '' || ! preg_match('/^[a-z][a-z0-9\-]*$/', $code)) {
            throw new InvalidTenantAttributeException(
                'Tenant code must be non-empty lowercase alphanumeric with optional hyphens, starting with a letter.',
            );
        }

        return $code;
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidTenantAttributeException('Tenant name must be non-empty.');
        }

        return $name;
    }

    private function toRead(Tenant $tenant): TenantRead
    {
        return new TenantRead(
            id: (int) $tenant->id,
            code: (string) $tenant->code,
            name: (string) $tenant->name,
            active: (bool) $tenant->active,
            createdAt: $tenant->created_at?->toDateTimeImmutable(),
        );
    }
}
