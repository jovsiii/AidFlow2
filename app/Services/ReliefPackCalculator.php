<?php

namespace App\Services;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;

class ReliefPackCalculator
{
    private array $requiredItems;

    private int $threshold;

    public function __construct(?array $requiredItems = null, ?int $threshold = null)
    {
        $container = Container::getInstance();
        $configuration = function_exists('config') && $container->bound('config')
            ? config('relief_packs', [])
            : require dirname(__DIR__, 2).'/config/relief_packs.php';

        $this->requiredItems = $requiredItems ?? $configuration['required_items'] ?? [];
        $this->threshold = $threshold ?? (int) ($configuration['threshold'] ?? 300);
    }

    public function requiredItems(): array
    {
        return $this->requiredItems;
    }

    public function threshold(): int
    {
        return $this->threshold;
    }

    public function count($inventory): int
    {
        $inventory = collect($inventory);
        $standardPacks = $inventory->filter(fn ($item) => is_array($item) && ($item['type'] ?? '') === 'standard')->count();
        $stockByName = $inventory
            ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null))
            ->groupBy(fn ($item) => mb_strtolower(trim($item['name'])))
            ->map(fn (Collection $items) => $items->sum(fn ($item) => (int) ($item['stock'] ?? 0)));

        $completePacks = collect($this->requiredItems)
            ->map(fn ($requiredQuantity, $itemName) => intdiv(
                (int) ($stockByName->get(mb_strtolower($itemName), 0)),
                $requiredQuantity
            ))
            ->min() ?? 0;

        return min($this->threshold, max($standardPacks, $completePacks));
    }
}