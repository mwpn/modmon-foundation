<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TenantLanding\Http\Controllers\TenantLandingController;

Route::prefix('tenant-landing')->name('tenant-landing.')->group(function () {
    Route::get('/', [TenantLandingController::class, 'index'])->name('index');
    Route::get('/tenants/{tenantId}', [TenantLandingController::class, 'edit'])->name('edit');
    Route::put('/tenants/{tenantId}', [TenantLandingController::class, 'update'])->name('update');
});
