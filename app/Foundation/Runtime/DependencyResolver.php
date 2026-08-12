<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\ModuleManifest;

/**
 * Resolves module dependencies and detects cycles/conflicts.
 */
class DependencyResolver
{
    /**
     * Resolve the boot order for a set of modules.
     *
     * Modules with no required capabilities boot first.
     * Modules whose requirements are provided by earlier modules boot after.
     *
     * @param ModuleManifest[] $manifests Keyed by module code
     * @return array{order: string[], errors: string[]}
     */
    public function resolve(array $manifests): array
    {
        $errors    = [];
        $resolved  = [];
        $provided  = [];
        $remaining = $manifests;
        $maxPasses = count($manifests) + 1;
        $pass      = 0;

        while (! empty($remaining) && $pass < $maxPasses) {
            $pass++;
            $progress = false;

            foreach ($remaining as $code => $manifest) {
                $missing = array_diff($manifest->requires, $provided);

                if (empty($missing)) {
                    $resolved[] = $code;
                    $provided   = array_merge($provided, $manifest->provides);
                    unset($remaining[$code]);
                    $progress = true;
                }
            }

            if (! $progress) {
                break;
            }
        }

        // Remaining modules have unresolvable dependencies
        foreach ($remaining as $code => $manifest) {
            $missing = array_diff($manifest->requires, $provided);
            $errors[] = "Module '{$code}' has unresolvable dependencies: " . implode(', ', $missing) . ".";
        }

        return ['order' => $resolved, 'errors' => $errors];
    }

    /**
     * Check if enabling a specific module is safe given currently enabled modules.
     *
     * @param ModuleManifest   $target    Module to enable
     * @param ModuleManifest[] $enabled   Currently enabled module manifests
     * @return string[] Problems found (empty = safe to enable)
     */
    public function canEnable(ModuleManifest $target, array $enabled): array
    {
        $provided = [];
        foreach ($enabled as $manifest) {
            $provided = array_merge($provided, $manifest->provides);
        }

        $missing = array_diff($target->requires, $provided);

        if (! empty($missing)) {
            return ["Missing required capabilities: " . implode(', ', $missing) . "."];
        }

        return [];
    }

    /**
     * Check if disabling a module would break dependencies of other enabled modules.
     *
     * @param ModuleManifest   $target  Module to disable
     * @param ModuleManifest[] $enabled Currently enabled module manifests (including target)
     * @return string[] Modules that would break
     */
    public function canDisable(ModuleManifest $target, array $enabled): array
    {
        $broken = [];
        $remainingProvided = [];

        // Capabilities that would remain after disabling target
        foreach ($enabled as $code => $manifest) {
            if ($code !== $target->code) {
                $remainingProvided = array_merge($remainingProvided, $manifest->provides);
            }
        }

        // Check if any remaining module requires capabilities only target provides
        foreach ($enabled as $code => $manifest) {
            if ($code === $target->code) {
                continue;
            }

            $missing = array_diff($manifest->requires, $remainingProvided);
            if (! empty($missing)) {
                $broken[] = "Module '{$code}' requires capabilities that would be lost: " . implode(', ', $missing) . ".";
            }
        }

        return $broken;
    }
}
