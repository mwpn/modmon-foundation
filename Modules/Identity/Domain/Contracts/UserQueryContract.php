<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Contracts;

use Modules\Identity\Domain\ReadModels\UserReadModel;

/**
 * Read-only user lookup contract for other modules.
 *
 * The single public boundary for user data. No Eloquent model,
 * password, or token ever crosses the module boundary.
 */
interface UserQueryContract
{
    public function findById(int $id): ?UserReadModel;

    public function findByEmail(string $email): ?UserReadModel;

    public function exists(string $email): bool;
}
