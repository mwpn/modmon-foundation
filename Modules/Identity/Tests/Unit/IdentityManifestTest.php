<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Unit;

use App\Foundation\Runtime\ManifestValidator;
use PHPUnit\Framework\TestCase;

class IdentityManifestTest extends TestCase
{
    private function manifestData(): array
    {
        return json_decode(
            file_get_contents(dirname(__DIR__, 3).'/Identity/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    public function test_module_json_is_valid(): void
    {
        $errors = (new ManifestValidator)->validate($this->manifestData());

        $this->assertSame([], $errors);
    }

    public function test_manifest_declares_expected_identity(): void
    {
        $data = $this->manifestData();

        $this->assertSame('Identity', $data['name']);
        $this->assertSame('identity', $data['code']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('platform', $data['type']);
        $this->assertSame('Modules\\Identity\\IdentityServiceProvider', $data['provider']);
    }

    public function test_manifest_declares_capabilities(): void
    {
        $data = $this->manifestData();

        $this->assertSame(['identity.user', 'identity.authentication'], $data['provides']);
        $this->assertSame([], $data['requires']['capabilities']);
    }

    public function test_manifest_declares_compatibility(): void
    {
        $data = $this->manifestData();

        $this->assertSame('^8.3', $data['compatibility']['php']);
        $this->assertSame('^13.0', $data['compatibility']['laravel']);
        $this->assertSame('^1.0', $data['compatibility']['foundation']);
    }
}
