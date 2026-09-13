<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\ItemController;

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{sku}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{sku}', [ItemController::class, 'update'])->name('items.update');
    Route::post('/items/{sku}/adjust', [ItemController::class, 'adjust'])->name('items.adjust');
});
