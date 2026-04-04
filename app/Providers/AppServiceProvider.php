<?php

namespace App\Providers;

use App\Auth\PlainTextUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('plaintext', function ($app, array $config) {
            return new PlainTextUserProvider($config['model']);
        });

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)
                ->by(mb_strtolower((string) $request->input('username')) . '|' . $request->ip())
                ->response(function () {
                    return back()
                        ->withErrors(['username' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ít phút.'])
                        ->onlyInput('username');
                });
        });
    }
}