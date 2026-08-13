<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\ManifestValidator;
use App\Foundation\Runtime\ModuleDiscovery;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

class ModuleMakeCommandTest extends TestCase
{
    private string $modulesPath;

    /** @var string[] Directories present in Modules/ before this test ran. */
    private array $preexistingModules = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulesPath = base_path('Modules');

        $this->preexistingModules = $this->moduleDirectories();

        $this->app->forgetInstance(ModuleDiscovery::class);
        $this->app->forgetInstance(ModuleRegistrarContract::class);
    }

    protected function tearDown(): void
    {
        // Remove only directories this test created. Directories that
        // already existed when the test started (real modules such as
        // Example, Identity, or Rbac) are never touched, so cleanup can
        // never delete a real module.
        foreach (array_diff($this->moduleDirectories(), $this->preexistingModules) as $directory) {
            $path = $this->modulesPath.'/'.$directory;
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            }
        }
        parent::tearDown();
    }

    /**
     * @return string[] Directories directly inside Modules/
     */
    private function moduleDirectories(): array
    {
        if (! is_dir($this->modulesPath)) {
            return [];
        }

        $directories = array_filter(
            scandir($this->modulesPath) ?: [],
            fn (string $item) => ! in_array($item, ['.', '..'], true)
                && is_dir($this->modulesPath.'/'.$item),
        );

        return array_values($directories);
    }

    public function test_it_scaffolds_the_minimum_portable_structure(): void
    {
        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $modulePath = $this->modulesPath.'/WaterBilling';

        $this->assertFileExists($modulePath.'/module.json');
        $this->assertFileExists($modulePath.'/WaterBillingServiceProvider.php');
        $this->assertFileExists($modulePath.'/README.md');

        $this->assertFileDoesNotExist($modulePath.'/Routes');
        $this->assertFileDoesNotExist($modulePath.'/Database');
        $this->assertFileDoesNotExist($modulePath.'/Tests');
    }

    public function test_it_generates_a_valid_module_json(): void
    {
        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $data = json_decode(
            File::get($this->modulesPath.'/WaterBilling/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $errors = app(ManifestValidator::class)->validate($data);

        $this->assertSame([], $errors, 'Generated module.json must pass ManifestValidator.');
        $this->assertSame('WaterBilling', $data['name']);
        $this->assertSame('water-billing', $data['code']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('business', $data['type']);
        $this->assertSame('Modules\\WaterBilling\\WaterBillingServiceProvider', $data['provider']);
        $this->assertSame('^8.3', $data['compatibility']['php']);
        $this->assertSame('^13.0', $data['compatibility']['laravel']);
        $this->assertSame('^1.0', $data['compatibility']['foundation']);
        $this->assertSame([], $data['requires']['capabilities']);
        $this->assertSame([], $data['provides']);
    }

    public function test_it_generates_a_provider_class_matching_the_manifest(): void
    {
        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $data = json_decode(
            File::get($this->modulesPath.'/WaterBilling/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $providerFile = $this->modulesPath.'/WaterBilling/WaterBillingServiceProvider.php';

        $this->assertFileExists($providerFile);
        $this->assertStringContainsString('namespace Modules\WaterBilling;', File::get($providerFile));
        $this->assertStringContainsString('class WaterBillingServiceProvider extends ServiceProvider', File::get($providerFile));

        // The provider class must be loadable and match the manifest FQCN.
        $this->assertTrue(class_exists($data['provider']));
        $this->assertTrue(is_subclass_of($data['provider'], ServiceProvider::class));
    }

    public function test_it_generates_a_readme_from_the_canonical_template(): void
    {
        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $readme = File::get($this->modulesPath.'/WaterBilling/README.md');

        $this->assertStringContainsString('# WaterBilling', $readme);
        $this->assertStringContainsString('`business`', $readme);
        $this->assertStringContainsString('^8.3', $readme);
        $this->assertStringContainsString('^13.0', $readme);
        $this->assertStringContainsString('^1.0', $readme);
        $this->assertStringContainsString('module:doctor water-billing', $readme);
        $this->assertStringContainsString('module:install water-billing', $readme);
        $this->assertStringContainsString('Modules\\\\WaterBilling', $readme);
    }

    public function test_it_scaffolds_provides_requires_and_purpose_from_options(): void
    {
        $this->artisan('module:make', [
            'name' => 'Scheduling',
            '--type' => 'business',
            '--purpose' => 'Employee shift scheduling.',
            '--provides' => 'scheduling.shift',
            '--requires' => 'identity.user,identity.authentication',
        ])->assertSuccessful();

        $data = json_decode(
            File::get($this->modulesPath.'/Scheduling/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $errors = app(ManifestValidator::class)->validate($data);

        $this->assertSame([], $errors);
        $this->assertSame('business', $data['type']);
        $this->assertSame(['scheduling.shift'], $data['provides']);
        $this->assertSame(['identity.user', 'identity.authentication'], $data['requires']['capabilities']);

        $readme = File::get($this->modulesPath.'/Scheduling/README.md');

        $this->assertStringContainsString('Employee shift scheduling.', $readme);
        $this->assertStringContainsString('`scheduling.shift`', $readme);
        $this->assertStringContainsString('`identity.user`', $readme);
        $this->assertStringContainsString('`identity.authentication`', $readme);
    }

    public function test_it_rejects_invalid_capability_identifiers(): void
    {
        $this->artisan('module:make', [
            'name' => 'Inventory',
            '--provides' => 'INVALID_CAPABILITY',
        ])->assertFailed()
            ->expectsOutputToContain('Invalid capability identifier');

        $this->assertFileDoesNotExist($this->modulesPath.'/Inventory');
    }

    public function test_scaffolded_module_conforms_to_authoring_standard_minimum(): void
    {
        $this->artisan('module:make', [
            'name' => 'WaterBilling',
            '--type' => 'business',
            '--purpose' => 'Water utility billing.',
            '--provides' => 'billing.invoice',
        ])->assertSuccessful();

        $modulePath = $this->modulesPath.'/WaterBilling';
        $data = json_decode(
            File::get($modulePath.'/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame([], app(ManifestValidator::class)->validate($data));
        $this->assertSame('WaterBilling', $data['name']);
        $this->assertSame('water-billing', $data['code']);
        $this->assertSame('Modules\\WaterBilling\\WaterBillingServiceProvider', $data['provider']);
        $this->assertSame('^8.3', $data['compatibility']['php']);
        $this->assertSame('^13.0', $data['compatibility']['laravel']);
        $this->assertSame('^1.0', $data['compatibility']['foundation']);
        $this->assertIsArray($data['requires']['capabilities']);
        $this->assertIsArray($data['provides']);

        $providerFile = $modulePath.'/WaterBillingServiceProvider.php';
        $this->assertFileExists($providerFile);
        $this->assertTrue(class_exists($data['provider']));
        $this->assertTrue(is_subclass_of($data['provider'], ServiceProvider::class));

        $discovery = app(ModuleDiscovery::class)->discover();
        $this->assertArrayHasKey('water-billing', $discovery['manifests']);
        $this->assertSame([], $discovery['errors']);

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('water-billing'));
    }

    public function test_output_is_deterministic(): void
    {
        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $first = File::get($this->modulesPath.'/WaterBilling/module.json')
            .File::get($this->modulesPath.'/WaterBilling/WaterBillingServiceProvider.php')
            .File::get($this->modulesPath.'/WaterBilling/README.md');

        File::deleteDirectory($this->modulesPath.'/WaterBilling');

        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $second = File::get($this->modulesPath.'/WaterBilling/module.json')
            .File::get($this->modulesPath.'/WaterBilling/WaterBillingServiceProvider.php')
            .File::get($this->modulesPath.'/WaterBilling/README.md');

        $this->assertSame($first, $second);
    }

    public function test_it_rejects_an_invalid_module_name(): void
    {
        $this->artisan('module:make', ['name' => 'inventory'])
            ->assertFailed()
            ->expectsOutputToContain('Invalid module name');

        $this->assertFileDoesNotExist($this->modulesPath.'/Inventory');
    }

    public function test_it_rejects_an_invalid_module_code(): void
    {
        $this->artisan('module:make', ['name' => 'Inventory', '--code' => 'INVALID_CODE'])
            ->assertFailed()
            ->expectsOutputToContain('Invalid module code');

        $this->assertFileDoesNotExist($this->modulesPath.'/Inventory');
    }

    public function test_it_rejects_an_invalid_module_type(): void
    {
        $this->artisan('module:make', ['name' => 'Inventory', '--type' => 'invalid'])
            ->assertFailed()
            ->expectsOutputToContain('Invalid module type');

        $this->assertFileDoesNotExist($this->modulesPath.'/Inventory');
    }

    public function test_it_rejects_a_duplicate_module_directory(): void
    {
        File::makeDirectory($this->modulesPath.'/Inventory', 0755, true);

        $this->artisan('module:make', ['name' => 'Inventory'])
            ->assertFailed()
            ->expectsOutputToContain('already exists');

        $this->assertFileDoesNotExist($this->modulesPath.'/Inventory/module.json');
    }

    public function test_it_rejects_a_duplicate_module_code(): void
    {
        File::makeDirectory($this->modulesPath.'/MeterReading', 0755, true);
        File::put(
            $this->modulesPath.'/MeterReading/module.json',
            '{"schema":1,"name":"Meter Reading","code":"water-billing","version":"1.0.0","type":"business","provider":"Modules\\\\MeterReading\\\\MeterReadingServiceProvider"}',
        );
        File::put($this->modulesPath.'/MeterReading/MeterReadingServiceProvider.php', '<?php');

        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertFailed()
            ->expectsOutputToContain("Module code 'water-billing' is already used");

        $this->assertFileDoesNotExist($this->modulesPath.'/WaterBilling');
    }

    public function test_it_rejects_a_duplicate_provider_class(): void
    {
        File::makeDirectory($this->modulesPath.'/Inventory', 0755, true);
        File::put(
            $this->modulesPath.'/Inventory/module.json',
            '{"schema":1,"name":"Inventory","code":"inventory","version":"1.0.0","type":"business","provider":"Modules\\\\WaterBilling\\\\WaterBillingServiceProvider"}',
        );
        File::put($this->modulesPath.'/Inventory/InventoryServiceProvider.php', '<?php');

        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertFailed()
            ->expectsOutputToContain('already used by the module');

        $this->assertFileDoesNotExist($this->modulesPath.'/WaterBilling');
    }

    public function test_it_never_mutates_runtime_module_state(): void
    {
        $this->artisan('module:make', ['name' => 'WaterBilling'])
            ->assertSuccessful();

        $this->assertFileDoesNotExist(storage_path('app/modules.json'));

        $result = app(ModuleDiscovery::class)->discover();
        $this->assertArrayHasKey('water-billing', $result['manifests']);
        $this->assertSame([], $result['errors']);

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('water-billing'));
        $this->assertTrue(app(ModuleRegistrarContract::class)->isInstalled('water-billing') === false);
    }
}
