<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

use App\Foundation\SDK\ModuleManifest;
use App\Foundation\SDK\ModuleState;

/**
 * Contract for the module state registry.
 *
 * Manages persistent module lifecycle state (installed, enabled, disabled).
 */
interface ModuleRegistrarContract
{
    /**
     * Get the state of a module by code.
     */
    public function getState(string $code): ?ModuleState;

    /**
     * Set the state of a module.
     */
    public function setState(string $code, ModuleState $state): void;

    /**
     * Get all module states.
     *
     * @return array<string, ModuleState>
     */
    public function all(): array;

    /**
     * Check if a module is installed (installed, enabled, or disabled).
     */
    public function isInstalled(string $code): bool;

    /**
     * Check if a module is enabled.
     */
    public function isEnabled(string $code): bool;

    /**
     * Remove a module's state record entirely.
     */
    public function forget(string $code): void;
}
