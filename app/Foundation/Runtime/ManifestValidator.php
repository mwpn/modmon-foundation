<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\ModuleManifest;

/**
 * Validates module.json manifests against the expected schema.
 */
class ManifestValidator
{
    /**
     * Required top-level keys in module.json.
     */
    private const REQUIRED_KEYS = ['schema', 'name', 'code', 'version', 'provider'];

    /**
     * Allowed module type values.
     */
    private const ALLOWED_TYPES = ['platform', 'business', 'integration'];

    /**
     * Validate raw decoded JSON data.
     *
     * @return string[] List of validation errors (empty = valid)
     */
    public function validate(array $data, string $path = ''): array
    {
        $errors = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === null) {
                $errors[] = "Missing required field: {$key}";
            }
        }

        if (! empty($errors)) {
            return $errors;
        }

        // Schema version
        if (! is_int($data['schema']) || $data['schema'] < 1) {
            $errors[] = "Field 'schema' must be a positive integer.";
        }

        // Code format: lowercase, alphanumeric + hyphens
        if (! preg_match('/^[a-z][a-z0-9\-]*$/', $data['code'])) {
            $errors[] = "Field 'code' must be lowercase alphanumeric with hyphens, starting with a letter.";
        }

        // Version: basic semver
        if (! preg_match('/^\d+\.\d+\.\d+/', $data['version'])) {
            $errors[] = "Field 'version' must follow semantic versioning (e.g. 1.0.0).";
        }

        // Type
        $type = $data['type'] ?? 'business';
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = "Field 'type' must be one of: " . implode(', ', self::ALLOWED_TYPES) . ".";
        }

        // Provider class
        if (! is_string($data['provider']) || ! str_contains($data['provider'], '\\')) {
            $errors[] = "Field 'provider' must be a fully-qualified class name.";
        }

        // Compatibility (optional but validated if present)
        if (isset($data['compatibility'])) {
            if (! is_array($data['compatibility'])) {
                $errors[] = "Field 'compatibility' must be an object.";
            }
        }

        // Requires (optional)
        if (isset($data['requires'])) {
            if (! is_array($data['requires'])) {
                $errors[] = "Field 'requires' must be an object.";
            } elseif (isset($data['requires']['capabilities']) && ! is_array($data['requires']['capabilities'])) {
                $errors[] = "Field 'requires.capabilities' must be an array.";
            }
        }

        // Provides (optional)
        if (isset($data['provides']) && ! is_array($data['provides'])) {
            $errors[] = "Field 'provides' must be an array of capability identifiers.";
        }

        return $errors;
    }

    /**
     * Validate and create a ModuleManifest from raw data.
     *
     * @throws \InvalidArgumentException when validation fails
     */
    public function validateAndCreate(array $data, string $path = ''): ModuleManifest
    {
        $errors = $this->validate($data, $path);

        if (! empty($errors)) {
            throw new \InvalidArgumentException(
                "Invalid module.json" . ($path ? " at {$path}" : '') . ":\n- " . implode("\n- ", $errors)
            );
        }

        return ModuleManifest::fromArray($data, $path);
    }
}
