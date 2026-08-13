<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Auth\LoginController;
use Modules\Identity\Http\Controllers\Auth\LogoutController;
use Modules\Identity\Http\Controllers\Auth\PasswordResetController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('identity.login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('identity.login.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
        ->name('identity.password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('identity.password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('identity.password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('identity.password.update');
});

Route::post('/logout', [LogoutController::class, 'logout'])
    ->middleware('auth')
    ->name('identity.logout');
