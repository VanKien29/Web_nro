<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\AccountController;
use App\Http\Controllers\Api\Admin\AdminLogController;
use App\Http\Controllers\Api\Admin\BackAccessoryController;
use App\Http\Controllers\Api\Admin\BadgeController;
use App\Http\Controllers\Api\Admin\CatalogLookupController;
use App\Http\Controllers\Api\Admin\CostumeController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\GiftcodeController;
use App\Http\Controllers\Api\Admin\ItemController;
use App\Http\Controllers\Api\Admin\MilestoneController;
use App\Http\Controllers\Api\Admin\PetController;
use App\Http\Controllers\Api\Admin\PlayerController;
use App\Http\Controllers\Api\Admin\ShopController;
use App\Http\Controllers\Api\Admin\TitleItemController;
use App\Http\Controllers\Api\AdminRuntimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware('throttle:admin-login')->group(function () {
        Route::post('/api/login', [AdminAuthController::class, 'apiLogin']);
    });

    Route::middleware('admin.auth')->group(function () {
        Route::get('/api/me', [AdminAuthController::class, 'me']);
        Route::post('/api/logout', [AdminAuthController::class, 'apiLogout']);

        Route::get('/api/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/api/dashboard/history', [DashboardController::class, 'history']);
        Route::get('/api/dashboard/topUsers', [DashboardController::class, 'topUsers']);
        Route::get('/api/dashboard/monthRevenue', [DashboardController::class, 'monthlyRevenue']);
        Route::get('/api/dashboard/overview', [DashboardController::class, 'overview']);

        Route::get('/api/accounts', [AccountController::class, 'index']);
        Route::get('/api/accounts/{id}', [AccountController::class, 'show'])->whereNumber('id');
        Route::get('/api/accounts/{id}/player-full', [AccountController::class, 'playerFull'])->whereNumber('id');
        Route::get('/api/accounts/{id}/player-sections/{section}', [AccountController::class, 'playerSection'])->whereNumber('id');
        Route::get('/api/accounts/{id}/activity', [AccountController::class, 'activity'])->whereNumber('id');
        Route::put('/api/accounts/{id}/badges', [AccountController::class, 'updateBadges'])->whereNumber('id');
        Route::post('/api/accounts', [AccountController::class, 'store']);
        Route::put('/api/accounts/{id}', [AccountController::class, 'update'])->whereNumber('id');
        Route::delete('/api/accounts/{id}', [AccountController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/players', [PlayerController::class, 'index']);
        Route::get('/api/players/inventory/search', [PlayerController::class, 'inventorySearch']);
        Route::get('/api/players/{id}', [PlayerController::class, 'show'])->whereNumber('id');
        Route::put('/api/players/{id}/stats', [PlayerController::class, 'updateStats'])->whereNumber('id');
        Route::post('/api/players/{id}/inventory/buff', [PlayerController::class, 'buffInventory'])->whereNumber('id');
        Route::post('/api/players/{id}/inventory/revoke', [PlayerController::class, 'revokeInventory'])->whereNumber('id');

        Route::get('/api/giftcodes', [GiftcodeController::class, 'index']);
        Route::get('/api/giftcodes/{id}', [GiftcodeController::class, 'show'])->whereNumber('id');
        Route::get('/api/giftcodes/{id}/activity', [GiftcodeController::class, 'activity'])->whereNumber('id');
        Route::post('/api/giftcodes/{id}/clone', [GiftcodeController::class, 'clone'])->whereNumber('id');
        Route::post('/api/giftcodes', [GiftcodeController::class, 'store']);
        Route::put('/api/giftcodes/{id}', [GiftcodeController::class, 'update'])->whereNumber('id');
        Route::delete('/api/giftcodes/{id}', [GiftcodeController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/admin-logs', [AdminLogController::class, 'index']);

        Route::post('/api/runtime/shop/reload', [AdminRuntimeController::class, 'reloadShop']);
        Route::get('/api/runtime/health', [AdminRuntimeController::class, 'health']);
        Route::get('/api/runtime/bosses', [AdminRuntimeController::class, 'bosses']);
        Route::post('/api/runtime/bosses', [AdminRuntimeController::class, 'createBoss']);
        Route::post('/api/runtime/bosses/action', [AdminRuntimeController::class, 'bossAction']);
        Route::put('/api/runtime/bosses', [AdminRuntimeController::class, 'updateBoss']);
        Route::get('/api/runtime/boss-configs', [AdminRuntimeController::class, 'bossConfigs']);
        Route::post('/api/runtime/boss-configs', [AdminRuntimeController::class, 'saveBossConfig']);
        Route::get('/api/runtime/map-mobs', [AdminRuntimeController::class, 'mapMobs']);
        Route::post('/api/runtime/map-mobs', [AdminRuntimeController::class, 'saveMapMobs']);
        Route::post('/api/runtime/buffs/mail', [AdminRuntimeController::class, 'buffMail']);
        Route::post('/api/runtime/buffs/account', [AdminRuntimeController::class, 'buffAccount']);

        Route::get('/api/milestones/{type}', [MilestoneController::class, 'index']);
        Route::get('/api/milestones/{type}/{id}', [MilestoneController::class, 'show'])->whereNumber('id');
        Route::post('/api/milestones/{type}', [MilestoneController::class, 'store']);
        Route::put('/api/milestones/{type}/{id}', [MilestoneController::class, 'update'])->whereNumber('id');
        Route::delete('/api/milestones/{type}/{id}', [MilestoneController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/items', [ItemController::class, 'index']);
        Route::get('/api/items/batch', [ItemController::class, 'batch']);
        Route::put('/api/items/{id}', [ItemController::class, 'update'])->whereNumber('id');
        Route::get('/api/items/search', [CatalogLookupController::class, 'searchItems']);
        Route::get('/api/options', [CatalogLookupController::class, 'options']);

        Route::get('/api/badges', [BadgeController::class, 'index']);
        Route::get('/api/badges/{id}', [BadgeController::class, 'show'])->whereNumber('id');
        Route::post('/api/badges', [BadgeController::class, 'store']);
        Route::post('/api/badges/{id}', [BadgeController::class, 'update'])->whereNumber('id');
        Route::delete('/api/badges/{id}', [BadgeController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/title-items', [TitleItemController::class, 'index']);
        Route::get('/api/title-items/icon/{iconId}', [TitleItemController::class, 'icon'])->whereNumber('iconId');
        Route::post('/api/title-items', [TitleItemController::class, 'store']);
        Route::post('/api/title-items/{id}', [TitleItemController::class, 'update'])->whereNumber('id');

        Route::get('/api/costumes', [CostumeController::class, 'index']);
        Route::post('/api/costumes', [CostumeController::class, 'store']);
        Route::get('/api/costumes/{id}', [CostumeController::class, 'show'])->whereNumber('id');
        Route::post('/api/costumes/{id}', [CostumeController::class, 'update'])->whereNumber('id');
        Route::delete('/api/costumes/{id}', [CostumeController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/pets', [PetController::class, 'index']);
        Route::post('/api/pets', [PetController::class, 'store']);
        Route::get('/api/pets/{id}', [PetController::class, 'show'])->whereNumber('id');
        Route::post('/api/pets/{id}', [PetController::class, 'update'])->whereNumber('id');
        Route::delete('/api/pets/{id}', [PetController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/back-accessories', [BackAccessoryController::class, 'index']);
        Route::post('/api/back-accessories', [BackAccessoryController::class, 'store']);
        Route::get('/api/back-accessories/{id}', [BackAccessoryController::class, 'show'])->whereNumber('id');
        Route::post('/api/back-accessories/{id}', [BackAccessoryController::class, 'update'])->whereNumber('id');
        Route::delete('/api/back-accessories/{id}', [BackAccessoryController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/shops', [ShopController::class, 'index']);
        Route::get('/api/shops/tab/{tabId}', [ShopController::class, 'tab'])->whereNumber('tabId');
        Route::put('/api/shops/tab/{tabId}', [ShopController::class, 'updateTab'])->whereNumber('tabId');
    });

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
