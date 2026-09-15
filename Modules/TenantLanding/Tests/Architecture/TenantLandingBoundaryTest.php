<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class TenantLandingBoundaryTest extends TestCase
{
    public function test_manifest_requires_tenancy_domain_only_and_provides_landing(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(['tenancy.domain'], $manifest['requires']['capabilities'] ?? []);
        $this->assertSame(['tenancy.landing'], $manifest['provides']);
        $this->assertSame('platform', $manifest['type']);
    }

    public function test_sources_do_not_touch_tenant_context_or_foreign_internals(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'TenantContextContract',
            'setCurrent(',
            'Modules\\Tenancy\\Domain\\Models',
            'Modules\\TenantDomains\\Domain\\Models',
            'Modules\\Branding\\Domain\\Models',
            'Modules\\TenancyBranding\\Domain\\Models',
            'Modules\\Identity\\Models',
            'tenancy_tenants',
            'tenancy_memberships',
            'tenancy_contexts',
            'tenancy_domains',
            'branding_application',
            'tenancy_branding_overrides',
            'App\\Foundation\\Runtime',
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
                    "TenantLanding file {$file} must not reference {$needle}",
                );
            }
        }
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
