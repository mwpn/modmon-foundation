<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Unit;

use PHPUnit\Framework\TestCase;

class WorkspaceProviderCompatibilityTest extends TestCase
{
    public function test_provider_guards_active_workspace_binding_with_interface_exists(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'WorkspaceServiceProvider.php',
        );

        $this->assertStringContainsString('interface_exists', $source);
        $this->assertStringContainsString(
            'App\\Foundation\\SDK\\Contracts\\ActiveWorkspaceContract',
            $source,
        );
        $this->assertStringNotContainsString(
            'use App\\Foundation\\SDK\\Contracts\\ActiveWorkspaceContract;',
            $source,
            'Eager use-import would autoload-fatal on hosts without the contract when contributions reference related classes.',
        );
    }

    public function test_navigation_does_not_eager_load_context_active_workspace_class(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'WorkspaceServiceProvider.php',
        );

        $this->assertStringNotContainsString(
            'ContextActiveWorkspace::WORKSPACE_',
            $source,
        );
        $this->assertStringContainsString("'workspace.owner'", $source);
        $this->assertStringContainsString("'workspace.tenant'", $source);
    }
}
