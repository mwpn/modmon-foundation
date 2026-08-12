<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Queries;

use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Identity\Domain\ReadModels\UserReadModel;
use Modules\Identity\Models\User;

/**
 * Eloquent-backed implementation of UserQueryContract.
 *
 * Maps the internal Eloquent model to the public UserReadModel; the
 * model itself never leaves the module.
 */
class EloquentUserQuery implements UserQueryContract
{
    public function findById(int $id): ?UserReadModel
    {
        $user = User::query()->find($id);

        return $user === null ? null : $this->toReadModel($user);
    }

    public function findByEmail(string $email): ?UserReadModel
    {
        $user = User::query()->where('email', $email)->first();

        return $user === null ? null : $this->toReadModel($user);
    }

    public function exists(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    private function toReadModel(User $user): UserReadModel
    {
        return new UserReadModel(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            emailVerifiedAt: $user->email_verified_at?->toImmutable(),
            createdAt: $user->created_at?->toImmutable(),
        );
    }
}
