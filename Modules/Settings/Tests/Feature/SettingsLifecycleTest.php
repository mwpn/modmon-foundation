<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Domain\Contracts\RuntimeSettingsContract;

/**
 * Disable/enable preserves stored settings; capability follows lifecycle.
 */
class SettingsLifecycleTest extends SettingsTestCase
{
    public function test_disable_preserves_rows_and_unregisters_capability(): void
    {
        $manager = $this->installSettings();
        $settings = app(RuntimeSettingsContract::class);
        $settings->set('settings.app.name', 'Keep Me');
        $settings->set('billing.tax.rate', 11);

        $result = $manager->disable('settings');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('settings'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('settings.runtime'));
        $this->assertTrue(Schema::hasTable('settings_entries'));
        $this->assertDatabaseHas('settings_entries', ['key' => 'settings.app.name']);
        $this->assertDatabaseHas('settings_entries', ['key' => 'billing.tax.rate']);
    }

    public function test_enable_restores_capability_and_contract_with_preserved_data(): void
    {
        $manager = $this->installSettings();
        app(RuntimeSettingsContract::class)->set('settings.app.name', 'Survivor');

        $this->assertTrue($manager->disable('settings')['success']);
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('settings.runtime'));

        $result = $manager->enable('settings');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('settings'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('settings.runtime'));

        $settings = app(RuntimeSettingsContract::class);
        $this->assertSame('Survivor', $settings->get('settings.app.name'));
        $this->assertTrue($settings->has('settings.app.name'));
    }

    public function test_disable_does_not_drop_settings_table(): void
    {
        $manager = $this->installSettings();
        $columnsBefore = Schema::getColumnListing('settings_entries');

        $this->assertTrue($manager->disable('settings')['success']);

        $this->assertTrue(Schema::hasTable('settings_entries'));
        $this->assertSame($columnsBefore, Schema::getColumnListing('settings_entries'));
    }

    public function test_same_process_disable_unregisters_capability_but_binding_may_linger(): void
    {
        $manager = $this->installSettings();
        app(RuntimeSettingsContract::class)->set('settings.app.name', 'Lingering');

        $this->assertTrue($manager->disable('settings')['success']);
        $this->assertFalse(
            app(CapabilityRegistryContract::class)->has('settings.runtime'),
            'Capability must be unavailable immediately after disable',
        );

        // Foundation does not unload already-registered providers in the
        // same process (same semantics as Identity auth wiring). The
        // container binding may still resolve; consumers must gate on
        // settings.runtime, not on container resolution alone.
        $this->assertTrue(
            app()->bound(RuntimeSettingsContract::class),
            'Same-process disable does not forget the provider binding',
        );

        $resolved = app(RuntimeSettingsContract::class);
        $this->assertSame('Lingering', $resolved->get('settings.app.name'));
    }
}
