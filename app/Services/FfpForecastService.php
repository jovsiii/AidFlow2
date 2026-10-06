<?php

namespace App\Services;

use InvalidArgumentException;

class FfpForecastService
{
    private array $model;

    public function __construct(string $modelPath)
    {
        $contents = file_get_contents($modelPath);

        if ($contents === false) {
            throw new InvalidArgumentException("Unable to read FFP model: {$modelPath}");
        }

        try {
            $this->model = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('Invalid FFP model JSON.', 0, $exception);
        }
    }

    /**
     * Calculate tiered FFP requirements from reported barangay evacuee counts.
     *
     * @param  array<string, int|float>  $barangayCounts
     * @return array<string, mixed>
     */
    public function predict(
        array $barangayCounts,
        string $unit = 'individuals',
        string $tier = 'expected',
        ?string $event = null,
    ): array {
        $unit = strtolower($unit);
        $tier = strtolower($tier);

        if (! in_array($unit, ['individuals', 'families'], true)) {
            throw new InvalidArgumentException('FPP forecast unit must be individuals or families.');
        }

        if (! in_array($tier, ['lean', 'expected', 'high'], true)) {
            throw new InvalidArgumentException('FFP forecast tier must be lean, expected, or high.');
        }

        $householdSize = (float) ($this->model['avg_household_size'] ?? 0);
        if ($householdSize <= 0) {
            throw new InvalidArgumentException('The FFP model does not define a valid average household size.');
        }

        $ratios = $this->model['ffp_per_person'] ?? [];
        $eventRatios = $this->model['ffp_per_person_by_event'] ?? [];
        $ratio = $event !== null && isset($eventRatios[$event])
            ? (float) $eventRatios[$event]
            : (float) ($ratios[$tier] ?? $ratios['expected'] ?? 0);

        $perBarangay = [];
        $totalEvacuees = 0.0;
        $totalFfp = [
            'FFP_low' => 0.0,
            'FFP_expected' => 0.0,
            'FFP_high' => 0.0,
        ];

        foreach ($barangayCounts as $barangay => $count) {
            $count = (float) $count;
            if ($count < 0) {
                throw new InvalidArgumentException("Barangay count for {$barangay} cannot be negative.");
            }

            $evacuees = $unit === 'families' ? $count * $householdSize : $count;
            $packCount = $evacuees * $ratio;
            $totalEvacuees += $evacuees;
            $perBarangay[$barangay] = [
                'evacuees' => $evacuees,
                'FFP' => $packCount,
                'tier' => $tier,
            ];

            $totalFfp['FFP_low'] += $evacuees * (float) ($ratios['lean'] ?? $ratio);
            $totalFfp['FFP_expected'] += $evacuees * (float) ($ratios['expected'] ?? $ratio);
            $totalFfp['FFP_high'] += $evacuees * (float) ($ratios['high'] ?? $ratio);
        }

        return [
            'unit' => $unit,
            'tier' => $tier,
            'event' => $event,
            'perBarangay' => $perBarangay,
            'totals' => [
                'evacuees' => (int) $totalEvacuees,
                'FFP_low' => round($totalFfp['FFP_low'], 1),
                'FFP_expected' => round($totalFfp['FFP_expected'], 1),
                'FFP_high' => round($totalFfp['FFP_high'], 1),
            ],
            'model' => [
                'avg_household_size' => $householdSize,
                'ratio' => $ratio,
            ],
        ];
    }

    /**
     * Return only the two requested family forecast values.
     *
     * @return array{predictedFamilyHeads: int, estimatedFamilyFoodPacksNeeded: float}
     */
    public function predictFamilyOutputs(int $predictedFamilyHeads): array
    {
        $householdSize = (float) ($this->model['avg_household_size'] ?? 0);
        $expectedRatio = (float) ($this->model['ffp_per_person']['expected'] ?? 0);

        if ($householdSize <= 0 || $expectedRatio <= 0) {
            throw new InvalidArgumentException('The FFP model does not define valid family forecast parameters.');
        }

        $predictedIndividuals = $predictedFamilyHeads * $householdSize;

        return [
            'predictedFamilyHeads' => $predictedFamilyHeads,
            'estimatedFamilyFoodPacksNeeded' => round($predictedIndividuals * $expectedRatio, 1),
        ];
    }
}
