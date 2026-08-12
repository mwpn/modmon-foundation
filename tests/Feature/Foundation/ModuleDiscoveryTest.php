<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\ModuleDiscovery;
use Tests\TestCase;

class ModuleDiscoveryTest extends TestCase
{
    public function test_discovers_example_module(): void
    {
        $discovery = app(ModuleDiscovery::class);
        $result = $discovery->discover();

        $this->assertArrayHasKey('example', $result['manifests']);
        $this->assertEquals('Example', $result['manifests']['example']->name);
        $this->assertEquals('1.0.0', $result['manifests']['example']->version);
    }

    public function test_example_manifest_has_correct_capabilities(): void
    {
        $discovery = app(ModuleDiscovery::class);
        $result = $discovery->discover();

        $manifest = $result['manifests']['example'];
        $this->assertContains('example.demo', $manifest->provides);
        $this->assertEmpty($manifest->requires);
    }

    public function test_example_manifest_has_correct_provider(): void
    {
        $discovery = app(ModuleDiscovery::class);
        $result = $discovery->discover();

        $manifest = $result['manifests']['example'];
        $this->assertEquals('Modules\\Example\\ExampleServiceProvider', $manifest->provider);
    }

    public function test_no_discovery_errors_for_example(): void
    {
        $discovery = app(ModuleDiscovery::class);
        $result = $discovery->discover();

        $this->assertArrayNotHasKey('Example', $result['errors']);
    }
}
