<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TenancyBranding\Http\Controllers\TenantBrandingController;

Route::prefix('tenancy-branding')->name('tenancy-branding.')->group(function () {
    Route::get('/', [TenantBrandingController::class, 'index'])->name('index');
    Route::get('/tenants/{tenantId}', [TenantBrandingController::class, 'edit'])->name('edit');
    Route::put('/tenants/{tenantId}', [TenantBrandingController::class, 'update'])->name('update');
    Route::delete('/tenants/{tenantId}', [TenantBrandingController::class, 'destroy'])->name('destroy');
});
