<?php

use App\Services\ReliefPackCalculator;

function completeReliefPackItems(): array
{
    return [
        ['name' => 'Toothbrush', 'stock' => 5],
        ['name' => 'Toothpaste', 'stock' => 2],
        ['name' => 'Shampoo', 'stock' => 1],
        ['name' => 'Bath Bar Soap', 'stock' => 4],
        ['name' => 'Laundry Bar Soap', 'stock' => 2000],
        ['name' => 'Sanitary Napkin', 'stock' => 4],
        ['name' => 'Comb', 'stock' => 1],
        ['name' => 'Disposable Shaving Razor', 'stock' => 1],
        ['name' => 'Nail Cutter', 'stock' => 1],
        ['name' => 'Bathroom Dipper', 'stock' => 1],
        ['name' => '20L Square Plastic Bucket with Deep Cover and Plastic Handle', 'stock' => 1],
        ['name' => 'Blanket', 'stock' => 1],
        ['name' => 'Mosquito Net', 'stock' => 1],
        ['name' => 'Mat', 'stock' => 1],
        ['name' => 'Kitchen Utensils', 'stock' => 1],
        ['name' => 'Rice', 'stock' => 6],
        ['name' => 'Canned Sardines', 'stock' => 5],
        ['name' => 'Canned Tuna', 'stock' => 5],
        ['name' => 'Canned Beef Loaf', 'stock' => 5],
        ['name' => 'Coffee or Energy Drinks', 'stock' => 5],
    ];
}

it('counts one pack for one complete standard item group', function () {
    expect((new ReliefPackCalculator())->count(completeReliefPackItems()))->toBe(1);
});

it('caps the standard relief-pack count at 300', function () {
    $items = collect(range(1, 301))->map(fn () => [
        'type' => 'standard',
    ])->all();

    expect((new ReliefPackCalculator())->count($items))->toBe(300);
});

it('exposes the standard relief-pack threshold', function () {
    expect((new ReliefPackCalculator())->threshold())->toBe(300);
});

it('does not count an incomplete item group as a pack', function () {
    $items = completeReliefPackItems();
    array_pop($items);

    expect((new ReliefPackCalculator())->count($items))->toBe(0);
});