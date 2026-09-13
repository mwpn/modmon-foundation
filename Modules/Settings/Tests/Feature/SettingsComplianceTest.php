<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Domain\Contracts\RuntimeSettingsContract;

/**
 * Phase 2 — portability / compliance proof (no new features).
 *
 * Models a clean-host sequence on this Foundation authoring host:
 * discovered by copy → doctor → install owns migration → contract →
 * disable/enable preserves rows → fail-closed migration → host sources
 * untouched.
 */
class SettingsComplianceTest extends SettingsTestCase
{
    /** @var list<string> */
    private array $watchedHostFiles = [
        'bootstrap/app.php',
        'routes/web.php',
        'config/app.php',
        'config/auth.php',
        'composer.json',
        'composer.lock',
        'app/Foundation/FoundationServiceProvider.php',
        'app/Foundation/Runtime/ModuleManager.php',
    ];

    public function test_portability_and_lifecycle_compliance(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        // Discovered by copy only — not installed, no owned table, no capability.
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('settings'));
        $this->assertFalse(Schema::hasTable('settings_entries'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertFalse(
            app()->bound(RuntimeSettingsContract::class),
            'Contract must not be bound before Settings is installed/enabled',
        );

        $this->artisan('module:doctor', ['code' => 'settings'])->assertSuccessful();

        $install = app(\App\Foundation\Runtime\ModuleManager::class)->install('settings');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
            'Explicit install must own and report Settings migrations',
        );

        $this->assertTrue(Schema::hasTable('settings_entries'));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('settings'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertTrue(app()->bound(RuntimeSettingsContract::class));

        $settings = app(RuntimeSettingsContract::class);
        $settings->set('settings.app.name', 'Compliance');
        $settings->set('billing.tax.rate', 11);
        $this->assertSame('Compliance', $settings->get('settings.app.name'));

        $manager = app(\App\Foundation\Runtime\ModuleManager::class);
        $disable = $manager->disable('settings');
        $this->assertTrue($disable['success'], implode(' | ', $disable['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('settings'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertTrue(Schema::hasTable('settings_entries'));
        $this->assertDatabaseHas('settings_entries', ['key' => 'settings.app.name']);
        $this->assertDatabaseHas('settings_entries', ['key' => 'billing.tax.rate']);

        // Settings Phase 1 contributes no Experience surfaces; capability
        // removal is the runtime contribution cleared on disable.
        $this->assertFalse(
            is_subclass_of(
                \Modules\Settings\SettingsServiceProvider::class,
                \App\Foundation\SDK\Contributions\ContributesRoutes::class,
            ),
        );
        $this->assertFalse(
            is_subclass_of(
                \Modules\Settings\SettingsServiceProvider::class,
                \App\Foundation\SDK\Contributions\ContributesNavigation::class,
            ),
        );
        $this->assertFalse(
            is_subclass_of(
                \Modules\Settings\SettingsServiceProvider::class,
                \App\Foundation\SDK\Contributions\ContributesPermissions::class,
            ),
        );

        $enable = $manager->enable('settings');
        $this->assertTrue($enable['success'], implode(' | ', $enable['messages']));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertSame('Compliance', app(RuntimeSettingsContract::class)->get('settings.app.name'));
        $this->assertSame(11, app(RuntimeSettingsContract::class)->get('billing.tax.rate'));

        $this->assertSame(
            $hashesBefore,
            $this->hashWatchedHostFiles(),
            'Install/disable/enable must not modify unrelated host source files',
        );
    }

    public function test_migration_failure_is_fail_closed(): void
    {
        Schema::create('settings_entries', function ($table) {
            $table->id();
            $table->string('key')->unique();
        });

        $hashesBefore = $this->hashWatchedHostFiles();

        $result = app(\App\Foundation\Runtime\ModuleManager::class)->install('settings');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('settings'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertFalse(app()->bound(RuntimeSettingsContract::class));
        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    public function test_settings_requires_no_identity_or_rbac(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(base_path('Modules/Settings/module.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame([], $manifest['requires']['capabilities'] ?? []);
        $this->assertSame(['settings.runtime'], $manifest['provides']);

        $this->installSettings();
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('identity.user'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('authorization.permission'));
    }

    /**
     * @return array<string, string>
     */
    private function hashWatchedHostFiles(): array
    {
        $hashes = [];

        foreach ($this->watchedHostFiles as $relative) {
            $path = base_path($relative);
            $this->assertFileExists($path);
            $hashes[$relative] = hash_file('sha256', $path);
        }

        return $hashes;
    }
}
