<?php

declare(strict_types=1);

namespace Modules\Settings\Application\Services;

use InvalidArgumentException;
use Modules\Settings\Domain\Contracts\RuntimeSettingsContract;
use Modules\Settings\Domain\Models\SettingEntry;

/**
 * Database-backed RuntimeSettingsContract.
 *
 * No caching, tenancy, encryption, feature flags, or schema language.
 */
final class DatabaseRuntimeSettings implements RuntimeSettingsContract
{
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->normalizeKey($key);

        $entry = SettingEntry::query()->where('key', $key)->first();

        if ($entry === null) {
            return $default;
        }

        return $entry->value;
    }

    public function set(string $key, mixed $value): void
    {
        $key = $this->normalizeKey($key);
        $this->assertJsonSerializable($value);

        SettingEntry::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    public function has(string $key): bool
    {
        $key = $this->normalizeKey($key);

        return SettingEntry::query()->where('key', $key)->exists();
    }

    public function forget(string $key): void
    {
        $key = $this->normalizeKey($key);

        SettingEntry::query()->where('key', $key)->delete();
    }

    public function all(?string $prefix = null): array
    {
        // Filter in PHP so prefix characters (%, _, \) are literal —
        // never SQL LIKE wildcards.
        $normalizedPrefix = null;
        if ($prefix !== null && $prefix !== '') {
            $normalizedPrefix = $this->normalizeKey($prefix);
        }

        $result = [];

        foreach (SettingEntry::query()->orderBy('key')->get(['key', 'value']) as $entry) {
            if ($normalizedPrefix !== null
                && $entry->key !== $normalizedPrefix
                && ! str_starts_with($entry->key, $normalizedPrefix.'.')) {
                continue;
            }

            $result[$entry->key] = $entry->value;
        }

        return $result;
    }

    private function normalizeKey(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('Settings key must be a non-empty string.');
        }

        return $key;
    }

    private function assertJsonSerializable(mixed $value): void
    {
        if ($this->containsDisallowed($value)) {
            throw new InvalidArgumentException(
                'Settings values must be JSON-serializable scalars or arrays; objects are not allowed.',
            );
        }

        if (json_encode($value) === false) {
            throw new InvalidArgumentException(
                'Settings values must be JSON-serializable scalars or arrays; objects are not allowed.',
            );
        }
    }

    private function containsDisallowed(mixed $value): bool
    {
        if (is_resource($value) || $value instanceof \Closure || is_object($value)) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if ($this->containsDisallowed($item)) {
                return true;
            }
        }

        return false;
    }
}
