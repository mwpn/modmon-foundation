<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\Contracts\StockContract;
use Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use Modules\Inventory\Domain\Models\StockMovement;

/**
 * Phase 2 — portability / compliance proof (no new features).
 *
 * Models a clean-host sequence on this Foundation authoring host:
 * copy/discovery → doctor → install owns migrations → StockContract +
 * HTTP + contributions → disable/enable preserves data → fail-closed
 * migration → host sources untouched. No Identity/RBAC/Settings required.
 */
class InventoryComplianceTest extends InventoryTestCase
{
    /** @var list<string> */
    private array $watchedHostFiles = [
        'bootstrap/app.php',
        'routes/web.php',
        'config/app.php',
        'config/auth.php',
        'composer.json',
        'composer.lock',
        'app/Foundation/FoundationServiceProvider.php',
        'app/Foundation/Runtime/ModuleManager.php',
    ];

    public function test_portability_lifecycle_contributions_and_invariants(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        // Copy/discovery only — not installed; no schema; no capability.
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertFalse(Schema::hasTable('inventory_items'));
        $this->assertFalse(Schema::hasTable('inventory_stock_movements'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertFalse(
            app()->bound(StockContract::class),
            'StockContract must not be bound before Inventory is installed/enabled',
        );

        $this->artisan('module:doctor', ['code' => 'inventory'])->assertSuccessful();

        // Discovery / doctor must not mutate schema.
        $this->assertFalse(Schema::hasTable('inventory_items'));
        $this->assertFalse(Schema::hasTable('inventory_stock_movements'));

        $manager = app(ModuleManager::class);
        $install = $manager->install('inventory');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
            'Explicit install must own and report Inventory migrations',
        );

        $this->assertTrue(Schema::hasTable('inventory_items'));
        $this->assertTrue(Schema::hasTable('inventory_stock_movements'));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertTrue(app()->bound(StockContract::class));
        $this->assertInstanceOf(StockService::class, app(StockContract::class));

        // StockContract + HTTP / views.
        $stock = app(StockContract::class);
        app(StockService::class)->createItem('SKU-COMP', 'Compliance Widget', 'ea');
        $this->assertSame(5, $stock->adjust('SKU-COMP', 5, 'recv compliance'));
        $this->assertSame(3, $stock->adjust('SKU-COMP', -2, 'issue compliance'));
        $this->assertSame(3, $stock->onHand('SKU-COMP'));
        $this->assertSame(2, StockMovement::query()->count());

        $this->get('/inventory/items')->assertOk();
        $this->get('/inventory/items/create')->assertOk();
        $this->get('/inventory/items/SKU-COMP/edit')->assertOk();

        // Runtime contributions while enabled.
        $permIds = array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('inventory'),
        );
        $this->assertEqualsCanonicalizing([
            'inventory.items.view',
            'inventory.items.manage',
            'inventory.stock.adjust',
        ], $permIds);

        $nav = collect(app(NavigationRegistryContract::class)->items())
            ->first(fn ($i) => $i->id === 'inventory.items');
        $this->assertNotNull($nav);
        $this->assertSame('/inventory/items', $nav->route);

        $widgets = app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats');
        $this->assertTrue(collect($widgets)->contains(fn ($w) => $w->id === 'inventory.low-stock'));

        // Domain invariants remain enforced after install.
        app(StockService::class)->updateItem('SKU-COMP', 'Renamed Widget', 'ea', true);
        $this->assertSame('SKU-COMP', $stock->find('SKU-COMP')?->sku);
        $this->assertFalse($stock->exists('HACKED'));

        try {
            $stock->adjust('SKU-COMP', -99, 'would go negative');
            $this->fail('Expected negative stock rejection');
        } catch (InvalidStockAdjustmentException) {
            //
        }
        $this->assertSame(3, $stock->onHand('SKU-COMP'));
        $this->assertSame(2, StockMovement::query()->count());

        // Disable clears runtime contributions; data + movements survive.
        $disable = $manager->disable('inventory');
        $this->assertTrue($disable['success'], implode(' | ', $disable['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('inventory'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'inventory'),
        );
        $this->assertFalse(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->moduleCode === 'inventory'),
        );

        $this->assertTrue(Schema::hasTable('inventory_items'));
        $this->assertTrue(Schema::hasTable('inventory_stock_movements'));
        $this->assertDatabaseHas('inventory_items', [
            'sku' => 'SKU-COMP',
            'name' => 'Renamed Widget',
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'recv compliance', 'quantity_after' => 5]);
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'issue compliance', 'quantity_after' => 3]);

        // Re-enable restores capability + contributions; data intact.
        $enable = $manager->enable('inventory');
        $this->assertTrue($enable['success'], implode(' | ', $enable['messages']));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertSame(3, app(StockContract::class)->onHand('SKU-COMP'));
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('inventory'));
        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'inventory.items'),
        );
        $this->assertTrue(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->id === 'inventory.low-stock'),
        );
        $this->assertSame(2, StockMovement::query()->count());
        $this->get('/inventory/items')->assertOk();

        $this->assertSame(
            $hashesBefore,
            $this->hashWatchedHostFiles(),
            'Install/disable/enable must not modify unrelated host source files',
        );
    }

    public function test_migration_failure_is_fail_closed(): void
    {
        Schema::create('inventory_items', function ($table) {
            $table->id();
            $table->string('sku')->unique();
        });

        $hashesBefore = $this->hashWatchedHostFiles();

        $result = app(ModuleManager::class)->install('inventory');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertFalse(app()->bound(StockContract::class));
        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    public function test_inventory_requires_no_identity_rbac_or_settings(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(base_path('Modules/Inventory/module.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame([], $manifest['requires']['capabilities'] ?? []);
        $this->assertSame(['inventory.stock'], $manifest['provides']);
        $this->assertSame('business', $manifest['type']);

        $this->installInventory();
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('identity.user'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('authorization.permission'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('settings.runtime'));
    }

    /**
     * @return array<string, string>
     */
    private function hashWatchedHostFiles(): array
    {
        $hashes = [];

        foreach ($this->watchedHostFiles as $relative) {
            $path = base_path($relative);
            $this->assertFileExists($path);
            $hashes[$relative] = hash_file('sha256', $path);
        }

        return $hashes;
    }
}
