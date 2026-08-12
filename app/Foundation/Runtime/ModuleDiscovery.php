<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\ModuleManifest;
use Illuminate\Support\Facades\File;

/**
 * Discovers module directories and validates their manifests.
 *
 * Discovery is deterministic: it scans the Modules/ directory for
 * subdirectories containing a valid module.json. Invalid modules
 * produce actionable diagnostics but do not prevent discovery of
 * other modules.
 */
class ModuleDiscovery
{
    public function __construct(
        private readonly ManifestValidator $validator,
        private readonly string $modulesPath,
    ) {}

    /**
     * Discover all module directories.
     *
     * @return array{manifests: ModuleManifest[], errors: array<string, string[]>}
     */
    public function discover(): array
    {
        $manifests = [];
        $errors    = [];

        if (! is_dir($this->modulesPath)) {
            return compact('manifests', 'errors');
        }

        $directories = File::directories($this->modulesPath);
        sort($directories); // deterministic order

        foreach ($directories as $dir) {
            // Reject symlinks to prevent path traversal outside Modules/
            if (is_link($dir)) {
                $errors[basename($dir)] = ["Symlinks are not allowed in the Modules directory."];
                continue;
            }

            // Verify the resolved path is under the modules directory
            $realDir = realpath($dir);
            $realModulesPath = realpath($this->modulesPath);
            if ($realDir === false || $realModulesPath === false || ! str_starts_with($realDir, $realModulesPath . DIRECTORY_SEPARATOR)) {
                $errors[basename($dir)] = ["Module path resolves outside the Modules directory."];
                continue;
            }

            $manifestPath = $dir . '/module.json';

            if (! file_exists($manifestPath)) {
                $errors[basename($dir)] = ["No module.json found in {$dir}."];
                continue;
            }

            $json = file_get_contents($manifestPath);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[basename($dir)] = ["Invalid JSON in module.json: " . json_last_error_msg()];
                continue;
            }

            $validationErrors = $this->validator->validate($data, $dir);

            if (! empty($validationErrors)) {
                $errors[basename($dir)] = $validationErrors;
                continue;
            }

            $manifest = ModuleManifest::fromArray($data, $dir);

            // Detect duplicate module codes
            if (isset($manifests[$manifest->code])) {
                $existing = $manifests[$manifest->code];
                $errors[basename($dir)] = [
                    "Duplicate module code '{$manifest->code}' — already declared by module at {$existing->path}.",
                ];
                continue;
            }

            $manifests[$manifest->code] = $manifest;
        }

        return compact('manifests', 'errors');
    }

    /**
     * Discover a single module by code.
     */
    public function discoverOne(string $code): ?ModuleManifest
    {
        $result = $this->discover();

        return $result['manifests'][$code] ?? null;
    }
}
