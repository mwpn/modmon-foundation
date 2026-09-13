<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Settings must not couple to Identity, RBAC, or other business modules.
 */
class SettingsBoundaryTest extends TestCase
{
    public function test_settings_sources_do_not_import_identity_or_rbac(): void
    {
        $root = dirname(__DIR__, 2);
        $forbidden = [
            'Modules\\Identity',
            'Modules\\Rbac',
            'identity.user',
            'authorization.permission',
            'UserQueryContract',
            'AuthorizationContract',
            'RoleManagementContract',
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
                    "Settings file {$file} must not reference {$needle}",
                );
            }
        }
    }

    public function test_settings_does_not_declare_identity_or_rbac_requires(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $requires = $manifest['requires']['capabilities'] ?? [];

        $this->assertSame([], $requires);
        $this->assertSame(['settings.runtime'], $manifest['provides']);
    }

    public function test_public_contract_lives_under_domain_contracts(): void
    {
        $this->assertFileExists(
            dirname(__DIR__, 2).'/Domain/Contracts/RuntimeSettingsContract.php',
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
