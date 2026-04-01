<?php

namespace App\Http\Middleware;

use App\Models\Game\ApiToken;
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

        $apiToken = ApiToken::where('token', $token)->first();

        if (!$apiToken) {
            return response()->json(['ok' => false, 'error' => 'invalid_token'], 401);
        }

        $account = $apiToken->account;

        if (!$account) {
            return response()->json(['ok' => false, 'error' => 'invalid_token'], 401);
        }

        $request->merge(['game_user' => $account]);

        return $next($request);
    }
}
