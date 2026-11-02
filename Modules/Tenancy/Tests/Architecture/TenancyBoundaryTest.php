<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Public contracts/DTOs/exceptions stay Eloquent-free and do not import
 * other modules. Application may use Identity UserQueryContract only.
 */
final class TenancyBoundaryTest extends TestCase
{
    public function test_public_domain_surface_does_not_import_eloquent_or_other_modules(): void
    {
        $roots = [
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Contracts',
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'DTOs',
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Exceptions',
        ];

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

        foreach ($roots as $root) {
            $this->assertDirectoryExists($root);

            foreach ($this->phpFiles($root) as $file) {
                $source = file_get_contents($file);
                $this->assertNotFalse($source);

                foreach ($forbidden as $needle) {
                    $this->assertStringNotContainsString(
                        $needle,
                        $source,
                        "Tenancy public domain file {$file} must not reference {$needle}",
                    );
                }
            }
        }
    }

    public function test_module_sources_do_not_import_rbac_settings_inventory_or_identity_models(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'Modules\\Rbac\\',
            'Modules\\Settings\\',
            'Modules\\Inventory\\',
            'Modules\\Identity\\Models\\',
            'AuthorizationContract',
            'RoleManagementContract',
            'RuntimeSettingsContract',
            'StockContract',
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
                    "Tenancy file {$file} must not reference {$needle}",
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
        $this->assertSame(
            ['tenancy.tenant', 'tenancy.membership', 'tenancy.context'],
            $manifest['provides'],
        );
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
