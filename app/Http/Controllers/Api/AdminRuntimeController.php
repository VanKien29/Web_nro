<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Admin\RuntimeSupportService;
use App\Services\GameRuntimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRuntimeController extends Controller
{
    public function __construct(private readonly RuntimeSupportService $support)
    {
    }

    public function reloadShop(GameRuntimeService $runtime): JsonResponse
    {
        $response = $this->support->runtimeResult('shop.reload', fn() => $runtime->reloadShop());

        return response()->json($response['body'], $response['status']);
    }

    public function health(GameRuntimeService $runtime): JsonResponse
    {
        return $this->runtimeResponse('runtime.health', fn() => $runtime->health());
    }

    public function bosses(GameRuntimeService $runtime): JsonResponse
    {
        return $this->runtimeResponse('boss.list', function () use ($runtime) {
            $result = $runtime->bosses();
            if (!empty($result['ok']) && !empty($result['data']['bosses']) && is_array($result['data']['bosses'])) {
                $result['data']['bosses'] = $this->support->attachBossAvatars($result['data']['bosses']);
            }
            if (!empty($result['ok'])) {
                $result['data']['skill_options'] = $this->support->skillOptions();
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
        foreach (['index', 'level_index', 'hp', 'hp_max', 'dame', 'seconds_rest', 'gender'] as $field) {
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
                $result['data']['maps'] = $this->support->enrichMapsStaticData($result['data']['maps']);
                $result['data']['map_options'] = $this->support->mapOptions();
                $result['data']['npc_templates'] = $this->support->npcTemplates();
                $result['data']['item_templates'] = $this->support->dropItemTemplates();
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
                $maps = $this->support->enrichMapsStaticData([$result['data']['map']]);
                $result['data']['map'] = $maps[0] ?? $result['data']['map'];
            }

            return $result;
        });
    }

    public function buffMail(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $items = collect($request->input('items', []))
            ->filter(fn($item) => is_array($item))
            ->map(fn($item) => [
                'temp_id' => (int) ($item['temp_id'] ?? -1),
                'quantity' => max(1, min(99999, (int) ($item['quantity'] ?? 1))),
                'options' => collect($item['options'] ?? [])
                    ->filter(fn($option) => is_array($option))
                    ->map(fn($option) => [
                        'id' => (int) ($option['id'] ?? 0),
                        'param' => (int) ($option['param'] ?? 0),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn($item) => $item['temp_id'] >= 0)
            ->values()
            ->all();

        if (trim((string) $request->input('target', '')) === '') {
            return response()->json(['ok' => false, 'message' => 'Chưa nhập tên nhân vật nhận buff.'], 422);
        }
        if (!$items) {
            return response()->json(['ok' => false, 'message' => 'Chưa chọn vật phẩm để buff.'], 422);
        }

        $payload = [
            'target' => trim((string) $request->input('target')),
            'items' => $items,
            'notify' => $request->boolean('notify', true),
        ];

        return $this->runtimeResponse('buff.mail', fn() => $runtime->buffMail($payload));
    }

    public function buffAccount(Request $request, GameRuntimeService $runtime): JsonResponse
    {
        $target = trim((string) $request->input('target', ''));
        $payload = [
            'target_type' => in_array($request->input('target_type'), ['player_name', 'username', 'account_id'], true)
                ? $request->input('target_type')
                : 'player_name',
            'target' => $target,
            'cash' => max(0, (int) $request->input('cash', 0)),
            'danap' => max(0, (int) $request->input('danap', 0)),
            'active' => $request->boolean('active'),
            'notify' => $request->boolean('notify', true),
        ];

        if ($target === '') {
            return response()->json(['ok' => false, 'message' => 'Chưa nhập account hoặc nhân vật nhận buff.'], 422);
        }
        if ($payload['cash'] === 0 && $payload['danap'] === 0 && !$payload['active']) {
            return response()->json(['ok' => false, 'message' => 'Chưa nhập cash, danap hoặc bật active.'], 422);
        }

        return $this->runtimeResponse('buff.account', fn() => $runtime->buffAccount($payload));
    }

    private function runtimeResponse(string $action, callable $callback): JsonResponse
    {
        $response = $this->support->runtimeResult($action, $callback);

        return response()->json($response['body'], $response['status']);
    }
}
