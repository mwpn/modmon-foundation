<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Identity\Domain\ReadModels\UserReadModel;

/**
 * Minimal in-memory Identity double for RBAC Phase 1 tests.
 *
 * Only the public identity contract (`UserQueryContract` /
 * `UserReadModel`) is touched — RBAC must never depend on Identity
 * internals, and these tests must not require copying the Identity
 * module into the Foundation authoring host.
 */
class FakeUserQuery implements UserQueryContract
{
    /** @var array<string, UserReadModel> */
    private array $users = [];

    public function seed(int $id): void
    {
        $this->users[(string) $id] = new UserReadModel(
            id: $id,
            name: "User {$id}",
            email: "user{$id}@example.com",
        );
    }

    public function findById(int $id): ?UserReadModel
    {
        return $this->users[(string) $id] ?? null;
    }

    public function findByEmail(string $email): ?UserReadModel
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function exists(string $email): bool
    {
        return isset($this->users[$email]);
    }
}
