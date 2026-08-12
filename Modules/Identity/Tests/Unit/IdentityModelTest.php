<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Unit;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Identity\Models\User;
use PHPUnit\Framework\TestCase;

class IdentityModelTest extends TestCase
{
    public function test_model_extends_authenticatable(): void
    {
        $this->assertTrue(is_subclass_of(User::class, Authenticatable::class));
    }

    public function test_model_uses_notifiable(): void
    {
        $this->assertContains(Notifiable::class, class_uses_recursive(User::class));
    }

    public function test_model_does_not_extend_host_user(): void
    {
        $this->assertNotSame('App\Models\User', get_parent_class(User::class));
    }

    public function test_fillable_attributes(): void
    {
        $user = new User;

        $this->assertSame(['name', 'email', 'password'], $user->getFillable());
    }

    public function test_hidden_attributes(): void
    {
        $user = new User;

        $this->assertSame(['password', 'remember_token'], $user->getHidden());
    }

    public function test_password_cast_is_hashed(): void
    {
        $this->assertSame('hashed', (new User)->getCasts()['password']);
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $this->assertSame('datetime', (new User)->getCasts()['email_verified_at']);
    }
}
