<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\ModuleManifest;
use Composer\Semver\Semver;

/**
 * Validates module compatibility against the current environment.
 */
class CompatibilityChecker
{
    public const FOUNDATION_VERSION = '1.0.0';

    /**
     * Check compatibility of a module manifest.
     *
     * @return string[] List of incompatibility messages (empty = compatible)
     */
    public function check(ModuleManifest $manifest): array
    {
        $errors = [];

        // PHP version
        $phpConstraint = $manifest->phpConstraint();
        if ($phpConstraint !== null) {
            if (! Semver::satisfies(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION, $phpConstraint)) {
                $errors[] = "Requires PHP {$phpConstraint}, current is " . PHP_VERSION . ".";
            }
        }

        // Laravel version
        $laravelConstraint = $manifest->laravelConstraint();
        if ($laravelConstraint !== null) {
            $laravelVersion = app()->version();
            // Extract numeric version from Laravel version string
            if (preg_match('/^(\d+\.\d+\.\d+)/', $laravelVersion, $m)) {
                if (! Semver::satisfies($m[1], $laravelConstraint)) {
                    $errors[] = "Requires Laravel {$laravelConstraint}, current is {$laravelVersion}.";
                }
            }
        }

        // Foundation Contract version
        $foundationConstraint = $manifest->foundationConstraint();
        if ($foundationConstraint !== null) {
            if (! Semver::satisfies(self::FOUNDATION_VERSION, $foundationConstraint)) {
                $errors[] = "Requires Foundation Contract {$foundationConstraint}, current is " . self::FOUNDATION_VERSION . ".";
            }
        }

        return $errors;
    }
}
