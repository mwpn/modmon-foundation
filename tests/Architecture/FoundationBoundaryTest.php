<?php

declare(strict_types=1);

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Executable architectural invariants.
 *
 * These tests enforce Foundation boundary rules:
 * - Foundation code must not import/reference business module internals
 * - Foundation code must not hardcode business module checks
 */
class FoundationBoundaryTest extends TestCase
{
    /**
     * Foundation source files must not import any Modules\ namespace directly.
     *
     * The Foundation knows contracts and metadata, never future business implementations.
     */
    public function test_foundation_does_not_import_module_internals(): void
    {
        $foundationPath = $this->foundationPath();
        if (! is_dir($foundationPath)) {
            $this->markTestSkipped('Foundation directory not found.');
        }

        $violations = [];

        foreach ($this->phpFiles($foundationPath) as $file) {
            $contents = file_get_contents($file);
            $relativePath = str_replace(base_path() . '/', '', $file);

            // Check for use/import of Modules\ namespace
            if (preg_match_all('/^\s*use\s+(Modules\\\\[^;]+);/m', $contents, $matches)) {
                foreach ($matches[1] as $import) {
                    $violations[] = "{$relativePath} imports {$import}";
                }
            }

            // Check for inline references like new Modules\..., Modules\...::
            if (preg_match_all('/(?:new|instanceof)\s+(Modules\\\\[\w\\\\]+)/', $contents, $matches)) {
                foreach ($matches[1] as $ref) {
                    $violations[] = "{$relativePath} references {$ref}";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Foundation must not depend on module internals:\n" . implode("\n", $violations),
        );
    }

    /**
     * No hardcoded business-module checks in Foundation views.
     */
    public function test_foundation_views_do_not_hardcode_module_names(): void
    {
        $viewsPath = $this->foundationPath() . '/Experience/views';
        if (! is_dir($viewsPath)) {
            $this->markTestSkipped('Foundation views directory not found.');
        }

        $violations = [];
        $forbiddenPatterns = [
            '/if.*module.*(?:inventory|billing|customer|product|meter|pos|attendance)/i',
            '/\@if\s*\(\s*module_enabled\s*\(\s*[\'"](?!example)/i',
        ];

        foreach ($this->bladeFiles($viewsPath) as $file) {
            $contents = file_get_contents($file);
            $relativePath = str_replace(base_path() . '/', '', $file);

            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $contents, $match)) {
                    $violations[] = "{$relativePath}: hardcoded module check '{$match[0]}'";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Foundation views must not hardcode business module names:\n" . implode("\n", $violations),
        );
    }

    /**
     * Example module.json must be valid and parseable.
     */
    public function test_example_module_json_is_valid(): void
    {
        $path = base_path('Modules/Example/module.json');
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true);
        $this->assertNotNull($data, 'module.json must be valid JSON');
        $this->assertEquals(1, $data['schema']);
        $this->assertEquals('example', $data['code']);
        $this->assertArrayHasKey('provider', $data);
        $this->assertArrayHasKey('compatibility', $data);
    }

    /**
     * Every module directory must contain a module.json.
     */
    public function test_all_module_directories_have_manifest(): void
    {
        $modulesDir = base_path('Modules');
        if (! is_dir($modulesDir)) {
            $this->markTestSkipped('Modules directory not found.');
        }

        $dirs = array_filter(glob($modulesDir . '/*'), 'is_dir');
        foreach ($dirs as $dir) {
            $this->assertFileExists(
                $dir . '/module.json',
                basename($dir) . ' module directory must contain module.json',
            );
        }
    }

    private function foundationPath(): string
    {
        return base_path('app/Foundation');
    }

    private function phpFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function bladeFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
