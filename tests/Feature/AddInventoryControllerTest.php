<?php

use App\Http\Controllers\PhoneFeatures\AddInventoryController;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

it('stores inventory items from the phone form in firebase', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->once()->andReturn([]);
    $firebase->shouldReceive('createInventory')->once()->with([
        'name' => 'Rice',
        'category' => 'Food',
        'unit' => 'Pack',
        'stock' => 50,
        'received' => '2026-07-26',
        'expirationDate' => '2026-12-31',
        'batch' => 'Batch 1',
    ])->andReturn(['id' => 'abc123']);

    $request = new Request([
        'item_name' => 'Rice',
        'category' => 'Food',
        'unit' => 'Pack',
        'quantity' => 50,
        'date_received' => '2026-07-26',
        'expiration_date' => '2026-12-31',
        'batch_option' => 'existing',
        'batch' => 'Batch 1',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('success'))->toBe('Item added successfully');
});

it('hides the expiration date for equipment and hygiene', function () {
    $html = view('phoneFeatures.addInventory', [
        'batches' => collect(),
        'errors' => new MessageBag(),
    ])->render();

    expect($html)->toContain('id="expirationDateGroup"')
        ->and($html)->toContain("categorySelect.value !== 'Equipment'")
        ->and($html)->toContain("categorySelect.value !== 'Hygiene'")
        ->and($html)->toContain('expirationDateInput.disabled = !requiresExpirationDate');
});

it('applies toothbrush defaults when the item is selected', function () {
    $html = view('phoneFeatures.addInventory', [
        'batches' => collect(),
        'errors' => new MessageBag(),
    ])->render();

    expect($html)->toContain("itemSelect.value === 'Toothbrush'")
        ->and($html)->toContain("quantityInput.value = '1500'")
        ->and($html)->toContain("categorySelect.value = 'Hygiene'")
        ->and($html)->toContain("unitSelect.value = 'Pieces'");
});

it('applies rice defaults while retaining the expiration date input', function () {
    $html = view('phoneFeatures.addInventory', [
        'batches' => collect(),
        'errors' => new MessageBag(),
    ])->render();

    expect($html)->toContain("itemSelect.value === 'Rice'")
        ->and($html)->toContain("quantityInput.value = '1800'")
        ->and($html)->toContain("categorySelect.value = 'Food'")
        ->and($html)->toContain("unitSelect.value = 'Kg'")
        ->and($html)->toContain('id="expiration_date"');
});

it('allows equipment items without an expiration date', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('createInventory')->once()->with([
        'name' => 'Nail Cutter',
        'category' => 'Equipment',
        'unit' => 'Piece',
        'stock' => 5,
        'received' => '2026-10-06',
        'expirationDate' => null,
        'batch' => 'New Equipment Batch',
    ])->andReturn(['id' => 'abc123']);

    $request = new Request([
        'item_name' => 'Nail Cutter',
        'category' => 'Equipment',
        'unit' => 'Piece',
        'quantity' => 5,
        'date_received' => '2026-10-06',
        'batch_option' => 'new',
        'new_batch' => 'New Equipment Batch',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('success'))->toBe('Item added successfully');
});

it('allows toothbrush without an expiration date', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('createInventory')->once()->with([
        'name' => 'Toothbrush',
        'category' => 'Hygiene',
        'unit' => 'Pieces',
        'stock' => 1500,
        'received' => '2026-10-06',
        'expirationDate' => null,
        'batch' => 'New Hygiene Batch',
    ])->andReturn(['id' => 'abc123']);

    $request = new Request([
        'item_name' => 'Toothbrush',
        'category' => 'Hygiene',
        'unit' => 'Pieces',
        'quantity' => 1500,
        'date_received' => '2026-10-06',
        'batch_option' => 'new',
        'new_batch' => 'New Hygiene Batch',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('success'))->toBe('Item added successfully');
});

it('prevents adding stock beyond the maximum standard for an existing batch', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->once()->andReturn([
        'item-1' => [
            'name' => 'Toothbrush',
            'batch' => 'Batch 1',
            'stock' => 1500,
        ],
    ]);
    $firebase->shouldNotReceive('createInventory');

    $request = new Request([
        'item_name' => 'Toothbrush',
        'category' => 'Equipment',
        'unit' => 'Piece',
        'quantity' => 1,
        'date_received' => '2026-10-06',
        'expiration_date' => '2026-12-31',
        'batch_option' => 'existing',
        'batch' => 'Batch 1',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('errors')->first())->toContain('maximum stock');
});
