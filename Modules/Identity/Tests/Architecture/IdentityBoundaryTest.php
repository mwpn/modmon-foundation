<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Architecture boundary tests: Identity must never depend on the host
 * App\Models\User or on other modules' internals, and must never touch
 * Foundation-owned infrastructure (sessions) or invent identity_meta.
 */
class IdentityBoundaryTest extends TestCase
{
    /**
     * @return string[] PHP source files inside Modules/Identity
     */
    private function identityPhpFiles(): array
    {
        $files = [];
        $sourceDir = dirname(__DIR__, 2); // Modules/Identity
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $sourceDir,
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

    public function test_no_import_of_host_user_model(): void
    {
        foreach ($this->identityPhpFiles() as $file) {
            $content = file_get_contents($file);
            // Strip comments/docblocks so prose doesn't trip the check
            $code = preg_replace('#/\*.*?\*/#s', '', $content) ?? '';

            $this->assertStringNotContainsString(
                'use App\Models\User',
                $code,
                "{$file} must not import App\\Models\\User",
            );
            $this->assertStringNotContainsString(
                'extends App\Models\User',
                $code,
                "{$file} must not extend App\\Models\\User",
            );
            $this->assertStringNotContainsString(
                'App\Models\User::',
                $code,
                "{$file} must not reference App\\Models\\User statically",
            );
            $this->assertStringNotContainsString(
                'new App\Models\User',
                $code,
                "{$file} must not instantiate App\\Models\\User",
            );
            $this->assertStringNotContainsString(
                'Modules\\Rbac',
                $code,
                "{$file} must not depend on RBAC",
            );
            $this->assertStringNotContainsString(
                'Modules\\Tenancy',
                $code,
                "{$file} must not depend on Tenancy",
            );
        }
    }

    public function test_host_bootstrap_is_not_required_for_guest_redirect(): void
    {
        $bootstrap = file_get_contents(dirname(__DIR__, 4).'/bootstrap/app.php');

        $this->assertStringNotContainsString(
            'identity.login',
            $bootstrap,
            'bootstrap/app.php must not hardcode Identity login redirect',
        );
        $this->assertStringNotContainsString(
            'redirectGuestsTo',
            $bootstrap,
            'Identity guest redirect must not require host redirectGuestsTo edits',
        );
    }

    public function test_no_cross_module_internal_imports(): void
    {
        foreach ($this->identityPhpFiles() as $file) {
            $content = file_get_contents($file);

            $this->assertStringNotContainsString(
                'Modules\Example',
                $content,
                "{$file} must not import other modules' internals",
            );
        }
    }

    public function test_identity_never_mentions_identity_meta_table(): void
    {
        foreach ($this->identityPhpFiles() as $file) {
            $content = file_get_contents($file);

            $this->assertStringNotContainsString(
                'identity_meta',
                $content,
                "{$file} must not reference an identity_meta table",
            );
        }
    }

    public function test_identity_migrations_never_touch_sessions(): void
    {
        $migrationDir = __DIR__.'/../../Database/Migrations';
        if (! is_dir($migrationDir)) {
            $this->markTestSkipped('No migrations directory yet.');
        }

        foreach (glob($migrationDir.'/*.php') ?: [] as $file) {
            $content = file_get_contents($file);

            // Strip comments/docblocks so ownership prose doesn't trip the check
            $code = preg_replace('#/\*.*?\*/#s', '', $content) ?? '';

            $this->assertStringNotContainsString(
                "Schema::create('sessions'",
                $code,
                "{$file} must never create the Foundation-owned sessions table",
            );
            $this->assertStringNotContainsString(
                "Schema::table('sessions'",
                $code,
                "{$file} must never alter the Foundation-owned sessions table",
            );
            $this->assertStringNotContainsString(
                "Schema::drop('sessions'",
                $code,
                "{$file} must never drop the Foundation-owned sessions table",
            );
        }
    }
}
