<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class TenancyBrandingBoundaryTest extends TestCase
{
    public function test_sources_use_only_public_contracts_not_module_internals(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'Modules\\Branding\\Domain\\Models',
            'Modules\\Branding\\Application',
            'Modules\\Tenancy\\Domain\\Models',
            'Modules\\Tenancy\\Application',
            'branding_application',
            'tenancy_tenants',
            'tenancy_memberships',
            'tenancy_contexts',
            'App\\Foundation\\Runtime',
            'Modules\\Identity\\Models',
            'Modules\\Settings',
            'Modules\\Rbac',
            'RuntimeSettingsContract',
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
                    "TenancyBranding file {$file} must not reference {$needle}",
                );
            }
        }
    }

    public function test_manifest_requires_branding_and_tenancy_caps_only(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('integration', $manifest['type']);
        $this->assertSame(['branding.tenant'], $manifest['provides']);
        $this->assertEqualsCanonicalizing([
            'branding.application',
            'tenancy.tenant',
            'tenancy.context',
        ], $manifest['requires']['capabilities'] ?? []);
        $this->assertNotContains('branding.application', $manifest['provides']);
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
