<?php

namespace App\Http\Controllers\PhoneFeatures;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\InventoryStockStandard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Supplies the mobile inventory form and creates inventory records from its inputs. */
class AddInventoryController extends Controller
{
    public function __construct()
    {
    }

    /** Load distinct existing batch names for the inventory form. */
    public function index(FirebaseService $firebase)
    {
        $inventory = $firebase->getInventory();
        $batches = collect(is_array($inventory) ? $inventory : [])
            ->filter(fn ($item) => is_array($item) && filled($item['batch'] ?? null))
            ->pluck('batch')
            ->map(fn ($batch) => (string) $batch)
            ->unique()
            ->values();

        return view('phoneFeatures.addInventory', compact('batches'));
    }

    /** Normalize supported form field names and save the validated inventory item. */
    public function store(Request $request, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'item_name' => 'nullable|string|required_without:name',
            'other_item_name' => 'nullable|string|required_if:item_name,Other',
            'name' => 'nullable|string|required_without:item_name',
            'quantity' => 'nullable|integer|min:0|required_without:stock',
            'stock' => 'nullable|integer|min:0|required_without:quantity',
            'category' => 'required|string',
            'unit' => 'required|string',
            'date_received' => 'nullable|date|required_without:received',
            'received' => 'nullable|date|required_without:date_received',
            'expiration_date' => [
                'nullable',
                'date',
                Rule::requiredIf(
                    $request->category !== 'Equipment'
                    && ! $request->filled('expirationDate')
                ),
            ],
            'expirationDate' => [
                'nullable',
                'date',
                Rule::requiredIf(
                    $request->category !== 'Equipment'
                    && ! $request->filled('expiration_date')
                ),
            ],
            'batch_option' => 'required|in:existing,new',
            'batch' => 'nullable|string|max:100|required_if:batch_option,existing',
            'new_batch' => 'nullable|string|max:100|required_if:batch_option,new',
        ]);

        $batch = trim($validated['batch_option'] === 'new'
            ? $validated['new_batch']
            : $validated['batch']);
        $itemName = ($validated['item_name'] ?? null) === 'Other'
            ? $validated['other_item_name']
            : ($validated['item_name'] ?? $validated['name'] ?? null);
        $quantity = (int) ($validated['quantity'] ?? $validated['stock'] ?? 0);

        if ($validated['batch_option'] === 'existing') {
            $stockStandard = new InventoryStockStandard();
            $existingStock = collect($firebase->getInventory())
                ->filter(fn ($item) => is_array($item)
                    && (string) ($item['batch'] ?? '') === $batch
                    && mb_strtolower(trim((string) ($item['name'] ?? ''))) === mb_strtolower(trim($itemName)))
                ->sum(fn ($item) => max(0, (int) ($item['stock'] ?? 0)));
            $maximum = $stockStandard->maximumStockFor(['name' => $itemName]);

            if ($maximum !== null && $existingStock + $quantity > $maximum) {
                return redirect()->back()
                    ->withErrors(['batch' => "This item would exceed its maximum stock standard of {$maximum} for batch {$batch}."])
                    ->withInput();
            }
        }

        $payload = [
            'name' => $itemName,
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'stock' => $quantity,
            'received' => $validated['date_received'] ?? $validated['received'] ?? null,
            'expirationDate' => $validated['expiration_date'] ?? $validated['expirationDate'] ?? null,
            'batch' => $batch,
        ];

        $firebase->createInventory($payload);

        return redirect()->route('phoneFeatures.addInventory')->with('success', 'Item added successfully');
    }
}
