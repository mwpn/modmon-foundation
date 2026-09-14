<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Phase 0 boundary: public contracts/DTOs stay Eloquent-free and do not
 * import other modules' internals. Capability dependency on identity.user
 * is declared only in module.json.
 */
final class TenancyBoundaryTest extends TestCase
{
    public function test_domain_contracts_and_dtos_do_not_import_eloquent_or_other_modules(): void
    {
        $root = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Domain';
        $this->assertDirectoryExists($root);

        $forbidden = [
            'Illuminate\\Database\\Eloquent',
            'Modules\\Identity\\',
            'Modules\\Rbac\\',
            'Modules\\Settings\\',
            'Modules\\Inventory\\',
            'Modules\\Example\\',
            'App\\Foundation\\Runtime',
            'App\\Foundation\\Experience',
            'AuthorizationContract',
            'RoleManagementContract',
        ];

        foreach ($this->phpFiles($root) as $file) {
            $source = file_get_contents($file);
            $this->assertNotFalse($source);

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $source,
                    "Tenancy Domain file {$file} must not reference {$needle}",
                );
            }
        }
    }

    public function test_manifest_requires_identity_user_only(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $requires = $manifest['requires']['capabilities'] ?? [];
        $this->assertSame(['identity.user'], $requires);
        $this->assertNotContains('identity.authentication', $requires);
        $this->assertNotContains('authorization.permission', $requires);
        $this->assertNotContains('settings.runtime', $requires);
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
