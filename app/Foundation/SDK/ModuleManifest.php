<?php

declare(strict_types=1);

namespace App\Foundation\SDK;

/**
 * Immutable value object representing a validated module.json manifest.
 */
final readonly class ModuleManifest
{
    /**
     * @param string $name          Human-readable module name
     * @param string $code          Unique machine identifier (lowercase, no spaces)
     * @param string $version       Semantic version string
     * @param string $type          Module category: platform|business|integration
     * @param string $provider      Fully-qualified service provider class
     * @param array  $compatibility Compatibility requirements (php, laravel, foundation)
     * @param array  $requires      Required capabilities
     * @param array  $provides      Provided capabilities
     * @param int    $schema        Manifest schema version
     * @param string $path          Absolute path to module directory
     */
    public function __construct(
        public string $name,
        public string $code,
        public string $version,
        public string $type,
        public string $provider,
        public array  $compatibility,
        public array  $requires,
        public array  $provides,
        public int    $schema = 1,
        public string $path = '',
    ) {}

    /**
     * Create from decoded module.json array.
     */
    public static function fromArray(array $data, string $path = ''): self
    {
        return new self(
            name: $data['name'],
            code: $data['code'],
            version: $data['version'],
            type: $data['type'] ?? 'business',
            provider: $data['provider'],
            compatibility: $data['compatibility'] ?? [],
            requires: $data['requires']['capabilities'] ?? [],
            provides: $data['provides'] ?? [],
            schema: $data['schema'] ?? 1,
            path: $path,
        );
    }

    /**
     * Get the required PHP version constraint.
     */
    public function phpConstraint(): ?string
    {
        return $this->compatibility['php'] ?? null;
    }

    /**
     * Get the required Laravel version constraint.
     */
    public function laravelConstraint(): ?string
    {
        return $this->compatibility['laravel'] ?? null;
    }

    /**
     * Get the required Foundation Contract version constraint.
     */
    public function foundationConstraint(): ?string
    {
        return $this->compatibility['foundation'] ?? null;
    }
}
