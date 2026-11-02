<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use App\Foundation\Runtime\ManifestValidator;
use PHPUnit\Framework\TestCase;

final class TenancyManifestTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function manifestData(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
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

        $this->assertSame('Tenancy', $data['name']);
        $this->assertSame('tenancy', $data['code']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('platform', $data['type']);
        $this->assertSame('Modules\\Tenancy\\TenancyServiceProvider', $data['provider']);
    }

    public function test_manifest_declares_capabilities(): void
    {
        $data = $this->manifestData();

        $this->assertSame(
            ['tenancy.tenant', 'tenancy.membership', 'tenancy.context'],
            $data['provides'],
        );
        $this->assertSame(['identity.user'], $data['requires']['capabilities']);
    }

    public function test_manifest_declares_compatibility(): void
    {
        $data = $this->manifestData();

        $this->assertSame('^8.3', $data['compatibility']['php']);
        $this->assertSame('^13.0', $data['compatibility']['laravel']);
        $this->assertSame('^1.0', $data['compatibility']['foundation']);
    }
}
