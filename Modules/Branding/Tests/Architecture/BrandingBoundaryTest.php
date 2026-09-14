<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class BrandingBoundaryTest extends TestCase
{
    public function test_branding_sources_do_not_import_other_module_internals(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'Modules\\Identity',
            'Modules\\Rbac',
            'Modules\\Settings',
            'Modules\\Tenancy',
            'Modules\\Inventory',
            'UserQueryContract',
            'RuntimeSettingsContract',
            'TenantContract',
            'TenantContextContract',
            'App\\Foundation\\Runtime',
            'settings.runtime',
            'tenancy.tenant',
            'identity.user',
            'tenant_id',
        ];

        foreach ($this->phpFiles($root) as $file) {
            if (str_contains($file, DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $source = file_get_contents($file);
            $this->assertNotFalse($source);

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $source,
                    "Branding file {$file} must not reference {$needle}",
                );
            }
        }
    }

    public function test_manifest_requires_nothing_and_provides_branding_application(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame([], $manifest['requires']['capabilities'] ?? []);
        $this->assertSame(['branding.application'], $manifest['provides']);
        $this->assertSame('platform', $manifest['type']);
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
