<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\InventoryStockStandard;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;

/** Lists and maintains inventory records stored in Firebase. */
class InventoryController extends Controller
{
    /** Convert Firebase's keyed records into view-ready items while retaining each record ID. */
    private function normalizeInventoryItems($inventoryData)
    {
        return collect($inventoryData)
            ->map(function ($item, $id) {
                if (!is_array($item)) {
                    return null;
                }

                $item['id'] = $id;

                return $item;
            })
            ->filter()
            ->values();
    }

    /** Filter inventory, calculate summary metrics, and render the inventory page. */
    public function index(Request $request, FirebaseService $firebase, ?ReliefPackCalculator $reliefPackCalculator = null)
    {
        $reliefPackCalculator ??= new ReliefPackCalculator();
        $stockStandard = new InventoryStockStandard();
        $inventoryItems = $this->normalizeInventoryItems($firebase->getInventory());
        $inventoryItems = $inventoryItems->map(function ($item) use ($stockStandard) {
            $item['maximumStock'] = $stockStandard->maximumStockFor($item);
            $item['isLowStock'] = $stockStandard->isLowStock($item);

            return $item;
        });
        $batchGroups = $inventoryItems
            ->filter(fn ($item) => filled($item['batch'] ?? null))
            ->groupBy(fn ($item) => (string) $item['batch'])
            ->sortKeysUsing('strnatcasecmp');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $inventoryItems = $inventoryItems->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item['name'] ?? ''), $search)
                    || str_contains(mb_strtolower($item['category'] ?? ''), $search);
            });
        }

        if ($request->filled('category')) {
            $inventoryItems = $inventoryItems->filter(function ($item) use ($request) {
                return ($item['category'] ?? '') === $request->category;
            });
        }

        if ($request->filled('status')) {
            $today = now()->startOfDay();
            $inventoryItems = $inventoryItems->filter(function ($item) use ($request, $today) {
                $expirationDate = $item['expirationDate'] ?? null;

                if ($request->status === 'expired') {
                    return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
                }

                if ($request->status === 'near_expiry') {
                    return $expirationDate && \Carbon\Carbon::parse($expirationDate)->between($today, $today->copy()->addDays(30));
                }

                return !$expirationDate || \Carbon\Carbon::parse($expirationDate)->gt($today->copy()->addDays(30));
            });
        }

        $inventoryItems = $inventoryItems->sortBy(fn($item) => $item['name'] ?? '')->values();

        $today = now()->startOfDay();
        $totalItems = $inventoryItems->count();
        $lowStockItems = $inventoryItems->filter(fn($item) => $item['isLowStock'] ?? false)->count();
        $goodItems = $inventoryItems->filter(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return !$expirationDate || \Carbon\Carbon::parse($expirationDate)->gt($today->copy()->addDays(30));
        })->count();
        $nearExpiryItems = $inventoryItems->filter(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->between($today, $today->copy()->addDays(30));
        })->count();
        $expiredItems = $inventoryItems->filter(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
        })->count();
        $standardReliefPacks = $reliefPackCalculator->count($inventoryItems);
        $foodCount = $inventoryItems->filter(function ($item) {
            return mb_strtolower($item['category'] ?? '') === 'food';
        })->count();
        $nonFoodCount = $inventoryItems->filter(function ($item) {
            return mb_strtolower($item['category'] ?? '') !== 'food';
        })->count();

        $goodPercent = $totalItems > 0 ? round(($goodItems / $totalItems) * 100, 1) : 0;
        $nearExpiryPercent = $totalItems > 0 ? round(($nearExpiryItems / $totalItems) * 100, 1) : 0;
        $expiredPercent = $totalItems > 0 ? round(($expiredItems / $totalItems) * 100, 1) : 0;

        if ($totalItems > 0 && abs(($goodPercent + $nearExpiryPercent + $expiredPercent) - 100) > 0.1) {
            $expiredPercent = max(0, 100 - $goodPercent - $nearExpiryPercent);
        }

        return view('features.inventory.index', compact(
            'inventoryItems',
            'batchGroups',
            'totalItems',
            'lowStockItems',
            'goodItems',
            'nearExpiryItems',
            'expiredItems',
            'standardReliefPacks',
            'foodCount',
            'nonFoodCount',
            'goodPercent',
            'nearExpiryPercent',
            'expiredPercent'
        ));
    }

    /** Validate and add an inventory record. */
    public function store(Request $request, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'stock' => 'required|integer|min:0',
            'received' => 'nullable|date',
            'expirationDate' => 'nullable|date',
        ]);

        $firebase->createInventory($validated);

        return redirect()->back()->with('success', 'Item added successfully');
    }

    /** Validate and replace the selected inventory record's data. */
    public function update(Request $request, $id, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'stock' => 'required|integer|min:0',
            'received' => 'nullable|date',
            'expirationDate' => 'nullable|date',
        ]);

        $firebase->updateInventory($id, $validated);

        return redirect()->back()->with('success', 'Item updated successfully');
    }

    /** Remove the selected inventory record. */
    public function destroy($id, FirebaseService $firebase)
    {
        $firebase->deleteInventory($id);

        return redirect()->back()->with('success', 'Item deleted successfully');
    }

    /** Show records associated with the requested batch identifier. */
    public function batch($batchId, FirebaseService $firebase)
    {
        $items = $this->normalizeInventoryItems($firebase->getInventory())
            ->filter(fn($item) => ($item['id'] ?? null) === $batchId)
            ->values();

        return view('features.inventory.batch', ['items' => $items]);
    }
}
