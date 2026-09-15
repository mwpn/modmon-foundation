<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class TenantDomainsBoundaryTest extends TestCase
{
    public function test_sources_do_not_use_tenancy_internals_or_tenant_context(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'Modules\\Tenancy\\Domain\\Models',
            'Modules\\Tenancy\\Application',
            'TenantContextContract',
            'MembershipContract',
            'tenancy_tenants',
            'tenancy_memberships',
            'tenancy_contexts',
            'Modules\\Identity',
            'Modules\\Branding',
            'Modules\\Settings',
            'Modules\\Rbac',
            'App\\Foundation\\Runtime',
            'branding.application',
            'identity.user',
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
                    "TenantDomains file {$file} must not reference {$needle}",
                );
            }
        }
    }

    public function test_manifest_requires_only_tenancy_tenant(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('integration', $manifest['type']);
        $this->assertSame(['tenancy.domain'], $manifest['provides']);
        $this->assertSame(['tenancy.tenant'], $manifest['requires']['capabilities'] ?? []);
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
