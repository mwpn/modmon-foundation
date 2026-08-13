<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Tests\TestCase;

class IdentityUserCreateCommandTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::tearDown();
    }

    private function installIdentity(): void
    {
        $result = app(ModuleManager::class)->install('identity');

        if (! $result['success']) {
            $this->fail('Identity module could not be installed: '.implode(' | ', $result['messages']));
        }
    }

    public function test_command_creates_user_with_hashed_password_and_never_prints_secret(): void
    {
        $this->installIdentity();

        $this->artisan('identity:user:create', [
            '--name' => 'Bootstrap User',
            '--email' => 'bootstrap@example.com',
            '--password' => 'secret-password',
        ])
            ->expectsOutputToContain("User 'bootstrap@example.com' created successfully.")
            ->doesntExpectOutputToContain('secret-password')
            ->assertSuccessful();

        $output = Artisan::output();
        $user = User::query()->where('email', 'bootstrap@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Bootstrap User', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertStringNotContainsString($user->password, $output);
        $this->assertStringNotContainsString('secret-password', $output);
    }

    public function test_command_rejects_duplicate_email(): void
    {
        $this->installIdentity();

        User::query()->create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'secret-password',
        ]);

        $this->artisan('identity:user:create', [
            '--name' => 'Duplicate',
            '--email' => 'existing@example.com',
            '--password' => 'secret-password',
        ])->assertExitCode(1);

        $this->assertSame(1, User::query()->where('email', 'existing@example.com')->count());
    }

    public function test_command_rejects_missing_options(): void
    {
        $this->installIdentity();

        $this->artisan('identity:user:create', [
            '--name' => 'No Email',
        ])->assertExitCode(1);

        $this->assertSame(0, User::query()->count());
    }

    public function test_command_rejects_invalid_email(): void
    {
        $this->installIdentity();

        $this->artisan('identity:user:create', [
            '--name' => 'Bad Email',
            '--email' => 'not-an-email',
            '--password' => 'secret-password',
        ])->assertExitCode(1);

        $this->assertSame(0, User::query()->count());
    }

    public function test_command_rejects_short_password(): void
    {
        $this->installIdentity();

        $this->artisan('identity:user:create', [
            '--name' => 'Short Password',
            '--email' => 'short@example.com',
            '--password' => 'short',
        ])->assertExitCode(1);

        $this->assertSame(0, User::query()->count());
    }

    public function test_command_does_not_exist_when_identity_is_not_enabled(): void
    {
        // Fresh process with Identity discovered but not installed/enabled:
        // the command must not be contributed.
        try {
            Artisan::call('identity:user:create', [
                '--name' => 'Should Fail',
                '--email' => 'fail@example.com',
                '--password' => 'secret-password',
            ]);
            $this->fail('identity:user:create must not exist when Identity is not enabled');
        } catch (CommandNotFoundException $e) {
            $this->assertStringContainsString('identity:user:create', $e->getMessage());
        }

        $this->assertSame(0, User::query()->count());
    }

    public function test_command_creates_no_roles_or_permissions(): void
    {
        $this->installIdentity();

        $this->artisan('identity:user:create', [
            '--name' => 'Plain User',
            '--email' => 'plain@example.com',
            '--password' => 'secret-password',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'plain@example.com')->first();
        $attributes = array_keys($user->getAttributes());

        $this->assertNotContains('role', $attributes);
        $this->assertNotContains('roles', $attributes);
        $this->assertNotContains('is_admin', $attributes);
        $this->assertFalse(method_exists($user, 'assignRole'));
        $this->assertFalse(method_exists($user, 'givePermissionTo'));
    }
}
