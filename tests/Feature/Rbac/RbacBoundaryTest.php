<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use PHPUnit\Framework\TestCase;

/**
 * Architecture boundary: RBAC must never import Identity internals
 * (models, queries, controllers, service providers) or the host user
 * model. Only the public identity contracts are allowed.
 */
class RbacBoundaryTest extends TestCase
{
    /**
     * @return string[] PHP source files inside Modules/Rbac (module code only)
     */
    private function rbacPhpFiles(): array
    {
        $files = [];
        $moduleDir = $this->moduleRoot();

        if ($moduleDir === '' || ! is_dir($moduleDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $moduleDir,
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            $normalized = str_replace('\\', '/', $file->getPathname());
            if ($file->getExtension() === 'php' && ! str_contains($normalized, '/Tests/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Locate the Modules/Rbac root by walking up from this test file —
     * works standalone (no Laravel app) and from any repository layout.
     */
    private function moduleRoot(): string
    {
        $dir = __DIR__;

        while (true) {
            if (is_dir($dir.'/Modules/Rbac')) {
                return $dir.'/Modules/Rbac';
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                return '';
            }

            $dir = $parent;
        }
    }

    public function test_module_source_files_are_audited(): void
    {
        $this->assertNotEmpty(
            $this->rbacPhpFiles(),
            'Boundary test must audit at least one PHP source file in Modules/Rbac.',
        );
    }

    public function test_no_import_of_identity_or_host_internals(): void
    {
        foreach ($this->rbacPhpFiles() as $file) {
            $content = file_get_contents($file);
            $code = preg_replace('#/\*.*?\*/#s', '', $content) ?? '';

            foreach ($this->forbiddenImports() as $forbidden) {
                $this->assertStringNotContainsString(
                    $forbidden,
                    $code,
                    "{$file} must not import {$forbidden}",
                );
            }
        }
    }

    public function test_rbac_only_imports_identity_public_contracts(): void
    {
        foreach ($this->rbacPhpFiles() as $file) {
            $content = file_get_contents($file);
            $code = preg_replace('#/\*.*?\*/#s', '', $content) ?? '';

            preg_match_all('/use\s+Modules\\\\Identity\\\\[^;]+;/', $code, $matches);

            foreach ($matches[0] ?? [] as $import) {
                $this->assertStringContainsString(
                    'Domain\\Contracts\\',
                    $import,
                    "{$file} must only import Identity public contracts, got: {$import}",
                );
            }
        }
    }

    /**
     * @return string[]
     */
    private function forbiddenImports(): array
    {
        return [
            'use Modules\\Identity\\Models',
            'use Modules\\Identity\\Application',
            'use Modules\\Identity\\Infrastructure',
            'use Modules\\Identity\\Http',
            'use Modules\\Identity\\IdentityServiceProvider',
            'use Modules\\Identity\\Console',
            'use Modules\\Identity\\Notifications',
            'use App\\Models\\User',
        ];
    }
}
