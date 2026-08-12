<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\CompatibilityChecker;
use App\Foundation\Runtime\DependencyResolver;
use App\Foundation\Runtime\ManifestValidator;
use App\Foundation\Runtime\ModuleDiscovery;
use App\Foundation\Runtime\ModuleManager;
use App\Foundation\Runtime\ModuleRegistrar;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleManifest;
use App\Foundation\SDK\ModuleState;
use Tests\TestCase;

/**
 * Migration path handling regression tests.
 *
 * - Windows absolute paths work (test_install_actually_runs_module_migrations)
 * - Linux/macOS paths remain supported (realpath is platform-agnostic)
 * - a module without a migrations directory stays installable: the
 *   realpath guard must not turn "no migrations" into a hard failure
 * - no regression to explicit install semantics
 */
class ModuleMigrationPathTest extends TestCase
{
    private string $modulesJsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulesJsonPath = storage_path('app/modules.json');

        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }
        parent::tearDown();
    }

    /**
     * Build a ModuleManager whose discovery returns a single module with
     * the given manifest, bypassing the filesystem scan.
     */
    private function managerWithManifest(ModuleManifest $manifest): array
    {
        $registrar = new ModuleRegistrar($this->modulesJsonPath);

        $discovery = new class($manifest) extends ModuleDiscovery
        {
            public function __construct(private readonly ModuleManifest $manifest)
            {
                parent::__construct(new ManifestValidator, storage_path('testing'));
            }

            public function discover(): array
            {
                return ['manifests' => [$this->manifest->code => $this->manifest], 'errors' => []];
            }
        };

        $manager = new ModuleManager(
            $discovery,
            $registrar,
            app(CapabilityRegistryContract::class),
            new CompatibilityChecker,
            new DependencyResolver,
            app(NavigationRegistryContract::class),
            app(WorkspaceRegistryContract::class),
            app(PermissionRegistryContract::class),
        );

        return [$manager, $registrar];
    }

    /**
     * A module whose directory has no Database/Migrations (e.g. a
     * module:make scaffold) must still install explicitly. This guards
     * against the realpath guard turning "no migrations" into a failure.
     */
    public function test_module_without_migrations_directory_still_installs(): void
    {
        $manifest = new ModuleManifest(
            name: 'Scaffold',
            code: 'scaffold',
            version: '1.0.0',
            type: 'business',
            provider: 'Modules\\Example\\ExampleServiceProvider',
            compatibility: [],
            requires: [],
            provides: [],
            path: storage_path('testing/modules-absent-'.uniqid()), // does not exist
        );

        [$manager, $registrar] = $this->managerWithManifest($manifest);
        $result = $manager->install('scaffold');

        $this->assertTrue($result['success']);
        $this->assertFalse(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Migration path could not be resolved')),
            'Absent migrations directory must not be reported as a path resolution failure',
        );
        $this->assertFalse(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Migrations applied')),
            'No migrations => no "Migrations applied" message',
        );

        $this->assertEquals(ModuleState::Enabled, $registrar->getState('scaffold'));
    }
}
