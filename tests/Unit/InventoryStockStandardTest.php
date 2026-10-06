<?php

use App\Services\InventoryStockStandard;

it('defines the supplied maximum stock standard for every item', function () {
    $standards = new InventoryStockStandard();

    expect($standards->maximumStock(['name' => 'Toothbrush']))->toBe(1500)
        ->and($standards->maximumStock(['name' => 'Laundry Bar Soap']))->toBe(300)
        ->and($standards->maximumStock(['name' => 'Rice']))->toBe(1800)
        ->and($standards->maximumStock(['name' => 'Coffee or Energy Drinks']))->toBe(1500)
        ->and($standards->maximumStock(['name' => 'Unknown Item']))->toBeNull();
});

it('flags stock below the item-specific maximum standard', function () {
    $standards = new InventoryStockStandard();

    expect($standards->isLowStock(['name' => 'Toothbrush', 'stock' => 1499]))->toBeTrue()
        ->and($standards->isLowStock(['name' => 'Toothbrush', 'stock' => 1500]))->toBeFalse()
        ->and($standards->isLowStock(['name' => 'Rice', 'stock' => 1799]))->toBeTrue()
        ->and($standards->isLowStock(['name' => 'Unknown Item', 'stock' => 0]))->toBeFalse();
});
