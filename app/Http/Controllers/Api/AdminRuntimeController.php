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

    public function mapMobs(GameRuntimeService $runtime): JsonResponse
    {
        return $this->runtimeResponse('map_mob.list', function () use ($runtime) {
            $result = $runtime->mapMobs();
            if (!empty($result['ok']) && !empty($result['data']['maps']) && is_array($result['data']['maps'])) {
                $result['data']['maps'] = $this->enrichMapsStaticData($result['data']['maps']);
                $result['data']['map_options'] = $this->mapOptions();
                $result['data']['npc_templates'] = $this->npcTemplates();
                $result['data']['item_templates'] = $this->dropItemTemplates();
            }
            return $result;
        });
    }

    public function saveMapMobs(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $mobs = $request->input('mobs', []);
        if (!is_array($mobs)) {
            return response()->json(['ok' => false, 'message' => 'Dữ liệu mob không hợp lệ'], 422);
        }

        $payload = [
            'map_id' => (int) $request->input('map_id', -1),
            'max_player' => max(1, min(100, (int) $request->input('max_player', 12))),
            'zones' => max(1, min(120, (int) $request->input('zones', 1))),
            'waypoints' => collect($request->input('waypoints', []))->map(function ($waypoint) {
                $waypoint = is_array($waypoint) ? $waypoint : [];
                return [
                    'name' => (string) ($waypoint['name'] ?? ''),
                    'min_x' => max(0, min(32767, (int) ($waypoint['min_x'] ?? 0))),
                    'min_y' => max(0, min(32767, (int) ($waypoint['min_y'] ?? 0))),
                    'max_x' => max(0, min(32767, (int) ($waypoint['max_x'] ?? 0))),
                    'max_y' => max(0, min(32767, (int) ($waypoint['max_y'] ?? 0))),
                    'is_enter' => !empty($waypoint['is_enter']),
                    'is_offline' => !empty($waypoint['is_offline']),
                    'go_map' => (int) ($waypoint['go_map'] ?? 0),
                    'go_x' => max(0, min(32767, (int) ($waypoint['go_x'] ?? 0))),
                    'go_y' => max(0, min(32767, (int) ($waypoint['go_y'] ?? 0))),
                ];
            })->values()->all(),
            'npcs' => collect($request->input('npcs', []))->map(function ($npc) {
                $npc = is_array($npc) ? $npc : [];
                return [
                    'id' => (int) ($npc['id'] ?? 0),
                    'x' => max(0, min(32767, (int) ($npc['x'] ?? 0))),
                    'y' => max(0, min(32767, (int) ($npc['y'] ?? 0))),
                ];
            })->values()->all(),
            'drop_rules' => collect($request->input('drop_rules', []))->map(function ($rule) {
                $rule = is_array($rule) ? $rule : [];
                return [
                    'item_id' => (int) ($rule['item_id'] ?? 0),
                    'quantity_min' => max(1, (int) ($rule['quantity_min'] ?? 1)),
                    'quantity_max' => max(1, (int) ($rule['quantity_max'] ?? 1)),
                    'chance_numerator' => max(0, (int) ($rule['chance_numerator'] ?? 1)),
                    'chance_denominator' => max(1, (int) ($rule['chance_denominator'] ?? 100)),
                    'mob_temp_id' => $rule['mob_temp_id'] === null || $rule['mob_temp_id'] === '' ? null : (int) $rule['mob_temp_id'],
                    'active' => !empty($rule['active']),
                    'note' => (string) ($rule['note'] ?? ''),
                    'options' => collect($rule['options'] ?? [])->map(function ($option) {
                        $option = is_array($option) ? $option : [];
                        return [
                            'id' => (int) ($option['id'] ?? 0),
                            'param' => (int) ($option['param'] ?? 0),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'mobs' => collect($mobs)->map(function ($mob) {
                $mob = is_array($mob) ? $mob : [];
                return [
                    'temp_id' => (int) ($mob['temp_id'] ?? 0),
                    'level' => max(0, min(127, (int) ($mob['level'] ?? 1))),
                    'hp' => max(1, (int) ($mob['hp'] ?? 1)),
                    'percent_dame' => max(0, min(100, (int) ($mob['percent_dame'] ?? $mob['dame'] ?? 0))),
                    'x' => max(0, min(32767, (int) ($mob['x'] ?? 0))),
                    'y' => max(0, min(32767, (int) ($mob['y'] ?? 0))),
                ];
            })->values()->all(),
        ];

        return $this->runtimeResponse('map_mob.save', function () use ($runtime, $payload) {
            $result = $runtime->saveMapMobs($payload);
            if (!empty($result['ok']) && !empty($result['data']['map']) && is_array($result['data']['map'])) {
                $maps = $this->enrichMapsStaticData([$result['data']['map']]);
                $result['data']['map'] = $maps[0] ?? $result['data']['map'];
            }
            return $result;
        });
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
                'target_id' => str_starts_with($action, 'boss') ? 'boss' : (str_starts_with($action, 'map_mob') ? 'map_mob' : 'shop'),
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

    private function enrichMapsStaticData(array $maps): array
    {
        $mapIds = collect($maps)->pluck('id')->filter()->map(fn($id) => (int) $id)->values()->all();
        if (empty($mapIds)) {
            return $maps;
        }

        $rows = DB::connection('game')->table('map_template')
            ->select('id', 'zones', 'waypoints', 'npcs')
            ->whereIn('id', $mapIds)
            ->get()
            ->keyBy('id');

        $fixedItemIds = collect([74, 78, 372, 373, 374, 375, 376, 377, 378])->all();
        $itemNames = DB::connection('game')->table('item_template')
            ->whereIn('id', $fixedItemIds)
            ->pluck('name', 'id');

        return array_map(function ($map) use ($rows, $itemNames) {
            $row = $rows->get((int) ($map['id'] ?? 0));
            $map['zones_config'] = (int) ($map['zones_config'] ?? ($row->zones ?? $map['zones'] ?? 0));
            $map['waypoints'] = $row ? $this->parseWaypoints((string) $row->waypoints) : [];
            $map['npcs'] = $row ? $this->parseNpcs((string) $row->npcs) : [];
            $map['fixed_items'] = $this->fixedItemsForMap((int) ($map['id'] ?? 0), $itemNames->all());
            return $map;
        }, $maps);
    }

    private function parseWaypoints(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $normalized = trim($raw);
        $normalized = preg_replace('/^\["\[/', '[[', $normalized) ?? $normalized;
        $normalized = preg_replace('/\]"\]$/', ']]', $normalized) ?? $normalized;
        $normalized = str_replace(']","[', '],[', $normalized);
        $decoded = json_decode($normalized, true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)->map(function ($row) {
            $row = is_array($row) ? $row : [];
            return [
                'name' => (string) ($row[0] ?? ''),
                'min_x' => (int) ($row[1] ?? 0),
                'min_y' => (int) ($row[2] ?? 0),
                'max_x' => (int) ($row[3] ?? 0),
                'max_y' => (int) ($row[4] ?? 0),
                'is_enter' => (int) ($row[5] ?? 0) === 1,
                'is_offline' => (int) ($row[6] ?? 0) === 1,
                'go_map' => (int) ($row[7] ?? 0),
                'go_x' => (int) ($row[8] ?? 0),
                'go_y' => (int) ($row[9] ?? 0),
            ];
        })->values()->all();
    }

    private function parseNpcs(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $npcIds = collect($decoded)->map(fn($row) => (int) ($row[0] ?? 0))->filter()->values()->all();
        $npcRows = DB::connection('game')->table('npc_template')
            ->whereIn('id', $npcIds)
            ->get(['id', 'name', 'avatar', 'head', 'body', 'leg'])
            ->keyBy('id');

        return collect($decoded)->map(function ($row) use ($npcRows) {
            $row = is_array($row) ? $row : [];
            $id = (int) ($row[0] ?? 0);
            $npc = $npcRows->get($id);
            return [
                'id' => $id,
                'name' => (string) ($npc->name ?? ''),
                'avatar' => isset($npc->avatar) ? (int) $npc->avatar : null,
                'head' => isset($npc->head) ? (int) $npc->head : null,
                'body' => isset($npc->body) ? (int) $npc->body : null,
                'leg' => isset($npc->leg) ? (int) $npc->leg : null,
                'x' => (int) ($row[1] ?? 0),
                'y' => (int) ($row[2] ?? 0),
            ];
        })->values()->all();
    }

    private function fixedItemsForMap(int $mapId, array $itemNames): array
    {
        $fixedItems = match ($mapId) {
            21 => [[74, 1, 633, 315]],
            22 => [[74, 1, 56, 315]],
            23 => [[74, 1, 633, 320]],
            42 => [[78, 1, 70, 288]],
            43 => [[78, 1, 70, 264]],
            44 => [[78, 1, 70, 288]],
            85 => [[372, 1, 0, 0]],
            86 => [[373, 1, 0, 0]],
            87 => [[374, 1, 0, 0]],
            88 => [[375, 1, 0, 0]],
            89 => [[376, 1, 0, 0]],
            90 => [[377, 1, 0, 0]],
            91 => [[378, 1, 0, 0]],
            default => [],
        };

        return collect($fixedItems)->map(function ($row) use ($itemNames) {
            $id = (int) ($row[0] ?? 0);
            return [
                'id' => $id,
                'name' => (string) ($itemNames[$id] ?? ''),
                'quantity' => (int) ($row[1] ?? 1),
                'x' => (int) ($row[2] ?? 0),
                'y' => (int) ($row[3] ?? 0),
                'per_zone' => true,
            ];
        })->values()->all();
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

    private function mapOptions(): array
    {
        try {
            return DB::connection('game')->table('map_template')
                ->orderBy('id')
                ->get(['id', 'name', 'planet_id'])
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'planet_id' => (int) $row->planet_id,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function npcTemplates(): array
    {
        try {
            return DB::connection('game')->table('npc_template')
                ->orderBy('id')
                ->get(['id', 'name', 'avatar', 'head', 'body', 'leg'])
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'avatar' => isset($row->avatar) ? (int) $row->avatar : null,
                    'head' => isset($row->head) ? (int) $row->head : null,
                    'body' => isset($row->body) ? (int) $row->body : null,
                    'leg' => isset($row->leg) ? (int) $row->leg : null,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function dropItemTemplates(): array
    {
        try {
            return DB::connection('game')->table('item_template')
                ->orderBy('id')
                ->get(['id', DB::raw('NAME as name'), DB::raw('TYPE as type'), 'icon_id'])
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'type' => (int) $row->type,
                    'icon_id' => isset($row->icon_id) ? (int) $row->icon_id : null,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
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
