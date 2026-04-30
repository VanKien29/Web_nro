<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminUnknownIpAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function apiLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = mb_strtolower(trim($credentials['username']));

        if (!Auth::guard('admin')->attempt([
            'username' => $username,
            'password' => $credentials['password'],
            'is_admin' => 1,
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'Sai tài khoản hoặc mật khẩu, hoặc không có quyền admin.',
            ], 422);
        }

        $request->session()->regenerate();

        $this->checkIpAndAlert($request);

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => Auth::guard('admin')->id(),
                'username' => Auth::guard('admin')->user()->username,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
        ]);
    }

    public function apiLogout(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    // ── Helpers ──

    private function checkIpAndAlert(Request $request): void
    {
        $admin = Auth::guard('admin')->user();
        $currentIp = $request->ip();
        $savedIp = $admin->ip_address;

        if (!$savedIp || $savedIp !== $currentIp) {
            $alertEmail = env('ADMIN_ALERT_EMAIL', $admin->email);
            if ($alertEmail) {
                try {
                    Mail::to($alertEmail)->send(new AdminUnknownIpAlert($admin, $currentIp, $savedIp));
                } catch (\Exception $e) {
                    Log::warning('Không thể gửi email cảnh báo IP admin: ' . $e->getMessage());
                }
            }
        }

        $admin->ip_address = $currentIp;
        $admin->save();
    }
}
