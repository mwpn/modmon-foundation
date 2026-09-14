<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Branding\Http\Controllers\BrandingController;

Route::prefix('branding')->name('branding.')->group(function () {
    Route::get('/', [BrandingController::class, 'edit'])->name('edit');
    Route::put('/', [BrandingController::class, 'update'])->name('update');
});
