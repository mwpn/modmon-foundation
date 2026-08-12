<?php

use Illuminate\Support\Facades\Route;
use Modules\Example\Http\Controllers\ExampleController;

Route::prefix('example')->group(function () {
    Route::get('/', [ExampleController::class, 'index'])->name('example.index');
    Route::get('/about', [ExampleController::class, 'about'])->name('example.about');
});
