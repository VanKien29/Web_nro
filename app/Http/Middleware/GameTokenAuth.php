<?php

namespace App\Http\Middleware;

use App\Models\Game\Account;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;

class GameTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $jwt = new JwtService();
        $payload = $jwt->decode($token);

        if (!$payload || !isset($payload->sub)) {
            return response()->json(['ok' => false, 'error' => 'invalid_token'], 401);
        }

        $account = Account::find($payload->sub);

        if (!$account) {
            return response()->json(['ok' => false, 'error' => 'invalid_token'], 401);
        }

        $request->merge(['game_user' => $account]);

        return $next($request);
    }
}
