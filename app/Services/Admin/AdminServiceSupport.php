<?php

namespace App\Services\Admin;

use App\Models\AdminActionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

abstract class AdminServiceSupport
{
    protected function fixJson(?string $value): string
    {
        if (!$value) {
            return '[]';
        }

        $normalized = trim($value);
        $normalized = preg_replace('/,\s*([\]\}])/', '$1', $normalized);
        $normalized = preg_replace('/([\[\{])\s*,/', '$1', $normalized);
        $normalized = preg_replace('/,\s*,/', ',', $normalized);

        return $normalized;
    }

    protected function sanitizeLogState(?array $state): ?array
    {
        if ($state === null) {
            return null;
        }

        unset(
            $state['password'],
            $state['newpass'],
            $state['token'],
            $state['xsrf_token'],
            $state['remember_token']
        );

        foreach ($state as $key => $value) {
            if (is_string($value) && mb_strlen($value) > 2000) {
                $state[$key] = mb_substr($value, 0, 2000) . ' ...';
            }
        }

        return $state;
    }

    protected function logAdminAction(
        string $action,
        string $targetType,
        int|string|null $targetId,
        ?string $summary,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?array $meta = null
    ): void {
        try {
            $admin = Auth::guard('admin')->user();
            AdminActionLog::create([
                'admin_user_id' => $admin?->id,
                'admin_username' => $admin?->username ?? $admin?->name ?? 'admin',
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId !== null ? (string) $targetId : null,
                'target_label' => $summary,
                'summary' => $summary,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'meta' => $meta,
            ]);
        } catch (\Throwable) {
            // Logging must never break admin actions.
        }
    }

    protected function nextGameId(string $table): int
    {
        return ((int) (DB::connection('game')->table($table)->max('id') ?? 0)) + 1;
    }

    protected function itemIconMapForDetails(iterable $details): array
    {
        $ids = [];
        foreach ($details as $detail) {
            if (!is_string($detail) || trim($detail) === '') {
                continue;
            }

            $decoded = json_decode($this->fixJson($detail), true);
            if (!is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $item) {
                if (is_array($item) && isset($item['temp_id']) && is_numeric($item['temp_id'])) {
                    $ids[(int) $item['temp_id']] = true;
                }
            }
        }

        $ids = array_keys($ids);
        if (!$ids) {
            return [];
        }

        return DB::connection('game')
            ->table('item_template')
            ->whereIn('id', $ids)
            ->pluck('icon_id', 'id')
            ->map(fn($iconId) => $iconId !== null ? (int) $iconId : null)
            ->all();
    }
}
