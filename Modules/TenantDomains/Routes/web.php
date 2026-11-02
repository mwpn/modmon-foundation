<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TenantDomains\Http\Controllers\TenantDomainController;

Route::prefix('tenant-domains')->name('tenant-domains.')->group(function () {
    Route::get('/', [TenantDomainController::class, 'index'])->name('index');
    Route::get('/tenants/{tenantId}', [TenantDomainController::class, 'show'])->name('show');
    Route::post('/tenants/{tenantId}', [TenantDomainController::class, 'store'])->name('store');
    Route::post('/tenants/{tenantId}/domains/{domainId}/primary', [TenantDomainController::class, 'setPrimary'])->name('primary');
    Route::post('/tenants/{tenantId}/domains/{domainId}/activate', [TenantDomainController::class, 'activate'])->name('activate');
    Route::post('/tenants/{tenantId}/domains/{domainId}/deactivate', [TenantDomainController::class, 'deactivate'])->name('deactivate');
    Route::delete('/tenants/{tenantId}/domains/{domainId}', [TenantDomainController::class, 'destroy'])->name('destroy');
});
