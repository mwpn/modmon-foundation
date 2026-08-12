<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\SDK\ModuleManifest;
use PHPUnit\Framework\TestCase;

class ModuleManifestTest extends TestCase
{
    public function test_from_array_creates_manifest(): void
    {
        $manifest = ModuleManifest::fromArray([
            'schema' => 1,
            'name' => 'Test',
            'code' => 'test',
            'version' => '1.0.0',
            'type' => 'business',
            'provider' => 'Modules\\Test\\TestProvider',
            'compatibility' => [
                'php' => '^8.3',
                'laravel' => '^13.0',
                'foundation' => '^1.0',
            ],
            'requires' => ['capabilities' => ['identity.user']],
            'provides' => ['test.feature'],
        ], '/path/to/test');

        $this->assertEquals('test', $manifest->code);
        $this->assertEquals('^8.3', $manifest->phpConstraint());
        $this->assertEquals('^13.0', $manifest->laravelConstraint());
        $this->assertEquals('^1.0', $manifest->foundationConstraint());
        $this->assertEquals(['identity.user'], $manifest->requires);
        $this->assertEquals(['test.feature'], $manifest->provides);
        $this->assertEquals('/path/to/test', $manifest->path);
    }

    public function test_defaults_for_optional_fields(): void
    {
        $manifest = ModuleManifest::fromArray([
            'schema' => 1,
            'name' => 'Minimal',
            'code' => 'minimal',
            'version' => '0.1.0',
            'provider' => 'Modules\\Minimal\\Provider',
        ]);

        $this->assertEquals('business', $manifest->type);
        $this->assertEmpty($manifest->requires);
        $this->assertEmpty($manifest->provides);
        $this->assertNull($manifest->phpConstraint());
    }
}
