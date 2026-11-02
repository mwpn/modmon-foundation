<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Runtime\CompatibilityChecker;
use App\Foundation\SDK\ModuleManifest;
use PHPUnit\Framework\TestCase;

/**
 * Foundation Contract SemVer (1.5.0: Experience floating slot, ADR-0013; 1.4.0: Experience brand hook, ADR-0012; 1.3.0: Experience icon/modal components,
 * ADR-0011; 1.2.0: navigation workspace fallback).
 */
class FoundationCompatibilityVersionTest extends TestCase
{
    public function test_foundation_contract_version_is_1_5_0(): void
    {
        $this->assertSame('1.5.0', CompatibilityChecker::FOUNDATION_VERSION);
    }

    public function test_caret_1_4_passes_on_current_foundation(): void
    {
        $checker = new CompatibilityChecker;
        $errors = $checker->check($this->manifestWithFoundation('^1.4'));

        $this->assertSame([], $errors);
    }

    public function test_caret_1_3_remains_compatible_with_1_4_0(): void
    {
        $checker = new CompatibilityChecker;
        $errors = $checker->check($this->manifestWithFoundation('^1.3'));

        $this->assertSame([], $errors);
    }

    public function test_caret_1_2_remains_compatible_with_1_4_0(): void
    {
        $checker = new CompatibilityChecker;
        $errors = $checker->check($this->manifestWithFoundation('^1.2'));

        $this->assertSame([], $errors);
    }

    public function test_caret_1_1_remains_compatible_with_1_4_0(): void
    {
        $checker = new CompatibilityChecker;
        $errors = $checker->check($this->manifestWithFoundation('^1.1'));

        $this->assertSame([], $errors);
    }

    public function test_caret_1_6_is_incompatible_with_1_5_0(): void
    {
        $checker = new CompatibilityChecker;
        $errors = $checker->check($this->manifestWithFoundation('^1.6'));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Requires Foundation Contract ^1.6', $errors[0]);
        $this->assertStringContainsString('current is 1.5.0', $errors[0]);
    }

    public function test_major_constraint_incompatible_fails(): void
    {
        $checker = new CompatibilityChecker;
        $errors = $checker->check($this->manifestWithFoundation('^2.0'));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Requires Foundation Contract ^2.0', $errors[0]);
    }

    private function manifestWithFoundation(string $foundation): ModuleManifest
    {
        return new ModuleManifest(
            name: 'Compat Probe',
            code: 'compat-probe',
            version: '1.0.0',
            type: 'platform',
            provider: 'Modules\\CompatProbe\\CompatProbeServiceProvider',
            compatibility: [
                'foundation' => $foundation,
            ],
            requires: [],
            provides: [],
        );
    }
}
