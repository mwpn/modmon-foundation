<?php

declare(strict_types=1);

namespace App\Foundation\Experience;

/**
 * The Experience icon set (Contract 1.3.0, ADR-0011): Lucide outline icons
 * (https://lucide.dev, ISC) copied into `icons/` — one file per icon with
 * its inner SVG markup, named as on lucide.dev. Rendered by
 * `<x-foundation::icon name="…" />`; navigation items may name one.
 */
final class Icons
{
    /** @var array<string, ?string> */
    private static array $cache = [];

    public static function has(string $name): bool
    {
        return self::markup($name) !== null;
    }

    /**
     * Inner SVG markup of an icon, or null for an unknown name.
     */
    public static function markup(string $name): ?string
    {
        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name)) {
            return null;
        }

        if (! array_key_exists($name, self::$cache)) {
            $file = __DIR__."/icons/{$name}.svg";
            self::$cache[$name] = is_file($file) ? trim((string) file_get_contents($file)) : null;
        }

        return self::$cache[$name];
    }
}
