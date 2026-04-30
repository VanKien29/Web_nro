<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/api.php';
require __DIR__.'/admin.php';

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
