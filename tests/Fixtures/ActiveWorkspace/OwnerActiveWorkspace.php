<?php

declare(strict_types=1);

namespace Tests\Fixtures\ActiveWorkspace;

use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;

/**
 * Test-only active workspace: workspace.owner.
 */
final class OwnerActiveWorkspace implements ActiveWorkspaceContract
{
    public function current(): string
    {
        return 'workspace.owner';
    }
}
