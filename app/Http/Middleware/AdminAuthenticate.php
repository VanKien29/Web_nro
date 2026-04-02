<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('admin.login');
        }

        $user = Auth::guard('admin')->user();

        if (!$user || !$user->is_admin) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Forbidden.',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}