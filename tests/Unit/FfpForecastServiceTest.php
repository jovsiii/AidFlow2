<?php

use App\Services\FfpForecastService;

it('calculates tiered pack forecasts from reported evacuee counts', function () {
    $modelPath = __DIR__.'/../../public/models/ffp_model.json';
    $service = new FfpForecastService($modelPath);

    $result = $service->predict(
        barangayCounts: [
            'Batis' => 1000,
            'Salapan' => 500,
        ],
        unit: 'individuals',
    );

    expect($result['totals']['evacuees'])->toBe(1500)
        ->and($result['totals']['FFP_low'])->toBe(511.5)
        ->and($result['totals']['FFP_expected'])->toBe(799.5)
        ->and($result['totals']['FFP_high'])->toBe(1704.0)
        ->and($result['totals']['FFP_high'])->toBeGreaterThan($result['totals']['FFP_low']);
});

it('uses the model household size when families are supplied', function () {
    $service = new FfpForecastService(__DIR__.'/../../public/models/ffp_model.json');

    $result = $service->predict(
        barangayCounts: ['Batis' => 100],
        unit: 'families',
    );

    expect($result['totals']['evacuees'])->toBe(392)
        ->and($result['totals']['FFP_expected'])->toBeGreaterThan(0);
});

it('calculates the two requested family forecast outputs', function () {
    $service = new FfpForecastService(__DIR__.'/../../public/models/ffp_model.json');

    $result = $service->predictFamilyOutputs(predictedFamilyHeads: 318);

    expect($result)->toBe([
        'predictedFamilyHeads' => 318,
        'estimatedFamilyFoodPacksNeeded' => 664.4,
    ]);
});
