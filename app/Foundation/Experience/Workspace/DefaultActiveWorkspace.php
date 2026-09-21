<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Workspace;

use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;

/**
 * Default active workspace: {@see ActiveWorkspaceContract::DEFAULT}.
 */
final class DefaultActiveWorkspace implements ActiveWorkspaceContract
{
    public function current(): string
    {
        return ActiveWorkspaceContract::DEFAULT;
    }
}
