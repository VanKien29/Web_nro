<?php

use Illuminate\Support\Facades\Route;

// API routes
Route::prefix('api')->group(function () {
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);
});

// SPA catch-all: mọi route khác đều trả về Vue app
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
