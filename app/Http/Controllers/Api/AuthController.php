<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = trim($request->input('username'));
        $password = trim($request->input('password'));

        $user = Account::where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user || trim($password) !== trim($user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sai tài khoản hoặc mật khẩu',
            ], 401);
        }

        $jwt = new JwtService();
        $token = $jwt->encode([
            'sub' => $user->id,
            'username' => $user->username,
            'is_admin' => (int) $user->is_admin,
        ]);

        // Lưu IP khi đăng nhập
        $user->ip_address = $request->ip();
        $user->save();

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'is_admin' => (int) $user->is_admin,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:32',
            'password' => 'required|string|min:3',
        ]);

        $username = trim($request->input('username'));
        $password = trim($request->input('password'));

        if (Account::where('username', $username)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tài khoản đã tồn tại',
            ], 409);
        }

        Account::create([
            'username' => $username,
            'password' => $password,
            'active' => 0,
            'is_admin' => 0,
            'ban' => 0,
            'cash' => 0,
            'coin' => 0,
            'danap' => 0,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng ký thành công',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $account = $request->get('game_user');

        $request->validate([
            'new_password' => 'required|string|min:3',
        ]);

        $account->password = trim($request->input('new_password'));
        $account->save();

        return response()->json([
            'ok' => true,
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $account = $request->get('game_user');

        if ((int) $account->active === 1) {
            return response()->json([
                'ok' => false,
                'error' => 'already_activated',
            ], 400);
        }

        if ((int) $account->cash < 10000) {
            return response()->json([
                'ok' => false,
                'error' => 'not_enough_cash',
                'need' => 10000,
            ], 400);
        }

        $account->decrement('cash', 10000);
        $account->update(['active' => 1]);

        return response()->json([
            'ok' => true,
            'message' => 'Kích hoạt tài khoản thành công',
        ]);
    }
}