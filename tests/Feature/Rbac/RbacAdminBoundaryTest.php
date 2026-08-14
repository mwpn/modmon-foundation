<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use PHPUnit\Framework\TestCase;

/**
 * Architecture boundary for the Phase 3 admin surface.
 *
 * RBAC controllers/views must never import Identity internals (models,
 * queries, controllers, service providers) or the host user model —
 * only the public identity contracts are allowed. Routes must stay
 * inside Modules/Rbac, and no host shell/sidebar file may be edited.
 */
class RbacAdminBoundaryTest extends TestCase
{
    private function rbacPhpFiles(): array
    {
        $files = [];
        $moduleDir = $this->moduleRoot();

        if ($moduleDir === '' || ! is_dir($moduleDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            $normalized = str_replace('\\', '/', $file->getPathname());
            if ($file->getExtension() === 'php' && ! str_contains($normalized, '/Tests/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

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

    public function test_controllers_import_only_public_identity_contracts(): void
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

    public function test_controllers_do_not_import_identity_or_host_internals(): void
    {
        $forbidden = [
            'use Modules\\Identity\\Models',
            'use Modules\\Identity\\Application',
            'use Modules\\Identity\\Infrastructure',
            'use Modules\\Identity\\Http',
            'use Modules\\Identity\\IdentityServiceProvider',
            'use Modules\\Identity\\Console',
            'use Modules\\Identity\\Notifications',
            'use App\\Models\\User',
        ];

        foreach ($this->rbacPhpFiles() as $file) {
            $content = file_get_contents($file);
            $code = preg_replace('#/\*.*?\*/#s', '', $content) ?? '';

            foreach ($forbidden as $forbiddenImport) {
                $this->assertStringNotContainsString(
                    $forbiddenImport,
                    $code,
                    "{$file} must not import {$forbiddenImport}",
                );
            }
        }
    }

    public function test_routes_live_inside_the_module(): void
    {
        $routeFile = $this->moduleRoot().'/Routes/web.php';

        $this->assertFileExists($routeFile, 'RBAC routes must live in Modules/Rbac/Routes/web.php');

        $content = file_get_contents($routeFile);

        $this->assertStringNotContainsString('Modules\\Identity', $content);
        $this->assertStringNotContainsString('App\\Models\\User', $content);
    }

    public function test_views_do_not_import_identity_or_host_user(): void
    {
        $viewsDir = $this->moduleRoot().'/Resources/views';

        if (! is_dir($viewsDir)) {
            $this->markTestSkipped('No views yet.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString('Modules\Identity', $content);
            $this->assertStringNotContainsString('App\Models\User', $content);
        }
    }

    public function test_no_host_shell_or_sidebar_edits(): void
    {
        $hostLayout = $this->findHostShell();

        if ($hostLayout === null) {
            $this->markTestSkipped('Host Experience shell not found.');
        }

        $content = file_get_contents($hostLayout);

        $this->assertStringNotContainsString('rbac', $content);
        $this->assertStringNotContainsString('Roles & Permissions', $content);
        $this->assertStringNotContainsString('rbac.roles.manage', $content);
    }

    /**
     * Locate the host Experience shell by walking up from this test file,
     * like moduleRoot(). Returns null when the host layout does not exist.
     */
    private function findHostShell(): ?string
    {
        $dir = __DIR__;

        while (true) {
            $candidate = $dir.'/app/Foundation/Experience/views/layouts/app.blade.php';
            if (is_file($candidate)) {
                return $candidate;
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                return null;
            }

            $dir = $parent;
        }
    }
}
