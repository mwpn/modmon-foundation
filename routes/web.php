<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('foundation::dashboard');
})->middleware('auth')->name('dashboard');
