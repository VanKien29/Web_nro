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
        Route::put('/items/{id}', [\App\Http\Controllers\Api\AdminController::class, 'itemsUpdate']);
        Route::get('/items/{id}/options', [\App\Http\Controllers\Api\AdminController::class, 'itemsOptions']);

        // Shops
        Route::get('/shops', [\App\Http\Controllers\Api\AdminController::class, 'shopsList']);
        Route::get('/shops/tab/{tabId}', [\App\Http\Controllers\Api\AdminController::class, 'shopTabGet']);
        Route::put('/shops/tab/{tabId}', [\App\Http\Controllers\Api\AdminController::class, 'shopTabUpdate']);
    });
});

// ========== ADMIN WEB ROUTES ==========
// ========== ADMIN SPA + JSON API ==========
Route::prefix('admin')->group(function () {

    // JSON auth endpoints
    Route::middleware('throttle:admin-login')->group(function () {
        Route::post('/api/login', [\App\Http\Controllers\Admin\AuthController::class, 'apiLogin']);
    });

    // Protected JSON APIs (session auth)
    Route::middleware('admin.auth')->group(function () {
        Route::get('/api/me', [\App\Http\Controllers\Admin\AuthController::class, 'me']);
        Route::post('/api/logout', [\App\Http\Controllers\Admin\AuthController::class, 'apiLogout']);

        // Dashboard
        Route::get('/api/dashboard/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/api/dashboard/history', [\App\Http\Controllers\Api\AdminController::class, 'history']);
        Route::get('/api/dashboard/topUsers', [\App\Http\Controllers\Api\AdminController::class, 'topUsers']);
        Route::get('/api/dashboard/monthRevenue', [\App\Http\Controllers\Api\AdminController::class, 'monthlyRevenue']);
        Route::get('/api/dashboard/overview', [\App\Http\Controllers\Api\AdminController::class, 'dashboardOverview']);

        // Accounts CRUD
        Route::get('/api/accounts', [\App\Http\Controllers\Api\AdminController::class, 'accountsList']);
        Route::get('/api/accounts/{id}', [\App\Http\Controllers\Api\AdminController::class, 'accountsGet']);
        Route::get('/api/accounts/{id}/player-full', [\App\Http\Controllers\Api\AdminController::class, 'accountsPlayerFull']);
        Route::get('/api/accounts/{id}/player-sections/{section}', [\App\Http\Controllers\Api\AdminController::class, 'accountsPlayerSection']);
        Route::get('/api/accounts/{id}/activity', [\App\Http\Controllers\Api\AdminController::class, 'accountsActivity']);
        Route::post('/api/accounts', [\App\Http\Controllers\Api\AdminController::class, 'accountsCreate']);
        Route::put('/api/accounts/{id}', [\App\Http\Controllers\Api\AdminController::class, 'accountsUpdate']);
        Route::delete('/api/accounts/{id}', [\App\Http\Controllers\Api\AdminController::class, 'accountsDelete']);

        // Giftcodes CRUD
        Route::get('/api/giftcodes', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesList']);
        Route::get('/api/giftcodes/{id}', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesGet']);
        Route::get('/api/giftcodes/{id}/activity', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesActivity']);
        Route::post('/api/giftcodes/{id}/clone', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesClone']);
        Route::post('/api/giftcodes', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesCreate']);
        Route::put('/api/giftcodes/{id}', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesUpdate']);
        Route::delete('/api/giftcodes/{id}', [\App\Http\Controllers\Api\AdminController::class, 'giftcodesDelete']);

        // Admin logs
        Route::get('/api/admin-logs', [\App\Http\Controllers\Api\AdminController::class, 'adminLogsList']);

        // Game runtime commands
        Route::post('/api/runtime/shop/reload', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'reloadShop']);
        Route::get('/api/runtime/bosses', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'bosses']);
        Route::post('/api/runtime/bosses', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'createBoss']);
        Route::post('/api/runtime/bosses/action', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'bossAction']);
        Route::put('/api/runtime/bosses', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'updateBoss']);
        Route::get('/api/runtime/boss-configs', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'bossConfigs']);
        Route::post('/api/runtime/boss-configs', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'saveBossConfig']);
        Route::get('/api/runtime/map-mobs', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'mapMobs']);
        Route::post('/api/runtime/map-mobs', [\App\Http\Controllers\Api\AdminRuntimeController::class, 'saveMapMobs']);

        // Milestone reward tables
        Route::get('/api/milestones/{type}', [\App\Http\Controllers\Api\AdminController::class, 'milestonesList']);
        Route::get('/api/milestones/{type}/{id}', [\App\Http\Controllers\Api\AdminController::class, 'milestonesGet']);
        Route::post('/api/milestones/{type}', [\App\Http\Controllers\Api\AdminController::class, 'milestonesCreate']);
        Route::put('/api/milestones/{type}/{id}', [\App\Http\Controllers\Api\AdminController::class, 'milestonesUpdate']);
        Route::delete('/api/milestones/{type}/{id}', [\App\Http\Controllers\Api\AdminController::class, 'milestonesDelete']);

        // Items & Options
        Route::get('/api/items', [\App\Http\Controllers\Api\AdminController::class, 'itemsList']);
        Route::put('/api/items/{id}', [\App\Http\Controllers\Api\AdminController::class, 'itemsUpdate']);
        Route::get('/api/items/batch', [\App\Http\Controllers\Api\AdminController::class, 'itemsBatch']);
        Route::get('/api/items/search', [\App\Http\Controllers\Admin\GiftcodeController::class, 'searchItems']);
        Route::get('/api/options', [\App\Http\Controllers\Admin\GiftcodeController::class, 'allOptions']);

        // Shops
        Route::get('/api/shops', [\App\Http\Controllers\Api\AdminController::class, 'shopsList']);
        Route::get('/api/shops/tab/{tabId}', [\App\Http\Controllers\Api\AdminController::class, 'shopTabGet']);
        Route::put('/api/shops/tab/{tabId}', [\App\Http\Controllers\Api\AdminController::class, 'shopTabUpdate']);
    });

    // Admin SPA catch-all — serves Vue app for all /admin/* routes
    Route::get('/login', function () {
        return response()
            ->view('admin.spa')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('admin.login');
    Route::get('/{any?}', function () {
        return response()
            ->view('admin.spa')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->where('any', '.*')->middleware('admin.auth');
});

// SPA catch-all: mọi route khác đều trả về Vue app
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
