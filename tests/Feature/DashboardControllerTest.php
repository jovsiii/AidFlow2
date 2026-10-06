<?php

use App\Http\Controllers\Features\DashboardController;
use App\Services\FirebaseService;

it('passes occupancy counts and barangay summary from occupied tents', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->andReturn([]);
    $firebase->shouldReceive('getOccupiedTents')->andReturn([
        ['tent_code' => 'BB-001', 'barangay_code' => 'BB'],
        ['tent_code' => 'BB-002', 'barangay_code' => 'BB'],
        ['tent_code' => 'WC-001', 'barangay_code' => 'WC'],
    ]);

    $controller = new DashboardController();
    $response = $controller->index($firebase);

    expect($response->getData()['occupiedTentsCount'])->toBe(3)
        ->and($response->getData()['barangayCount'])->toBe(2)
        ->and($response->getData()['occupancyData'])->toBe([
                'BB' => 2,
                'WC' => 1,
            ])
        ->and($response->getData()['forecastFfp'])->toBe(664.4)
        ->and($response->getData()['forecastFamilyHeads'])->toBe(318);
});
