<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Application\Services\DatabaseRuntimeSettings;
use Modules\Settings\Domain\Contracts\RuntimeSettingsContract;

/**
 * Explicit module:install owns Settings migrations and capability.
 */
class SettingsInstallTest extends SettingsTestCase
{
    public function test_settings_is_discovered_before_install(): void
    {
        $registrar = app(ModuleRegistrarContract::class);

        $this->assertNull($registrar->getState('settings'));
        $this->assertFalse(Schema::hasTable('settings_entries'));
    }

    public function test_install_creates_settings_entries_table(): void
    {
        $this->installSettings();

        $this->assertTrue(Schema::hasTable('settings_entries'));
        $this->assertTrue(Schema::hasColumns('settings_entries', ['id', 'key', 'value', 'created_at', 'updated_at']));
    }

    public function test_install_marks_enabled_and_registers_capability(): void
    {
        $this->installSettings();

        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('settings'),
        );
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('settings.runtime'));
    }

    public function test_runtime_settings_contract_is_bound_after_install(): void
    {
        $this->installSettings();

        $store = app(RuntimeSettingsContract::class);

        $this->assertInstanceOf(DatabaseRuntimeSettings::class, $store);
    }

    public function test_copy_alone_does_not_create_table(): void
    {
        // Module files already on disk (discovered). Without install,
        // owned tables must not exist.
        $this->assertFalse(Schema::hasTable('settings_entries'));
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('settings'));
    }
}
