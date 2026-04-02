<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminUnknownIpAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
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
            return back()
                ->withErrors(['username' => 'Sai tài khoản hoặc mật khẩu, hoặc không có quyền admin.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $currentIp = $request->ip();
        $savedIp = $admin->ip_address;

        // Kiểm tra IP lạ và gửi email cảnh báo (gồm cả lần đầu chưa có IP)
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

        // Cập nhật IP mới
        $admin->ip_address = $currentIp;
        $admin->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}