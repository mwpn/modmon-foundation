<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Unit;

use App\Foundation\Runtime\CompatibilityChecker;
use App\Foundation\SDK\ModuleManifest;
use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;

/**
 * Locks Workspace ^1.1 SemVer behaviour (Contract 1.1+ hosts vs 1.0).
 */
class WorkspaceFoundationConstraintTest extends TestCase
{
    public function test_manifest_requires_foundation_caret_1_1(): void
    {
        $this->assertSame('^1.1', $this->foundationConstraint());
    }

    public function test_constraint_passes_on_foundation_1_1_0_and_1_2_0(): void
    {
        $constraint = $this->foundationConstraint();

        $this->assertTrue(Semver::satisfies('1.1.0', $constraint));
        $this->assertTrue(Semver::satisfies('1.2.0', $constraint));
    }

    public function test_constraint_fails_on_foundation_1_0_0(): void
    {
        $constraint = $this->foundationConstraint();

        $this->assertFalse(Semver::satisfies('1.0.0', $constraint));

        $message = "Requires Foundation Contract {$constraint}, current is 1.0.0.";
        $this->assertStringContainsString('Requires Foundation Contract ^1.1', $message);
        $this->assertStringContainsString('current is 1.0.0', $message);
    }

    public function test_compatibility_checker_accepts_workspace_foundation_on_current_host(): void
    {
        $constraint = $this->foundationConstraint();
        $this->assertTrue(
            Semver::satisfies(CompatibilityChecker::FOUNDATION_VERSION, $constraint),
            'Host Foundation '.CompatibilityChecker::FOUNDATION_VERSION." must satisfy {$constraint}",
        );

        $checker = new CompatibilityChecker;
        $errors = $checker->check(new ModuleManifest(
            name: 'Workspace',
            code: 'workspace',
            version: '1.0.0',
            type: 'platform',
            provider: 'Modules\\Workspace\\WorkspaceServiceProvider',
            compatibility: ['foundation' => $constraint],
            requires: [],
            provides: [],
        ));

        $this->assertSame([], $errors);
    }

    private function foundationConstraint(): string
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'module.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $data['compatibility']['foundation'];
    }
}
