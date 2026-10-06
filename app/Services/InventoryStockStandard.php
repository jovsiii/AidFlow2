<?php

namespace App\Services;

class InventoryStockStandard
{
    private const MAXIMUM_STOCK = [
        'Toothbrush' => 1500,
        'Toothpaste' => 600,
        'Shampoo' => 300,
        'Bath Bar Soap' => 1200,
        'Laundry Bar Soap' => 300,
        'Sanitary Napkin' => 1200,
        'Comb' => 300,
        'Disposable Shaving Razor' => 300,
        'Nail Cutter' => 300,
        'Bathroom Dipper' => 300,
        '20L Square Plastic Bucket with Deep Cover and Plastic Handle' => 300,
        'Blanket' => 300,
        'Mosquito Net' => 300,
        'Mat' => 300,
        'Kitchen Utensils' => 300,
        'Rice' => 1800,
        'Canned Sardines' => 1500,
        'Canned Tuna' => 1500,
        'Canned Beef Loaf' => 1500,
        'Coffee or Energy Drinks' => 1500,
    ];

    public function maximumStock(array $item): ?int
    {
        $name = mb_strtolower(trim((string) ($item['name'] ?? '')));

        foreach (self::MAXIMUM_STOCK as $standardName => $maximum) {
            if (mb_strtolower(trim($standardName)) === $name) {
                return $maximum;
            }
        }

        return null;
    }

    public function isLowStock(array $item): bool
    {
        $maximum = $this->maximumStock($item);
        $stock = max(0, (int) ($item['stock'] ?? 0));

        return $maximum !== null && $stock < $maximum;
    }

    public function maximumStockFor(array $item): ?int
    {
        return $this->maximumStock($item);
    }
}
