<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $targetType = trim((string) $request->query('target_type', ''));
        $action = trim((string) $request->query('action', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = 20;

        $query = AdminActionLog::query()->orderByDesc('id');

        if ($targetType !== '') {
            $query->where('target_type', $targetType);
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('admin_username', 'like', "%{$search}%")
                    ->orWhere('target_label', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('target_id', (string) (int) $search);
                }
            });
        }

        $total = (clone $query)->count();
        $rows = $query
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit),
        ]);
    }
}
