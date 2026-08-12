<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\ManifestValidator;
use App\Foundation\Runtime\ModuleDiscovery;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Regression tests for ModuleDiscovery path safety.
 *
 * - Symlinks in Modules/ must be rejected
 * - Only direct child directories are scanned
 */
class ModuleDiscoverySafetyTest extends TestCase
{
    private string $tempModulesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempModulesPath = storage_path('testing/modules-' . uniqid());
        File::makeDirectory($this->tempModulesPath, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempModulesPath);
        parent::tearDown();
    }

    /**
     * A symlink inside the Modules directory must be rejected with an error.
     *
     * Skipped on environments where symlink creation requires elevated
     * privileges (e.g. Windows without Developer Mode or admin rights).
     */
    public function test_symlink_in_modules_directory_is_rejected(): void
    {
        // Create a real directory outside modules path
        $outsideDir = storage_path('testing/outside-' . uniqid());
        File::makeDirectory($outsideDir, 0755, true);
        file_put_contents($outsideDir . '/module.json', json_encode([
            'schema' => 1,
            'name' => 'Sneaky',
            'code' => 'sneaky',
            'version' => '1.0.0',
            'type' => 'business',
            'provider' => 'Modules\\Sneaky\\SneakyProvider',
        ]));

        // Attempt to create symlink — skip if OS/permissions deny it
        $symlinkTarget = $this->tempModulesPath . '/Sneaky';
        try {
            $created = @symlink($outsideDir, $symlinkTarget);
        } catch (\Throwable) {
            $created = false;
        }

        if (! $created) {
            File::deleteDirectory($outsideDir);
            $this->markTestSkipped('symlink() not available (requires elevated privileges on this OS).');
        }

        $discovery = new ModuleDiscovery(
            new ManifestValidator(),
            $this->tempModulesPath,
        );

        $result = $discovery->discover();

        // Should NOT discover the symlinked module
        $this->assertArrayNotHasKey('sneaky', $result['manifests']);

        // Should report error for the symlink
        $this->assertArrayHasKey('Sneaky', $result['errors']);
        $this->assertTrue(
            collect($result['errors']['Sneaky'])->contains(fn ($e) => str_contains($e, 'Symlink')),
            'Error should mention symlink rejection',
        );

        // Clean up
        unlink($symlinkTarget);
        File::deleteDirectory($outsideDir);
    }

    /**
     * A valid non-symlinked module directory should still be discovered.
     */
    public function test_real_directory_is_discovered(): void
    {
        $moduleDir = $this->tempModulesPath . '/ValidMod';
        File::makeDirectory($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/module.json', json_encode([
            'schema' => 1,
            'name' => 'Valid Module',
            'code' => 'valid-mod',
            'version' => '1.0.0',
            'type' => 'business',
            'provider' => 'Modules\\ValidMod\\ValidModProvider',
        ]));

        $discovery = new ModuleDiscovery(
            new ManifestValidator(),
            $this->tempModulesPath,
        );

        $result = $discovery->discover();

        $this->assertArrayHasKey('valid-mod', $result['manifests']);
        $this->assertEmpty($result['errors']);
    }
}
