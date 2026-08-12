<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;

/**
 * In-memory capability registry.
 *
 * Tracks which capabilities are provided by which enabled modules.
 */
class CapabilityRegistry implements CapabilityRegistryContract
{
    /**
     * @var array<string, string> capability => moduleCode
     */
    private array $capabilities = [];

    public function registerProvider(string $moduleCode, array $capabilities): void
    {
        foreach ($capabilities as $capability) {
            $this->capabilities[$capability] = $moduleCode;
        }
    }

    public function unregisterProvider(string $moduleCode): void
    {
        $this->capabilities = array_filter(
            $this->capabilities,
            fn (string $provider) => $provider !== $moduleCode,
        );
    }

    public function has(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    public function available(): array
    {
        return array_keys($this->capabilities);
    }

    public function provider(string $capability): ?string
    {
        return $this->capabilities[$capability] ?? null;
    }

    public function missing(array $required): array
    {
        return array_values(array_filter(
            $required,
            fn (string $cap) => ! $this->has($cap),
        ));
    }
}
