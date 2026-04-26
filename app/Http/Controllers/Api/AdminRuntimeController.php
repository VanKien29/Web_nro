<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Services\GameRuntimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function bosses(GameRuntimeService $runtime): JsonResponse
    {
        return $this->runtimeResponse('boss.list', function () use ($runtime) {
            $result = $runtime->bosses();
            if (!empty($result['ok']) && !empty($result['data']['bosses']) && is_array($result['data']['bosses'])) {
                $result['data']['bosses'] = $this->attachBossAvatars($result['data']['bosses']);
            }
            if (!empty($result['ok'])) {
                $result['data']['skill_options'] = $this->skillOptions();
            }
            return $result;
        });
    }

    public function createBoss(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $payload = $request->only([
            'boss_id',
            'count',
            'custom',
            'name',
            'gender',
            'outfit',
            'hp_max',
            'dame',
            'map_join',
            'seconds_rest',
            'skill_temp',
            'type_appear',
            'bosses_appear_together',
            'text_s',
            'text_m',
            'text_e',
            'type_appear',
            'group_members',
        ]);
        $payload['boss_id'] = (int) ($payload['boss_id'] ?? 0);
        $payload['count'] = max(1, min((int) ($payload['count'] ?? 1), 50));
        return $this->runtimeResponse('boss.create', fn() => $runtime->createBoss($payload));
    }

    public function bossAction(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $payload = [
            'manager' => (string) $request->input('manager', ''),
            'index' => (int) $request->input('index', -1),
            'action' => (string) $request->input('action', ''),
            'scope' => (string) $request->input('scope', 'single'),
        ];
        return $this->runtimeResponse('boss.action.' . $payload['action'], fn() => $runtime->bossAction($payload));
    }

    public function updateBoss(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $payload = $request->only([
            'manager',
            'index',
            'level_index',
            'enabled',
            'name',
            'template_name',
            'gender',
            'outfit',
            'hp',
            'hp_max',
            'dame',
            'seconds_rest',
            'map_join',
            'skill_temp',
            'type_appear',
            'bosses_appear_together',
            'text_s',
            'text_m',
            'text_e',
            'status',
        ]);
        if (isset($payload['index'])) {
            $payload['index'] = (int) $payload['index'];
        }
        if (isset($payload['level_index'])) {
            $payload['level_index'] = (int) $payload['level_index'];
        }
        foreach (['hp', 'hp_max', 'dame', 'seconds_rest', 'gender'] as $field) {
            if (isset($payload[$field]) && $payload[$field] !== '') {
                $payload[$field] = (int) $payload[$field];
            }
        }
        return $this->runtimeResponse('boss.update', fn() => $runtime->updateBoss($payload));
    }

    public function bossConfigs(GameRuntimeService $runtime): JsonResponse
    {
        return $this->runtimeResponse('boss.config.list', fn() => $runtime->bossConfigs());
    }

    public function saveBossConfig(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $payload = $request->only([
            'section',
            'boss_id',
            'level_index',
            'active',
            'name',
            'template_name',
            'gender',
            'outfit',
            'hp_max',
            'dame',
            'seconds_rest',
            'map_join',
            'skill_temp',
            'type_appear',
            'bosses_appear_together',
            'text_s',
            'text_m',
            'text_e',
            'rule_key',
            'manager_key',
            'count',
            'auto_spawn',
            'apply_now',
        ]);

        foreach (['boss_id', 'level_index', 'gender', 'hp_max', 'dame', 'seconds_rest', 'count'] as $field) {
            if (isset($payload[$field]) && $payload[$field] !== '') {
                $payload[$field] = (int) $payload[$field];
            }
        }

        return $this->runtimeResponse('boss.config.save.' . ($payload['section'] ?? 'unknown'), fn() => $runtime->saveBossConfig($payload));
    }

    private function runtimeResponse(string $action, callable $callback): JsonResponse
    {
        $result = [
            'ok' => false,
            'code' => 'RUNTIME_ERROR',
            'message' => 'Không gọi được game runtime API.',
        ];

        try {
            $result = $callback();
            $this->logRuntimeAction($action, $result);
        } catch (\Throwable $e) {
            $result['message'] = $e->getMessage();
            $this->logRuntimeAction($action, $result, $e);
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
                'target_id' => str_starts_with($action, 'boss') ? 'boss' : 'shop',
                'target_label' => $action,
                'summary' => !empty($result['ok'])
                    ? "Runtime {$action} thành công"
                    : "Runtime {$action} thất bại",
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

    private function attachBossAvatars(array $bosses): array
    {
        $headIds = [];
        foreach ($bosses as $boss) {
            $outfit = $boss['outfit'] ?? [];
            if (!is_array($outfit)) {
                $outfit = [];
            }
            $headId = (int) ($outfit[0] ?? -1);
            if ($headId >= 0) {
                $headIds[$headId] = true;
            }
            foreach (($boss['levels_data'] ?? []) as $level) {
                $levelOutfit = $level['outfit'] ?? [];
                if (!is_array($levelOutfit)) {
                    continue;
                }
                $levelHeadId = (int) ($levelOutfit[0] ?? -1);
                if ($levelHeadId >= 0) {
                    $headIds[$levelHeadId] = true;
                }
            }
        }

        $avatarByHead = [];
        if (!empty($headIds)) {
            try {
                $rows = DB::connection('game')->table('head_avatar')
                    ->whereIn('head_id', array_keys($headIds))
                    ->orderBy('head_id')
                    ->orderBy('avatar_id')
                    ->get(['head_id', 'avatar_id']);

                foreach ($rows as $row) {
                    $headId = (int) $row->head_id;
                    if (!isset($avatarByHead[$headId])) {
                        $avatarByHead[$headId] = (int) $row->avatar_id;
                    }
                }
            } catch (\Throwable) {
                $avatarByHead = [];
            }
        }

        foreach ($bosses as &$boss) {
            $outfit = $boss['outfit'] ?? [];
            $headId = is_array($outfit) ? (int) ($outfit[0] ?? -1) : -1;
            $boss['head_id'] = $headId;
            $boss['avatar_id'] = $headId >= 0 ? ($avatarByHead[$headId] ?? null) : null;
            if (!empty($boss['levels_data']) && is_array($boss['levels_data'])) {
                foreach ($boss['levels_data'] as &$level) {
                    $outfit = $level['outfit'] ?? [];
                    $headId = is_array($outfit) ? (int) ($outfit[0] ?? -1) : -1;
                    $level['head_id'] = $headId;
                    $level['avatar_id'] = $headId >= 0 ? ($avatarByHead[$headId] ?? null) : null;
                }
                unset($level);
            }
        }
        unset($boss);

        return $bosses;
    }

    private function skillOptions(): array
    {
        try {
            $rows = DB::connection('game')->table('skill_template')
                ->orderBy('nclass_id')
                ->orderBy('slot')
                ->get([
                    'id',
                    'nclass_id',
                    DB::raw('`NAME` as name'),
                    'max_point',
                    'icon_id',
                    'skills',
                ]);
        } catch (\Throwable) {
            return [];
        }

        $seen = [];
        $options = [];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $options[] = [
                'id' => $id,
                'nclass_id' => (int) $row->nclass_id,
                'class_name' => match ((int) $row->nclass_id) {
                    0 => 'Trái Đất',
                    1 => 'Namek',
                    2 => 'Xayda',
                    default => 'Khác',
                },
                'name' => (string) $row->name,
                'max_point' => (int) $row->max_point,
                'icon_id' => (int) $row->icon_id,
                'levels' => $this->parseSkillLevels((string) $row->skills),
            ];
        }

        return $options;
    }

    private function parseSkillLevels(string $raw): array
    {
        $normalized = str_replace(['["', '"[', '"]', ']"', '}","{'], ['[', '[', ']', ']', '},{'], trim($raw));
        $levels = json_decode($normalized, true);
        if (!is_array($levels)) {
            preg_match_all('/\{[^{}]*\}/', $normalized, $matches);
            $levels = [];
            foreach ($matches[0] ?? [] as $json) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $levels[] = $decoded;
                }
            }
        }

        $result = [];
        foreach ($levels as $level) {
            if (!is_array($level)) {
                continue;
            }
            $result[] = [
                'skill_id' => (int) ($level['id'] ?? 0),
                'point' => (int) ($level['point'] ?? 0),
                'cool_down' => (int) ($level['cool_down'] ?? 0),
                'damage' => (int) ($level['damage'] ?? 0),
                'mana_use' => (int) ($level['mana_use'] ?? 0),
            ];
        }

        return $result;
    }
}
