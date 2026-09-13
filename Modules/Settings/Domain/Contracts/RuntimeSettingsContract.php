<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\Contracts;

/**
 * Public runtime settings store for capability `settings.runtime`.
 *
 * Keys are non-empty strings (namespaced by convention as
 * `{owner}.{group}.{name}`). Values must be JSON-serializable scalars
 * or arrays — no PHP objects.
 */
interface RuntimeSettingsContract
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    /**
     * @return array<string, mixed>
     */
    public function all(?string $prefix = null): array;
}
