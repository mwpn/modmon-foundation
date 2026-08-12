<?php

declare(strict_types=1);

namespace App\Foundation\SDK;

/**
 * Represents the lifecycle state of a module.
 */
enum ModuleState: string
{
    case Discovered = 'discovered';
    case Installed  = 'installed';
    case Enabled    = 'enabled';
    case Disabled   = 'disabled';
}
