<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Experience\Navigation\NavigationRegistry;
use App\Foundation\SDK\DTOs\NavigationItem;
use PHPUnit\Framework\TestCase;

class NavigationRegistryTest extends TestCase
{
    private NavigationRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new NavigationRegistry();
    }

    public function test_register_and_retrieve_items(): void
    {
        $item = new NavigationItem(
            id: 'test.nav',
            moduleCode: 'test',
            label: 'Test',
            route: '/test',
        );

        $this->registry->register($item);
        $items = $this->registry->items();
        $this->assertCount(1, $items);
        $this->assertEquals('test.nav', $items[0]->id);
    }

    public function test_remove_by_module_clears_only_target(): void
    {
        $this->registry->register(new NavigationItem('a.nav', 'mod-a', 'A', '/a'));
        $this->registry->register(new NavigationItem('b.nav', 'mod-b', 'B', '/b'));

        $this->registry->removeByModule('mod-a');

        $items = $this->registry->items();
        $this->assertCount(1, $items);
        $this->assertEquals('b.nav', $items[0]->id);
    }

    public function test_items_sorted_by_order(): void
    {
        $this->registry->register(new NavigationItem('b', 'mod', 'B', '/b', order: 200));
        $this->registry->register(new NavigationItem('a', 'mod', 'A', '/a', order: 10));

        $items = $this->registry->items();
        $this->assertEquals('a', $items[0]->id);
        $this->assertEquals('b', $items[1]->id);
    }

    public function test_filter_by_workspace(): void
    {
        $this->registry->register(new NavigationItem('owner', 'mod', 'Owner', '/o', workspace: 'workspace.owner'));
        $this->registry->register(new NavigationItem('tenant', 'mod', 'Tenant', '/t', workspace: 'workspace.tenant'));
        $this->registry->register(new NavigationItem('global', 'mod', 'Global', '/g', workspace: null));

        $ownerItems = $this->registry->items('workspace.owner');
        $this->assertCount(2, $ownerItems); // owner + global (null workspace)
    }

    public function test_grouped_returns_items_by_group(): void
    {
        $this->registry->register(new NavigationItem('a', 'mod', 'A', '/a', group: 'Main'));
        $this->registry->register(new NavigationItem('b', 'mod', 'B', '/b', group: 'Main'));
        $this->registry->register(new NavigationItem('c', 'mod', 'C', '/c', group: 'Settings'));

        $groups = $this->registry->grouped();
        $this->assertArrayHasKey('Main', $groups);
        $this->assertArrayHasKey('Settings', $groups);
        $this->assertCount(2, $groups['Main']);
        $this->assertCount(1, $groups['Settings']);
    }
}
