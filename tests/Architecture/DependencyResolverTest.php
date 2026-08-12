<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Runtime\DependencyResolver;
use App\Foundation\SDK\ModuleManifest;
use PHPUnit\Framework\TestCase;

class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DependencyResolver();
    }

    public function test_modules_with_no_dependencies_resolve(): void
    {
        $manifests = [
            'a' => $this->manifest('a', [], ['a.cap']),
            'b' => $this->manifest('b', [], ['b.cap']),
        ];

        $result = $this->resolver->resolve($manifests);
        $this->assertEmpty($result['errors']);
        $this->assertCount(2, $result['order']);
    }

    public function test_modules_resolve_in_dependency_order(): void
    {
        $manifests = [
            'consumer' => $this->manifest('consumer', ['provider.cap'], []),
            'provider' => $this->manifest('provider', [], ['provider.cap']),
        ];

        $result = $this->resolver->resolve($manifests);
        $this->assertEmpty($result['errors']);
        $providerIdx = array_search('provider', $result['order']);
        $consumerIdx = array_search('consumer', $result['order']);
        $this->assertLessThan($consumerIdx, $providerIdx);
    }

    public function test_unresolvable_dependency_produces_error(): void
    {
        $manifests = [
            'consumer' => $this->manifest('consumer', ['nonexistent.cap'], []),
        ];

        $result = $this->resolver->resolve($manifests);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('nonexistent.cap', $result['errors'][0]);
    }

    public function test_circular_dependency_detected(): void
    {
        $manifests = [
            'a' => $this->manifest('a', ['b.cap'], ['a.cap']),
            'b' => $this->manifest('b', ['a.cap'], ['b.cap']),
        ];

        $result = $this->resolver->resolve($manifests);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_can_enable_checks_required_capabilities(): void
    {
        $target  = $this->manifest('consumer', ['identity.user'], []);
        $enabled = [
            'identity' => $this->manifest('identity', [], ['identity.user']),
        ];

        $problems = $this->resolver->canEnable($target, $enabled);
        $this->assertEmpty($problems);
    }

    public function test_can_enable_fails_with_missing_capabilities(): void
    {
        $target  = $this->manifest('consumer', ['identity.user'], []);
        $enabled = [];

        $problems = $this->resolver->canEnable($target, $enabled);
        $this->assertNotEmpty($problems);
    }

    public function test_can_disable_detects_dependent_modules(): void
    {
        $identity = $this->manifest('identity', [], ['identity.user']);
        $consumer = $this->manifest('consumer', ['identity.user'], []);

        $enabled = [
            'identity' => $identity,
            'consumer' => $consumer,
        ];

        $problems = $this->resolver->canDisable($identity, $enabled);
        $this->assertNotEmpty($problems);
    }

    public function test_can_disable_allows_when_no_dependents(): void
    {
        $identity = $this->manifest('identity', [], ['identity.user']);
        $standalone = $this->manifest('standalone', [], ['other.cap']);

        $enabled = [
            'identity' => $identity,
            'standalone' => $standalone,
        ];

        $problems = $this->resolver->canDisable($standalone, $enabled);
        $this->assertEmpty($problems);
    }

    private function manifest(string $code, array $requires = [], array $provides = []): ModuleManifest
    {
        return new ModuleManifest(
            name: ucfirst($code),
            code: $code,
            version: '1.0.0',
            type: 'business',
            provider: "Modules\\{$code}\\Provider",
            compatibility: [],
            requires: $requires,
            provides: $provides,
        );
    }
}
