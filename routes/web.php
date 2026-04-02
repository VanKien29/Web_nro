<?php

use Illuminate\Support\Facades\Route;

// ========== API ROUTES ==========
Route::prefix('api')->group(function () {

    // Public APIs (no auth)
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);
    Route::get('/bxh', [\App\Http\Controllers\Api\BxhController::class, 'index']);
    Route::get('/giftcodes', [\App\Http\Controllers\Api\GiftcodeController::class, 'index']);
    Route::get('/posts/{slug}', [\App\Http\Controllers\Api\HomeController::class, 'postDetail']);

    // Auth (public)
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);

    // Auth required (game.auth middleware)
    Route::middleware('game.auth')->group(function () {
        Route::post('/auth/change-password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);
        Route::post('/auth/activate', [\App\Http\Controllers\Api\AuthController::class, 'activate']);
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'profile']);
        Route::get('/topup/history', [\App\Http\Controllers\Api\TopupController::class, 'history']);
    });

    // SePay webhook (API key auth in controller)
    Route::post('/sepay/webhook', [\App\Http\Controllers\Api\SePayController::class, 'webhook']);
    Route::get('/sepay/cron', [\App\Http\Controllers\Api\SePayController::class, 'cron']);

    // Topup — protected by topup.secret middleware
    Route::middleware('topup.secret')->group(function () {
        Route::post('/topup/credit', [\App\Http\Controllers\Api\TopupController::class, 'credit']);

        // Card topup
        Route::post('/topup/log', [\App\Http\Controllers\Api\TopupCardController::class, 'create']);
        Route::get('/topup/log', [\App\Http\Controllers\Api\TopupCardController::class, 'get']);
        Route::put('/topup/log/{transId}', [\App\Http\Controllers\Api\TopupCardController::class, 'update']);
        Route::get('/topup/log/history/{username}', [\App\Http\Controllers\Api\TopupCardController::class, 'history']);
    });

    // Admin API — protected by topup.secret middleware
    Route::middleware('topup.secret')->prefix('admin')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/history', [\App\Http\Controllers\Api\AdminController::class, 'history']);
        Route::get('/revenue', [\App\Http\Controllers\Api\AdminController::class, 'revenue']);
        Route::get('/topUsers', [\App\Http\Controllers\Api\AdminController::class, 'topUsers']);
        Route::get('/monthlyRevenue', [\App\Http\Controllers\Api\AdminController::class, 'monthlyRevenue']);

        // Accounts CRUD
        Route::get('/accounts', [\App\Http\Controllers\Api\AdminController::class, 'accountsList']);
        Route::get('/accounts/{id}', [\App\Http\Controllers\Api\AdminController::class, 'accountsGet']);
        Route::post('/accounts', [\App\Http\Controllers\Api\AdminController::class, 'accountsCreate']);
        Route::put('/accounts/{id}', [\App\Http\Controllers\Api\AdminController::class, 'accountsUpdate']);
        Route::delete('/accounts/{id}', [\App\Http\Controllers\Api\AdminController::class, 'accountsDelete']);

        // Giftcodes CRUD
        Route::get('/giftcodes', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesList']);
        Route::get('/giftcodes/{id}', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesGet']);
        Route::post('/giftcodes', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesCreate']);
        Route::put('/giftcodes/{id}', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesUpdate']);
        Route::delete('/giftcodes/{id}', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesDelete']);

        // Items
        Route::get('/items', [\App\Http\Controllers\Api\AdminController::class, 'itemsList']);
        Route::get('/items/{id}/options', [\App\Http\Controllers\Api\AdminController::class, 'itemsOptions']);
    });
});

// ========== ADMIN WEB ROUTES ==========
Route::prefix('admin')->group(function () {
    // Login
    Route::middleware('throttle:admin-login')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Admin\AuthController::class, 'create'])->name('admin.login');
        Route::post('/login', [\App\Http\Controllers\Admin\AuthController::class, 'store'])->name('admin.login.store');
    });

    // Protected admin pages
    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Admin\AuthController::class, 'destroy'])->name('admin.logout');
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

        // Accounts
        Route::get('/accounts', [\App\Http\Controllers\Admin\AccountController::class, 'index'])->name('admin.accounts.index');
        Route::get('/accounts/create', [\App\Http\Controllers\Admin\AccountController::class, 'create'])->name('admin.accounts.create');
        Route::post('/accounts', [\App\Http\Controllers\Admin\AccountController::class, 'store'])->name('admin.accounts.store');
        Route::get('/accounts/{account}/edit', [\App\Http\Controllers\Admin\AccountController::class, 'edit'])->name('admin.accounts.edit');
        Route::put('/accounts/{account}', [\App\Http\Controllers\Admin\AccountController::class, 'update'])->name('admin.accounts.update');
        Route::delete('/accounts/{account}', [\App\Http\Controllers\Admin\AccountController::class, 'destroy'])->name('admin.accounts.destroy');

        // Giftcodes
        Route::get('/giftcodes', [\App\Http\Controllers\Admin\GiftcodeController::class, 'index'])->name('admin.giftcodes.index');
        Route::get('/giftcodes/create', [\App\Http\Controllers\Admin\GiftcodeController::class, 'create'])->name('admin.giftcodes.create');
        Route::post('/giftcodes', [\App\Http\Controllers\Admin\GiftcodeController::class, 'store'])->name('admin.giftcodes.store');
        Route::get('/giftcodes/{id}/edit', [\App\Http\Controllers\Admin\GiftcodeController::class, 'edit'])->name('admin.giftcodes.edit');
        Route::put('/giftcodes/{id}', [\App\Http\Controllers\Admin\GiftcodeController::class, 'update'])->name('admin.giftcodes.update');
        Route::delete('/giftcodes/{id}', [\App\Http\Controllers\Admin\GiftcodeController::class, 'destroy'])->name('admin.giftcodes.destroy');

        // Items
        Route::get('/items', [\App\Http\Controllers\Admin\ItemController::class, 'index'])->name('admin.items.index');
    });
});

// SPA catch-all: mọi route khác đều trả về Vue app
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');