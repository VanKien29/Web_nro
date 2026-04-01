<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TopupSecret
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('services.game_api.topup_secret');
        $provided = $request->header('X-Topup-Secret');

        if (!$secret || !$provided || !hash_equals($secret, $provided)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
