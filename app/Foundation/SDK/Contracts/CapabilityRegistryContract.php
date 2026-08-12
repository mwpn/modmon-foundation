<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

/**
 * Contract for the capability registry.
 *
 * Capabilities are semantic identifiers (dot-notation, e.g. "identity.user")
 * that express what functionality a module provides or requires.
 */
interface CapabilityRegistryContract
{
    /**
     * Register capabilities provided by a module.
     *
     * @param string   $moduleCode
     * @param string[] $capabilities
     */
    public function registerProvider(string $moduleCode, array $capabilities): void;

    /**
     * Remove all capabilities provided by a module.
     */
    public function unregisterProvider(string $moduleCode): void;

    /**
     * Check if a capability is currently available (provided by an enabled module).
     */
    public function has(string $capability): bool;

    /**
     * Get all currently available capabilities.
     *
     * @return string[]
     */
    public function available(): array;

    /**
     * Get the module code that provides a given capability.
     */
    public function provider(string $capability): ?string;

    /**
     * Check if all required capabilities are satisfied.
     *
     * @param string[] $required
     * @return string[] Missing capabilities
     */
    public function missing(array $required): array;
}
