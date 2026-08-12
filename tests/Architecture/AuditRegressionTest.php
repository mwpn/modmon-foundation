<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Runtime\CapabilityRegistry;
use App\Foundation\Runtime\ManifestValidator;
use App\Foundation\Runtime\ModuleDiscovery;
use App\Foundation\SDK\ModuleManifest;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for defects found during the Foundation v1 audit.
 */
class AuditRegressionTest extends TestCase
{
    /**
     * Capability collision: registering a capability already provided by
     * another module must be detectable via provider() before overwrite.
     *
     * Regression: CapabilityRegistry silently overwrote duplicate capabilities.
     */
    public function test_capability_registry_overwrites_are_detectable(): void
    {
        $registry = new CapabilityRegistry();

        $registry->registerProvider('module-a', ['shared.capability']);
        $this->assertEquals('module-a', $registry->provider('shared.capability'));

        // Calling code (ModuleManager) now checks provider() before registering.
        // Verify the registry still reports the original provider before overwrite.
        $existingProvider = $registry->provider('shared.capability');
        $this->assertNotNull($existingProvider, 'Provider should be detectable before a second registerProvider call');
        $this->assertEquals('module-a', $existingProvider);
    }

    /**
     * ManifestValidator must reject manifests with a provider FQN that has
     * no namespace separator (backslash). Plain class names are not valid.
     */
    public function test_manifest_validator_rejects_provider_without_namespace(): void
    {
        $validator = new ManifestValidator();
        $data = [
            'schema' => 1,
            'name' => 'Bad Provider',
            'code' => 'bad-provider',
            'version' => '1.0.0',
            'type' => 'business',
            'provider' => 'NoNamespaceClass',
        ];

        $errors = $validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'provider')),
            'Should detect invalid provider without namespace',
        );
    }

    /**
     * ModuleManifest::fromArray handles missing optional fields gracefully.
     */
    public function test_module_manifest_from_array_with_minimal_data(): void
    {
        $data = [
            'name' => 'Minimal',
            'code' => 'minimal',
            'version' => '1.0.0',
            'provider' => 'Modules\\Minimal\\MinimalProvider',
        ];

        $manifest = ModuleManifest::fromArray($data, '/test');
        $this->assertEquals('business', $manifest->type);
        $this->assertEmpty($manifest->compatibility);
        $this->assertEmpty($manifest->requires);
        $this->assertEmpty($manifest->provides);
        $this->assertEquals(1, $manifest->schema);
    }

    /**
     * CapabilityRegistry::missing() returns values-indexed array (no gaps).
     */
    public function test_capability_missing_returns_reindexed_array(): void
    {
        $registry = new CapabilityRegistry();
        $registry->registerProvider('mod', ['first.cap']);

        $missing = $registry->missing(['first.cap', 'second.cap', 'third.cap']);
        $this->assertEquals(['second.cap', 'third.cap'], $missing);
        // Verify keys are sequential (0, 1), not (1, 2) from array_filter
        $this->assertSame(0, array_key_first($missing));
    }

    /**
     * Capability collision across two modules must be queryable.
     * If module-a provides "x" and module-b tries to also provide "x",
     * the caller should detect module-a owns it before calling registerProvider.
     */
    public function test_capability_collision_detectable_before_registration(): void
    {
        $registry = new CapabilityRegistry();
        $registry->registerProvider('module-a', ['feature.auth']);

        // Simulate what ModuleManager now does before registering
        $collision = false;
        foreach (['feature.auth', 'feature.billing'] as $cap) {
            if ($registry->provider($cap) !== null) {
                $collision = true;
                break;
            }
        }

        $this->assertTrue($collision, 'Collision with feature.auth should be detected');
    }
}
