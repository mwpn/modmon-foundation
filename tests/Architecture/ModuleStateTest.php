<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\SDK\ModuleState;
use PHPUnit\Framework\TestCase;

class ModuleStateTest extends TestCase
{
    public function test_all_states_exist(): void
    {
        $this->assertCount(4, ModuleState::cases());
        $this->assertNotNull(ModuleState::Discovered);
        $this->assertNotNull(ModuleState::Installed);
        $this->assertNotNull(ModuleState::Enabled);
        $this->assertNotNull(ModuleState::Disabled);
    }

    public function test_from_string(): void
    {
        $this->assertEquals(ModuleState::Enabled, ModuleState::from('enabled'));
        $this->assertEquals(ModuleState::Disabled, ModuleState::from('disabled'));
    }

    public function test_values(): void
    {
        $this->assertEquals('discovered', ModuleState::Discovered->value);
        $this->assertEquals('installed', ModuleState::Installed->value);
        $this->assertEquals('enabled', ModuleState::Enabled->value);
        $this->assertEquals('disabled', ModuleState::Disabled->value);
    }
}
