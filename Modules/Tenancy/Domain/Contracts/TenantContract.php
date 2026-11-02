<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Contracts;

use Modules\Tenancy\Domain\DTOs\TenantRead;

/**
 * Public tenancy.tenant capability contract.
 *
 * Cross-module consumers read/manage tenants here only — never via
 * Tenancy Eloquent models or tenancy_* tables.
 *
 */
interface TenantContract
{
    public function findById(int $id): ?TenantRead;

    public function findByCode(string $code): ?TenantRead;

    public function exists(string $code): bool;

    /**
     * @return list<TenantRead>
     */
    public function all(): array;

    /**
     * @return list<TenantRead>
     */
    public function allActive(): array;

    public function create(string $code, string $name): TenantRead;

    public function rename(int $id, string $name): TenantRead;

    public function deactivate(int $id): void;

    public function activate(int $id): void;
}
