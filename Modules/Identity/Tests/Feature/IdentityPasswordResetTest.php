<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Identity\Models\User;
use Modules\Identity\Notifications\ResetPasswordNotification;
use Tests\TestCase;

class IdentityPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private string $modulesJsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesJsonPath = storage_path('app/modules.json');
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);

        $result = app(ModuleManager::class)->install('identity');

        if (! $result['success']) {
            $this->fail('Identity module could not be installed: '.implode(' | ', $result['messages']));
        }

        Notification::fake();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::tearDown();
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'old-password',
        ]);
    }

    public function test_forgot_password_form_renders(): void
    {
        $this->get(route('identity.password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?');
    }

    public function test_send_reset_link_sends_notification_with_identity_url(): void
    {
        $user = $this->createUser();

        $response = $this->post(route('identity.password.email'), [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);
            $url = $mail->actionUrl;

            $this->assertNotNull($url);
            $this->assertSame(
                route('identity.password.reset', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ]),
                $url,
            );

            return true;
        });
    }

    public function test_send_reset_link_does_not_send_for_unknown_email(): void
    {
        $response = $this->post(route('identity.password.email'), [
            'email' => 'missing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_reset_form_renders(): void
    {
        $this->get(route('identity.password.reset', ['token' => 'fake-token']))
            ->assertOk()
            ->assertSee('Reset your password');
    }

    public function test_reset_password_updates_hash(): void
    {
        $user = $this->createUser();

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('identity.password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertRedirect(route('identity.login'));

        $user->refresh();

        $this->assertTrue(
            Hash::check('new-secret-password', $user->password),
            'Password hash must be updated after reset',
        );
        $this->assertFalse(
            Hash::check('old-password', $user->password),
            'Old password must no longer validate',
        );
    }

    public function test_reset_password_requires_confirmation(): void
    {
        $user = $this->createUser();
        $token = Password::broker()->createToken($user);

        $response = $this->post(route('identity.password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        $this->createUser();

        $response = $this->post(route('identity.password.update'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertSessionHasErrors('email');

        $user = User::query()->where('email', 'user@example.com')->first();

        $this->assertFalse(
            Hash::check('new-secret-password', $user->password),
            'Password must not change when the token is invalid',
        );
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $user = $this->createUser();
        $token = Password::broker()->createToken($user);

        $this->post(route('identity.password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertRedirect(route('identity.login'));

        $response = $this->post(route('identity.password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'another-secret-password',
            'password_confirmation' => 'another-secret-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('another-secret-password', $user->fresh()->password));
    }
}
