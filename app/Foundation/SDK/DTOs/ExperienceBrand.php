<?php

declare(strict_types=1);

namespace App\Foundation\SDK\DTOs;

/**
 * The brand an Experience shell renders for the current request (Contract
 * 1.4.0, ADR-0012). Built in layers: every field remembers the level it was
 * set at and is replaced only from the same or a higher level, so module
 * decorators may register in any order.
 */
final readonly class ExperienceBrand
{
    public const LEVEL_DEFAULT = 0;

    public const LEVEL_APPLICATION = 10;

    public const LEVEL_TENANT = 20;

    public const LEVEL_TENANT_OVERRIDE = 30;

    public const FIELDS = ['name', 'logoUrl', 'logoDarkUrl', 'faviconUrl', 'primaryColor', 'headline', 'tagline', 'markStyle', 'shortName'];

    /** How the app shell shows the brand next to the menu: logo and name, logo only, or name only. */
    public const MARK_STYLES = ['logo_name', 'logo', 'name'];

    /**
     * @param  array<string, int>  $levels  field => level it was set at
     */
    public function __construct(
        public string $name,
        public ?string $logoUrl = null,
        public ?string $logoDarkUrl = null,
        public ?string $faviconUrl = null,
        public ?string $primaryColor = null,
        public ?string $headline = null,
        public ?string $tagline = null,
        public ?string $markStyle = null,
        /** Shorter name for the app shell's sidebar and phone header. */
        public ?string $shortName = null,
        public ?int $tenantId = null,
        public array $levels = [],
    ) {}

    /**
     * A copy with the given non-empty fields applied at `$level`. Fields set
     * earlier at a higher level are kept. `$tenantId` marks the brand as
     * tenant-specific.
     *
     * @param  array<string, ?string>  $fields
     */
    public function overlay(array $fields, int $level, ?int $tenantId = null): self
    {
        $values = [];
        $levels = $this->levels;

        foreach (self::FIELDS as $field) {
            $values[$field] = $this->{$field};
            $value = $fields[$field] ?? null;

            if ($value === null || $value === '' || ($levels[$field] ?? self::LEVEL_DEFAULT) > $level) {
                continue;
            }

            if ($field === 'markStyle' && ! in_array($value, self::MARK_STYLES, true)) {
                continue;
            }

            if ($field === 'primaryColor') {
                if (! preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    continue;
                }
                $value = strtolower($value);
            }

            $values[$field] = $value;
            $levels[$field] = $level;
        }

        return new self(...$values, tenantId: $tenantId ?? $this->tenantId, levels: $levels);
    }

    /**
     * Whether the app shell shows the logo / the name. A logo-only style
     * without a logo falls back to logo tile and name.
     */
    public function showsLogo(): bool
    {
        return $this->markStyle !== 'name';
    }

    public function showsName(): bool
    {
        return $this->markStyle !== 'logo' || $this->logoUrl === null;
    }

    /**
     * The name next to the menu: the short name when set.
     */
    public function panelName(): string
    {
        return $this->shortName ?? $this->name;
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
