<?php

use App\Http\Controllers\Api\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\GiftcodeController as AdminGiftcodeController;
use App\Http\Controllers\Api\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BxhController;
use App\Http\Controllers\Api\GiftcodeController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SePayController;
use App\Http\Controllers\Api\TopupCardController;
use App\Http\Controllers\Api\TopupController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/bxh', [BxhController::class, 'index']);
    Route::get('/giftcodes', [GiftcodeController::class, 'index']);
    Route::get('/posts/{slug}', [HomeController::class, 'postDetail']);

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('game.auth')->group(function () {
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('/auth/activate', [AuthController::class, 'activate']);
        Route::get('/profile', [ProfileController::class, 'profile']);
        Route::get('/topup/history', [TopupController::class, 'history']);
    });

    Route::post('/sepay/webhook', [SePayController::class, 'webhook']);
    Route::get('/sepay/cron', [SePayController::class, 'cron']);

    Route::middleware('topup.secret')->group(function () {
        Route::post('/topup/credit', [TopupController::class, 'credit']);

        Route::post('/topup/log', [TopupCardController::class, 'create']);
        Route::get('/topup/log', [TopupCardController::class, 'get']);
        Route::put('/topup/log/{transId}', [TopupCardController::class, 'update']);
        Route::get('/topup/log/history/{username}', [TopupCardController::class, 'history']);
    });

    Route::middleware('topup.secret')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/history', [AdminDashboardController::class, 'history']);
        Route::get('/revenue', [AdminDashboardController::class, 'revenue']);
        Route::get('/topUsers', [AdminDashboardController::class, 'topUsers']);
        Route::get('/monthlyRevenue', [AdminDashboardController::class, 'monthlyRevenue']);

        Route::get('/accounts', [AdminAccountController::class, 'index']);
        Route::get('/accounts/{id}', [AdminAccountController::class, 'show'])->whereNumber('id');
        Route::post('/accounts', [AdminAccountController::class, 'store']);
        Route::put('/accounts/{id}', [AdminAccountController::class, 'update'])->whereNumber('id');
        Route::delete('/accounts/{id}', [AdminAccountController::class, 'destroy'])->whereNumber('id');

        Route::get('/giftcodes', [AdminGiftcodeController::class, 'index']);
        Route::get('/giftcodes/{id}', [AdminGiftcodeController::class, 'show'])->whereNumber('id');
        Route::post('/giftcodes', [AdminGiftcodeController::class, 'store']);
        Route::put('/giftcodes/{id}', [AdminGiftcodeController::class, 'update'])->whereNumber('id');
        Route::delete('/giftcodes/{id}', [AdminGiftcodeController::class, 'destroy'])->whereNumber('id');

        Route::get('/items', [AdminItemController::class, 'index']);
        Route::get('/items/{id}/options', [AdminItemController::class, 'options'])->whereNumber('id');
        Route::put('/items/{id}', [AdminItemController::class, 'update'])->whereNumber('id');

        Route::get('/shops', [AdminShopController::class, 'index']);
        Route::get('/shops/tab/{tabId}', [AdminShopController::class, 'tab'])->whereNumber('tabId');
        Route::put('/shops/tab/{tabId}', [AdminShopController::class, 'updateTab'])->whereNumber('tabId');
    });
});
