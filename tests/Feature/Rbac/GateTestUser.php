<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * Minimal Authenticatable double for Gate integration tests.
 *
 * Only `getAuthIdentifier()` is meaningful — the RBAC Gate callback
 * derives the user id from it and validates the user through the
 * public `UserQueryContract`, never through this object or any
 * Identity/host model.
 */
final class GateTestUser implements Authenticatable
{
    use Authorizable;

    public function __construct(private readonly int|string $id)
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): ?string
    {
        return null;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken(#[\SensitiveParameter] $value): void
    {
        //
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
