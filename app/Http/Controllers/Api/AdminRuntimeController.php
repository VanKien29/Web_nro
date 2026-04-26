<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Services\GameRuntimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminRuntimeController extends Controller
{
    public function reloadShop(GameRuntimeService $runtime): JsonResponse
    {
        $result = [
            'ok' => false,
            'code' => 'RUNTIME_ERROR',
            'message' => 'Không gọi được game runtime API.',
        ];

        try {
            $result = $runtime->reloadShop();
            $this->logRuntimeAction('shop.reload', $result);
        } catch (\Throwable $e) {
            $result['message'] = $e->getMessage();
            $this->logRuntimeAction('shop.reload', $result, $e);
        }

        $status = !empty($result['ok']) ? 200 : (int) ($result['http_status'] ?? 500);
        if ($status < 400) {
            $status = !empty($result['ok']) ? 200 : 500;
        }

        return response()->json($result, $status);
    }

    private function logRuntimeAction(string $action, array $result, ?\Throwable $error = null): void
    {
        try {
            $admin = Auth::guard('admin')->user();
            AdminActionLog::create([
                'admin_user_id' => $admin?->id,
                'admin_username' => $admin?->username ?? $admin?->name ?? 'admin',
                'action' => $action,
                'target_type' => 'runtime',
                'target_id' => 'shop',
                'target_label' => 'Reload shop runtime',
                'summary' => !empty($result['ok'])
                    ? 'Reload shop trong game thành công'
                    : 'Reload shop trong game thất bại',
                'before_state' => null,
                'after_state' => null,
                'meta' => [
                    'result' => $result,
                    'error' => $error?->getMessage(),
                ],
            ]);
        } catch (\Throwable $ignored) {
            //
        }
    }
}
