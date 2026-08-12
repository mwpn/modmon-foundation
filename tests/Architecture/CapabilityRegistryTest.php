<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Runtime\CapabilityRegistry;
use PHPUnit\Framework\TestCase;

class CapabilityRegistryTest extends TestCase
{
    private CapabilityRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CapabilityRegistry();
    }

    public function test_register_and_check_capability(): void
    {
        $this->registry->registerProvider('example', ['example.demo']);
        $this->assertTrue($this->registry->has('example.demo'));
        $this->assertFalse($this->registry->has('example.other'));
    }

    public function test_unregister_removes_capabilities(): void
    {
        $this->registry->registerProvider('example', ['example.demo', 'example.other']);
        $this->registry->unregisterProvider('example');
        $this->assertFalse($this->registry->has('example.demo'));
        $this->assertFalse($this->registry->has('example.other'));
    }

    public function test_provider_returns_module_code(): void
    {
        $this->registry->registerProvider('example', ['example.demo']);
        $this->assertEquals('example', $this->registry->provider('example.demo'));
        $this->assertNull($this->registry->provider('nonexistent'));
    }

    public function test_missing_returns_unsatisfied_capabilities(): void
    {
        $this->registry->registerProvider('example', ['example.demo']);
        $missing = $this->registry->missing(['example.demo', 'identity.user']);
        $this->assertEquals(['identity.user'], $missing);
    }

    public function test_available_lists_all_capabilities(): void
    {
        $this->registry->registerProvider('mod-a', ['a.one', 'a.two']);
        $this->registry->registerProvider('mod-b', ['b.one']);
        $available = $this->registry->available();
        $this->assertCount(3, $available);
        $this->assertContains('a.one', $available);
        $this->assertContains('b.one', $available);
    }

    public function test_unregister_only_affects_target_module(): void
    {
        $this->registry->registerProvider('mod-a', ['a.one']);
        $this->registry->registerProvider('mod-b', ['b.one']);
        $this->registry->unregisterProvider('mod-a');
        $this->assertFalse($this->registry->has('a.one'));
        $this->assertTrue($this->registry->has('b.one'));
    }
}
