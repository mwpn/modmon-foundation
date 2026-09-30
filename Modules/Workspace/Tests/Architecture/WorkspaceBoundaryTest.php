<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class WorkspaceBoundaryTest extends TestCase
{
    public function test_sources_do_not_couple_to_tenant_domains_or_foundation_runtime(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'Modules\\TenantDomains',
            'Modules\\TenantLanding',
            'Modules\\TenancyBranding',
            'Modules\\Identity\\',
            'Modules\\Rbac',
            'Modules\\Branding',
            'App\\Foundation\\Runtime',
            'dkm.test',
            'assyafiiyah',
        ];

        foreach ($this->phpFiles($root) as $file) {
            if (str_contains($file, DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $source = (string) file_get_contents($file);
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $source,
                    "{$file} must not reference {$needle}",
                );
            }
        }
    }

    public function test_soft_domain_attribute_key_is_documented_without_class_import(): void
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR
            .'Application'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR
            .'ContextActiveWorkspace.php';
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString("tenant_domain.tenant_id", $source);
        $this->assertStringNotContainsString('use Modules\\TenantDomains', $source);
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
