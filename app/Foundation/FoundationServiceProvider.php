<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Foundation\Experience\Navigation\NavigationRegistry;
use App\Foundation\Experience\PermissionRegistry;
use App\Foundation\Experience\Workspace\WorkspaceRegistry;
use App\Foundation\Runtime\CapabilityRegistry;
use App\Foundation\Runtime\Commands\FoundationDoctorCommand;
use App\Foundation\Runtime\Commands\ModuleDisableCommand;
use App\Foundation\Runtime\Commands\ModuleDoctorCommand;
use App\Foundation\Runtime\Commands\ModuleEnableCommand;
use App\Foundation\Runtime\Commands\ModuleInstallCommand;
use App\Foundation\Runtime\Commands\ModuleListCommand;
use App\Foundation\Runtime\CompatibilityChecker;
use App\Foundation\Runtime\DependencyResolver;
use App\Foundation\Runtime\ManifestValidator;
use App\Foundation\Runtime\ModuleDiscovery;
use App\Foundation\Runtime\ModuleManager;
use App\Foundation\Runtime\ModuleRegistrar;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Foundation layers: Runtime, SDK contracts, Infrastructure,
 * Experience Kernel registries, and Artisan commands.
 */
class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- SDK Contracts → Implementations ---

        $this->app->singleton(ModuleRegistrarContract::class, function () {
            return new ModuleRegistrar(
                storage_path('app/modules.json'),
            );
        });

        $this->app->singleton(CapabilityRegistryContract::class, CapabilityRegistry::class);
        $this->app->singleton(NavigationRegistryContract::class, NavigationRegistry::class);
        $this->app->singleton(WorkspaceRegistryContract::class, WorkspaceRegistry::class);
        $this->app->singleton(PermissionRegistryContract::class, PermissionRegistry::class);

        // --- Runtime components ---

        $this->app->singleton(ManifestValidator::class);
        $this->app->singleton(CompatibilityChecker::class);
        $this->app->singleton(DependencyResolver::class);

        $this->app->singleton(ModuleDiscovery::class, function () {
            return new ModuleDiscovery(
                $this->app->make(ManifestValidator::class),
                base_path('Modules'),
            );
        });

        $this->app->singleton(ModuleManager::class, function () {
            return new ModuleManager(
                $this->app->make(ModuleDiscovery::class),
                $this->app->make(ModuleRegistrarContract::class),
                $this->app->make(CapabilityRegistryContract::class),
                $this->app->make(CompatibilityChecker::class),
                $this->app->make(DependencyResolver::class),
                $this->app->make(NavigationRegistryContract::class),
                $this->app->make(WorkspaceRegistryContract::class),
                $this->app->make(PermissionRegistryContract::class),
            );
        });
    }

    public function boot(): void
    {
        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleListCommand::class,
                ModuleDoctorCommand::class,
                ModuleInstallCommand::class,
                ModuleEnableCommand::class,
                ModuleDisableCommand::class,
                FoundationDoctorCommand::class,
            ]);
        }

        // Register Experience Blade components
        Blade::componentNamespace('App\\Foundation\\Experience\\Components', 'foundation');

        // Load Foundation views
        $this->loadViewsFrom(__DIR__ . '/Experience/views', 'foundation');

        // Boot enabled modules
        $this->app->make(ModuleManager::class)->bootEnabledModules();
    }
}
