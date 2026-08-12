<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\Contributions\ContributesDashboard;
use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\ModuleDiagnostic;
use App\Foundation\SDK\ModuleManifest;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/**
 * Central module lifecycle manager.
 *
 * Orchestrates discovery, installation, enable/disable, contribution
 * registration, and diagnostics.
 */
class ModuleManager
{
    /** @var ModuleManifest[] Discovered manifests keyed by code */
    private array $manifests = [];

    /** @var array<string, string[]> Discovery errors keyed by folder name */
    private array $discoveryErrors = [];

    private bool $discovered = false;

    public function __construct(
        private readonly ModuleDiscovery           $discovery,
        private readonly ModuleRegistrarContract    $registrar,
        private readonly CapabilityRegistryContract $capabilities,
        private readonly CompatibilityChecker       $compatibility,
        private readonly DependencyResolver         $resolver,
        private readonly NavigationRegistryContract  $navigation,
        private readonly WorkspaceRegistryContract   $workspace,
        private readonly PermissionRegistryContract  $permissions,
    ) {}

    /**
     * Run discovery and return discovered manifests.
     *
     * @return ModuleManifest[]
     */
    public function discover(): array
    {
        if (! $this->discovered) {
            $result = $this->discovery->discover();
            $this->manifests      = $result['manifests'];
            $this->discoveryErrors = $result['errors'];
            $this->discovered      = true;
        }

        return $this->manifests;
    }

    /**
     * Get discovery errors.
     */
    public function discoveryErrors(): array
    {
        $this->discover();

        return $this->discoveryErrors;
    }

    /**
     * Get a single module manifest.
     */
    public function manifest(string $code): ?ModuleManifest
    {
        $this->discover();

        return $this->manifests[$code] ?? null;
    }

    /**
     * Get all discovered manifests.
     *
     * @return ModuleManifest[]
     */
    public function manifests(): array
    {
        $this->discover();

        return $this->manifests;
    }

    /**
     * Run diagnostics on a specific module.
     *
     * @return ModuleDiagnostic[]
     */
    public function diagnose(string $code): array
    {
        $this->discover();
        $diagnostics = [];

        // 1. Manifest exists & valid
        $manifest = $this->manifests[$code] ?? null;
        if ($manifest === null) {
            $errors = $this->discoveryErrors[$code] ?? ["Module '{$code}' not found."];
            $diagnostics[] = new ModuleDiagnostic('manifest', false, implode(' ', $errors));
            return $diagnostics;
        }
        $diagnostics[] = new ModuleDiagnostic('manifest', true, "Valid module.json for '{$manifest->name}'.");

        // 2. PHP compatibility
        $phpConstraint = $manifest->phpConstraint();
        if ($phpConstraint) {
            $compErrors = $this->compatibility->check($manifest);
            $phpErrors = array_filter($compErrors, fn ($e) => str_contains($e, 'PHP'));
            $diagnostics[] = new ModuleDiagnostic(
                'php_compatibility',
                empty($phpErrors),
                empty($phpErrors)
                    ? "PHP " . PHP_VERSION . " satisfies {$phpConstraint}."
                    : implode(' ', $phpErrors),
            );
        }

        // 3. Laravel compatibility
        $laravelConstraint = $manifest->laravelConstraint();
        if ($laravelConstraint) {
            $compErrors = $this->compatibility->check($manifest);
            $laravelErrors = array_filter($compErrors, fn ($e) => str_contains($e, 'Laravel'));
            $diagnostics[] = new ModuleDiagnostic(
                'laravel_compatibility',
                empty($laravelErrors),
                empty($laravelErrors)
                    ? "Laravel " . app()->version() . " satisfies {$laravelConstraint}."
                    : implode(' ', $laravelErrors),
            );
        }

        // 4. Foundation compatibility
        $foundationConstraint = $manifest->foundationConstraint();
        if ($foundationConstraint) {
            $compErrors = $this->compatibility->check($manifest);
            $foundErrors = array_filter($compErrors, fn ($e) => str_contains($e, 'Foundation'));
            $diagnostics[] = new ModuleDiagnostic(
                'foundation_compatibility',
                empty($foundErrors),
                empty($foundErrors)
                    ? "Foundation " . CompatibilityChecker::FOUNDATION_VERSION . " satisfies {$foundationConstraint}."
                    : implode(' ', $foundErrors),
            );
        }

        // 5. Required capabilities
        if (! empty($manifest->requires)) {
            $missing = $this->capabilities->missing($manifest->requires);
            $diagnostics[] = new ModuleDiagnostic(
                'capabilities',
                empty($missing),
                empty($missing)
                    ? "All required capabilities available: " . implode(', ', $manifest->requires) . "."
                    : "Missing capabilities: " . implode(', ', $missing) . ".",
            );
        }

        // 6. Provider class exists
        $providerExists = class_exists($manifest->provider);
        $diagnostics[] = new ModuleDiagnostic(
            'provider',
            $providerExists,
            $providerExists
                ? "Provider class {$manifest->provider} found."
                : "Provider class {$manifest->provider} not found. Check autoloading.",
        );

        // 7. Installation state
        $state = $this->registrar->getState($code);
        $diagnostics[] = new ModuleDiagnostic(
            'state',
            true,
            $state ? "Current state: {$state->value}." : "Not yet installed (discovered only).",
        );

        // 8. Migrations directory
        $migrationsPath = $manifest->path . '/Database/Migrations';
        $hasMigrations  = is_dir($migrationsPath) && count(glob($migrationsPath . '/*.php') ?: []) > 0;
        $diagnostics[] = new ModuleDiagnostic(
            'migrations',
            true,
            $hasMigrations
                ? "Migrations directory found with " . count(glob($migrationsPath . '/*.php') ?: []) . " file(s)."
                : "No migrations directory (or empty).",
        );

        return $diagnostics;
    }

    /**
     * Install a module.
     *
     * Validates manifest, compatibility, required capabilities, runs
     * migrations, and marks as installed+enabled.
     *
     * @return array{success: bool, messages: string[]}
     */
    public function install(string $code): array
    {
        $this->discover();
        $messages = [];

        $manifest = $this->manifests[$code] ?? null;
        if (! $manifest) {
            return ['success' => false, 'messages' => ["Module '{$code}' not found."]];
        }

        // Already installed?
        if ($this->registrar->isInstalled($code)) {
            return ['success' => false, 'messages' => ["Module '{$code}' is already installed."]];
        }

        // Compatibility check
        $compErrors = $this->compatibility->check($manifest);
        if (! empty($compErrors)) {
            return ['success' => false, 'messages' => $compErrors];
        }

        // Required capabilities
        $missing = $this->capabilities->missing($manifest->requires);
        if (! empty($missing)) {
            return ['success' => false, 'messages' => [
                "Missing required capabilities: " . implode(', ', $missing) . ".",
            ]];
        }

        // Provider class
        if (! class_exists($manifest->provider)) {
            return ['success' => false, 'messages' => [
                "Provider class {$manifest->provider} not found.",
            ]];
        }

        // Run migrations
        $migrationsPath = $manifest->path . '/Database/Migrations';
        if (is_dir($migrationsPath) && count(glob($migrationsPath . '/*.php') ?: []) > 0) {
            $exitCode = Artisan::call('migrate', [
                '--path' => str_replace(base_path() . '/', '', $migrationsPath),
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                return ['success' => false, 'messages' => [
                    "Migration failed for module '{$code}'. Module was NOT installed.",
                ]];
            }

            $messages[] = "Migrations applied.";
        }

        // Capability collision check
        foreach ($manifest->provides as $capability) {
            $existingProvider = $this->capabilities->provider($capability);
            if ($existingProvider !== null) {
                return ['success' => false, 'messages' => [
                    "Capability '{$capability}' is already provided by module '{$existingProvider}'. Cannot install '{$code}'.",
                ]];
            }
        }

        // Duplicate provider class check
        foreach ($this->manifests as $existingCode => $existingManifest) {
            if ($existingCode === $code) {
                continue;
            }
            if ($existingManifest->provider === $manifest->provider && $this->registrar->isInstalled($existingCode)) {
                return ['success' => false, 'messages' => [
                    "Provider class {$manifest->provider} is already used by installed module '{$existingCode}'.",
                ]];
            }
        }

        // Mark installed + enabled BEFORE registering capabilities
        // so a persist failure does not leave stale in-memory capabilities
        $this->registrar->setState($code, ModuleState::Enabled);

        // Register capabilities
        $this->capabilities->registerProvider($code, $manifest->provides);

        $messages[] = "Module '{$code}' installed and enabled.";

        // Boot contributions
        $this->bootModuleContributions($manifest);

        return ['success' => true, 'messages' => $messages];
    }

    /**
     * Enable a previously disabled module.
     *
     * @return array{success: bool, messages: string[]}
     */
    public function enable(string $code): array
    {
        $this->discover();

        $manifest = $this->manifests[$code] ?? null;
        if (! $manifest) {
            return ['success' => false, 'messages' => ["Module '{$code}' not found."]];
        }

        $state = $this->registrar->getState($code);
        if ($state === null) {
            return ['success' => false, 'messages' => ["Module '{$code}' is not installed. Run module:install first."]];
        }
        if ($state === ModuleState::Enabled) {
            return ['success' => false, 'messages' => ["Module '{$code}' is already enabled."]];
        }

        // Re-validate dependencies before enabling
        $enabledManifests = $this->getEnabledManifests();
        $problems = $this->resolver->canEnable($manifest, $enabledManifests);
        if (! empty($problems)) {
            return ['success' => false, 'messages' => $problems];
        }

        // Capability collision check
        foreach ($manifest->provides as $capability) {
            $existingProvider = $this->capabilities->provider($capability);
            if ($existingProvider !== null && $existingProvider !== $code) {
                return ['success' => false, 'messages' => [
                    "Capability '{$capability}' is already provided by module '{$existingProvider}'. Cannot enable '{$code}'.",
                ]];
            }
        }

        // Mark enabled BEFORE registering capabilities
        $this->registrar->setState($code, ModuleState::Enabled);

        // Register capabilities
        $this->capabilities->registerProvider($code, $manifest->provides);

        // Boot contributions
        $this->bootModuleContributions($manifest);

        return ['success' => true, 'messages' => ["Module '{$code}' enabled."]];
    }

    /**
     * Disable a module. Preserves data, removes runtime contributions.
     *
     * @return array{success: bool, messages: string[]}
     */
    public function disable(string $code): array
    {
        $this->discover();

        $manifest = $this->manifests[$code] ?? null;
        if (! $manifest) {
            return ['success' => false, 'messages' => ["Module '{$code}' not found."]];
        }

        $state = $this->registrar->getState($code);
        if ($state !== ModuleState::Enabled) {
            return ['success' => false, 'messages' => ["Module '{$code}' is not currently enabled."]];
        }

        // Check if disabling would break other modules
        $enabledManifests = $this->getEnabledManifests();
        $problems = $this->resolver->canDisable($manifest, $enabledManifests);
        if (! empty($problems)) {
            return ['success' => false, 'messages' => $problems];
        }

        // Unregister capabilities
        $this->capabilities->unregisterProvider($code);

        // Remove contributions
        $this->navigation->removeByModule($code);
        $this->workspace->removeByModule($code);
        $this->permissions->removeByModule($code);

        // Mark disabled
        $this->registrar->setState($code, ModuleState::Disabled);

        return ['success' => true, 'messages' => ["Module '{$code}' disabled. Data preserved."]];
    }

    /**
     * Boot all enabled modules' contributions.
     * Called during application boot.
     */
    public function bootEnabledModules(): void
    {
        $this->discover();

        $enabledManifests = $this->getEnabledManifests();

        // Resolve boot order
        $resolution = $this->resolver->resolve($enabledManifests);

        foreach ($resolution['order'] as $code) {
            $manifest = $enabledManifests[$code];
            $this->capabilities->registerProvider($code, $manifest->provides);
            $this->bootModuleContributions($manifest);
        }
    }

    /**
     * Get manifests of all enabled modules.
     *
     * @return ModuleManifest[]
     */
    public function getEnabledManifests(): array
    {
        $this->discover();
        $enabled = [];

        foreach ($this->manifests as $code => $manifest) {
            if ($this->registrar->isEnabled($code)) {
                $enabled[$code] = $manifest;
            }
        }

        return $enabled;
    }

    /**
     * Boot a single module's contributions.
     */
    private function bootModuleContributions(ModuleManifest $manifest): void
    {
        if (! class_exists($manifest->provider)) {
            return;
        }

        $provider = app()->resolveProvider($manifest->provider);

        // Routes
        if ($provider instanceof ContributesRoutes) {
            $routeFiles = $provider->routeFiles();
            $routeFiles = is_array($routeFiles) ? $routeFiles : [$routeFiles];

            foreach ($routeFiles as $file) {
                if (file_exists($file)) {
                    Route::middleware('web')->group($file);
                }
            }
        }

        // Navigation
        if ($provider instanceof ContributesNavigation) {
            foreach ($provider->navigationItems() as $item) {
                $this->navigation->register($item);
            }
        }

        // Dashboard widgets
        if ($provider instanceof ContributesDashboard) {
            foreach ($provider->dashboardWidgets() as $widget) {
                $this->workspace->register($widget);
            }
        }

        // Permissions
        if ($provider instanceof ContributesPermissions) {
            $this->permissions->register($manifest->code, $provider->permissionDefinitions());
        }

        // Register & boot the provider itself
        app()->register($provider);
    }
}
