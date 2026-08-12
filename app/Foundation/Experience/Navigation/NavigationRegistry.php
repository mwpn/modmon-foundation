<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Navigation;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;

/**
 * In-memory navigation registry.
 *
 * Modules contribute navigation items. The shell renders them sorted by order.
 */
class NavigationRegistry implements NavigationRegistryContract
{
    /** @var NavigationItem[] */
    private array $items = [];

    public function register(NavigationItem $item): void
    {
        $this->items[$item->id] = $item;
    }

    public function removeByModule(string $moduleCode): void
    {
        $this->items = array_filter(
            $this->items,
            fn (NavigationItem $item) => $item->moduleCode !== $moduleCode,
        );
    }

    public function items(?string $workspace = null): array
    {
        $items = $workspace !== null
            ? array_filter($this->items, fn (NavigationItem $i) => $i->workspace === $workspace || $i->workspace === null)
            : $this->items;

        $sorted = array_values($items);
        usort($sorted, fn (NavigationItem $a, NavigationItem $b) => $a->order <=> $b->order);

        return $sorted;
    }

    public function grouped(?string $workspace = null): array
    {
        $groups = [];
        foreach ($this->items($workspace) as $item) {
            $group = $item->group ?? '_default';
            $groups[$group][] = $item;
        }

        return $groups;
    }
}
