<?php

use Illuminate\Support\Facades\Route;

// Host entry. Guests are sent on by the `auth` middleware: to the login
// page of whichever identity module is enabled, else to the welcome notice.
Route::redirect('/', '/dashboard')->name('home');

Route::view('/welcome', 'welcome')->middleware('guest')->name('welcome');

// ModMon foundation dashboard (modules contribute widgets here)
Route::get('/dashboard', function () {
    return view('foundation::dashboard');
})->middleware('auth')->name('dashboard');
