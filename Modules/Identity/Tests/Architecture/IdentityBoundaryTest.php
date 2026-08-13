<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Architecture boundary tests (Phase 5 finalized): Identity must never
 * depend on the host App\Models\User or on other modules' internals,
 * must never touch Foundation-owned infrastructure (sessions), invent
 * identity_meta, mutate .env, or require host source edits.
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

    private function hostRoot(): string
    {
        return dirname(__DIR__, 4);
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
        }
    }

    public function test_no_rbac_tenancy_or_subscription_dependency(): void
    {
        foreach ($this->identityPhpFiles() as $file) {
            $code = preg_replace('#/\*.*?\*/#s', '', file_get_contents($file)) ?? '';

            foreach (['Modules\\Rbac', 'Modules\\Tenancy', 'Modules\\Subscription'] as $forbidden) {
                $this->assertStringNotContainsString(
                    $forbidden,
                    $code,
                    "{$file} must not depend on {$forbidden}",
                );
            }
        }
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
        $this->assertDirectoryExists($migrationDir);

        foreach (glob($migrationDir.'/*.php') ?: [] as $file) {
            $content = file_get_contents($file);
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

    public function test_identity_source_never_mutates_env_file(): void
    {
        foreach ($this->identityPhpFiles() as $file) {
            $code = preg_replace('#/\*.*?\*/#s', '', file_get_contents($file)) ?? '';

            $this->assertDoesNotMatchRegularExpression(
                '/file_put_contents\s*\(\s*[\'"].*\.env/',
                $code,
                "{$file} must never write .env",
            );
            $this->assertStringNotContainsString(
                "putenv('AUTH_MODEL",
                $code,
                "{$file} must never set AUTH_MODEL via putenv",
            );
        }
    }

    public function test_host_bootstrap_is_not_required_for_guest_redirect(): void
    {
        $bootstrap = file_get_contents($this->hostRoot().'/bootstrap/app.php');

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

    public function test_host_routes_and_auth_config_are_not_identity_coupled(): void
    {
        $web = file_get_contents($this->hostRoot().'/routes/web.php');
        $auth = file_get_contents($this->hostRoot().'/config/auth.php');

        $this->assertStringNotContainsString('Modules\\Identity', $web);
        $this->assertStringNotContainsString('identity.login', $web);
        $this->assertStringNotContainsString('Modules\\Identity', $auth);
    }

    public function test_foundation_has_no_identity_specific_knowledge(): void
    {
        $foundationDir = $this->hostRoot().'/app/Foundation';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($foundationDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $this->assertStringNotContainsString(
                'identity.login',
                $content,
                "{$file->getPathname()} must not hardcode identity.login",
            );
            $this->assertStringNotContainsString(
                'Modules\\Identity',
                $content,
                "{$file->getPathname()} must not import Identity",
            );
        }
    }

    public function test_provider_does_not_contribute_nav_widgets_or_permissions(): void
    {
        $provider = file_get_contents(dirname(__DIR__, 2).'/IdentityServiceProvider.php');

        $this->assertStringNotContainsString('ContributesNavigation', $provider);
        $this->assertStringNotContainsString('ContributesDashboard', $provider);
        $this->assertStringNotContainsString('ContributesPermissions', $provider);
        $this->assertStringContainsString('ContributesRoutes', $provider);
    }

    public function test_readme_covers_authoring_standard_contract_sections(): void
    {
        $readme = file_get_contents(dirname(__DIR__, 2).'/README.md');

        foreach ([
            '## Type',
            '## Compatibility',
            '## Provides',
            '## Requires',
            '## Optional Integrations',
            '## Installation',
            '## Configuration',
            '## Permissions',
            '## Routes',
            '## Events Published',
            '## Events Consumed',
            '## Public Contracts',
            '## Database Ownership',
            '## Navigation Contributions',
            '## Dashboard Contributions',
            '## Testing',
            '## Version History',
        ] as $section) {
            $this->assertStringContainsString(
                $section,
                $readme,
                "README must include Authoring Standard §16 section: {$section}",
            );
        }
    }
}
