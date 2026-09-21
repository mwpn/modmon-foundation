<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

/**
 * Resolves the active Experience workspace identifier.
 *
 * Returns a workspace id such as {@see self::DEFAULT} or
 * `workspace.owner`. Foundation does not interpret host, tenant, or
 * SaaS meaning — hosts or workspace modules bind the implementation.
 */
interface ActiveWorkspaceContract
{
    public const DEFAULT = 'workspace.default';

    /**
     * Active workspace id for the current request/context.
     */
    public function current(): string;
}
