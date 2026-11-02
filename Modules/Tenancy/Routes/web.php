<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\ContextController;
use Modules\Tenancy\Http\Controllers\TenantController;

/*
| Phase 2 Tenancy admin routes intentionally have no Identity `auth` or
| RBAC/`can` middleware. Contributed permissions are metadata for Gate/
| Experience consumers when composed — they do not secure these routes.
*/

Route::prefix('tenancy')->name('tenancy.')->group(function () {
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('/tenants/{tenant}/deactivate', [TenantController::class, 'deactivate'])->name('tenants.deactivate');
    Route::post('/tenants/{tenant}/members', [TenantController::class, 'addMember'])->name('tenants.members.add');
    Route::delete('/tenants/{tenant}/members/{userId}', [TenantController::class, 'removeMember'])->name('tenants.members.remove');

    Route::get('/context', [ContextController::class, 'show'])->name('context.show');
    Route::post('/context', [ContextController::class, 'set'])->name('context.set');
    Route::post('/context/clear', [ContextController::class, 'clear'])->name('context.clear');
});
