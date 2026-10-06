<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ReliefPackCalculator
{
    private const STANDARD_PACK_THRESHOLD = 300;

    private const REQUIRED_ITEMS = [
        'Toothbrush' => 5,
        'Toothpaste' => 2,
        'Shampoo' => 1,
        'Bath Bar Soap' => 4,
        'Laundry Bar Soap' => 2000,
        'Sanitary Napkin' => 4,
        'Comb' => 1,
        'Disposable Shaving Razor' => 1,
        'Nail Cutter' => 1,
        'Bathroom Dipper' => 1,
        '20L Square Plastic Bucket with Deep Cover and Plastic Handle' => 1,
        'Blanket' => 1,
        'Mosquito Net' => 1,
        'Mat' => 1,
        'Kitchen Utensils' => 1,
        'Rice' => 6,
        'Canned Sardines' => 5,
        'Canned Tuna' => 5,
        'Canned Beef Loaf' => 5,
        'Coffee or Energy Drinks' => 5,
    ];

    public function requiredItems(): array
    {
        return self::REQUIRED_ITEMS;
    }

    public function threshold(): int
    {
        return self::STANDARD_PACK_THRESHOLD;
    }

    public function count($inventory): int
    {
        $inventory = collect($inventory);
        $standardPacks = $inventory->filter(fn ($item) => is_array($item) && ($item['type'] ?? '') === 'standard')->count();
        $stockByName = $inventory
            ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null))
            ->groupBy(fn ($item) => mb_strtolower(trim($item['name'])))
            ->map(fn (Collection $items) => $items->sum(fn ($item) => (int) ($item['stock'] ?? 0)));

        $completePacks = collect(self::REQUIRED_ITEMS)
            ->map(fn ($requiredQuantity, $itemName) => intdiv(
                (int) ($stockByName->get(mb_strtolower($itemName), 0)),
                $requiredQuantity
            ))
            ->min() ?? 0;

        return min(self::STANDARD_PACK_THRESHOLD, $standardPacks + $completePacks);
    }
}