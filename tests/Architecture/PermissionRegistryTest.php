<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Experience\PermissionRegistry;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use PHPUnit\Framework\TestCase;

class PermissionRegistryTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PermissionRegistry();
    }

    public function test_register_and_retrieve_permissions(): void
    {
        $perms = [
            new PermissionDefinition('test.view', 'test', 'View Test'),
            new PermissionDefinition('test.manage', 'test', 'Manage Test'),
        ];

        $this->registry->register('test', $perms);

        $all = $this->registry->all();
        $this->assertCount(2, $all);
    }

    public function test_for_module(): void
    {
        $this->registry->register('mod-a', [
            new PermissionDefinition('a.view', 'mod-a', 'View A'),
        ]);
        $this->registry->register('mod-b', [
            new PermissionDefinition('b.view', 'mod-b', 'View B'),
        ]);

        $perms = $this->registry->forModule('mod-a');
        $this->assertCount(1, $perms);
        $this->assertEquals('a.view', $perms[0]->id);
    }

    public function test_remove_by_module(): void
    {
        $this->registry->register('mod-a', [
            new PermissionDefinition('a.view', 'mod-a', 'View A'),
        ]);
        $this->registry->register('mod-b', [
            new PermissionDefinition('b.view', 'mod-b', 'View B'),
        ]);

        $this->registry->removeByModule('mod-a');

        $all = $this->registry->all();
        $this->assertCount(1, $all);
        $this->assertEquals('b.view', $all[0]->id);
    }

    public function test_grouped_by_module(): void
    {
        $this->registry->register('mod-a', [
            new PermissionDefinition('a.view', 'mod-a', 'View A'),
            new PermissionDefinition('a.manage', 'mod-a', 'Manage A'),
        ]);

        $grouped = $this->registry->groupedByModule();
        $this->assertArrayHasKey('mod-a', $grouped);
        $this->assertCount(2, $grouped['mod-a']);
        $this->assertContains('a.view', $grouped['mod-a']);
    }
}
