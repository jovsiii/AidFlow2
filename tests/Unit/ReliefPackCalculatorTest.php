<?php

use App\Services\ReliefPackCalculator;

function reliefPackConfiguration(): array
{
    return require dirname(__DIR__, 2).'/config/relief_packs.php';
}

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
    $configuration = reliefPackConfiguration();
    $calculator = new ReliefPackCalculator($configuration['required_items'], $configuration['threshold']);

    expect($calculator->count(completeReliefPackItems()))->toBe(1);
});

it('counts complete packs using the configured pack contents', function () {
    $calculator = new ReliefPackCalculator([
        'Rice' => 6,
        'Canned Tuna' => 5,
    ]);

    $items = [
        ['name' => 'Rice', 'stock' => 12],
        ['name' => 'Canned Tuna', 'stock' => 10],
    ];

    expect($calculator->count($items))->toBe(2);
});

it('caps the standard relief-pack count at 300', function () {
    $configuration = reliefPackConfiguration();
    $calculator = new ReliefPackCalculator($configuration['required_items'], $configuration['threshold']);
    $items = collect(range(1, 301))->map(fn () => [
        'type' => 'standard',
    ])->all();

    expect($calculator->count($items))->toBe(300);
});

it('exposes the configured standard relief-pack threshold', function () {
    $configuration = reliefPackConfiguration();

    expect((new ReliefPackCalculator($configuration['required_items'], 250))->threshold())->toBe(250);
});

it('does not count an incomplete item group as a pack', function () {
    $configuration = reliefPackConfiguration();
    $calculator = new ReliefPackCalculator($configuration['required_items'], $configuration['threshold']);
    $items = completeReliefPackItems();
    array_pop($items);

    expect($calculator->count($items))->toBe(0);
});