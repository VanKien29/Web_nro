<?php

namespace App\Services\Admin;

use App\Models\AdminActionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RuntimeSupportService
{
    public function runtimeResult(string $action, callable $callback): array
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

        return ['body' => $result, 'status' => $status];
    }

    public function attachBossAvatars(array $bosses): array
    {
        $headIds = [];
        foreach ($bosses as $boss) {
            $outfit = is_array($boss['outfit'] ?? null) ? $boss['outfit'] : [];
            $headId = (int) ($outfit[0] ?? -1);
            if ($headId >= 0) {
                $headIds[$headId] = true;
            }
            foreach (($boss['levels_data'] ?? []) as $level) {
                $levelOutfit = is_array($level['outfit'] ?? null) ? $level['outfit'] : [];
                $levelHeadId = (int) ($levelOutfit[0] ?? -1);
                if ($levelHeadId >= 0) {
                    $headIds[$levelHeadId] = true;
                }
            }
        }

        $avatarByHead = [];
        if ($headIds) {
            try {
                $rows = DB::connection('game')->table('head_avatar')
                    ->whereIn('head_id', array_keys($headIds))
                    ->orderBy('head_id')
                    ->orderBy('avatar_id')
                    ->get(['head_id', 'avatar_id']);
                foreach ($rows as $row) {
                    $headId = (int) $row->head_id;
                    $avatarByHead[$headId] ??= (int) $row->avatar_id;
                }
            } catch (\Throwable) {
                $avatarByHead = [];
            }
        }

        foreach ($bosses as &$boss) {
            $outfit = is_array($boss['outfit'] ?? null) ? $boss['outfit'] : [];
            $headId = (int) ($outfit[0] ?? -1);
            $boss['head_id'] = $headId;
            $boss['avatar_id'] = $headId >= 0 ? ($avatarByHead[$headId] ?? null) : null;
            if (!empty($boss['levels_data']) && is_array($boss['levels_data'])) {
                foreach ($boss['levels_data'] as &$level) {
                    $levelOutfit = is_array($level['outfit'] ?? null) ? $level['outfit'] : [];
                    $levelHeadId = (int) ($levelOutfit[0] ?? -1);
                    $level['head_id'] = $levelHeadId;
                    $level['avatar_id'] = $levelHeadId >= 0 ? ($avatarByHead[$levelHeadId] ?? null) : null;
                }
                unset($level);
            }
        }
        unset($boss);

        return $bosses;
    }

    public function enrichMapsStaticData(array $maps): array
    {
        $mapIds = collect($maps)->pluck('id')->filter()->map(fn($id) => (int) $id)->values()->all();
        if (!$mapIds) {
            return $maps;
        }

        $rows = DB::connection('game')->table('map_template')
            ->select('id', 'zones', 'waypoints', 'npcs')
            ->whereIn('id', $mapIds)
            ->get()
            ->keyBy('id');

        $fixedItemIds = [74, 78, 372, 373, 374, 375, 376, 377, 378];
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

    public function skillOptions(): array
    {
        try {
            $rows = DB::connection('game')->table('skill_template')
                ->orderBy('nclass_id')
                ->orderBy('slot')
                ->get(['id', 'nclass_id', DB::raw('`NAME` as name'), 'max_point', 'icon_id', 'skills']);
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

    public function mapOptions(): array
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

    public function npcTemplates(): array
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

    public function dropItemTemplates(): array
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

    private function logRuntimeAction(string $action, array $result, ?\Throwable $error = null): void
    {
        try {
            $admin = Auth::guard('admin')->user();
            AdminActionLog::create([
                'admin_user_id' => $admin?->id,
                'admin_username' => $admin?->username ?? $admin?->name ?? 'admin',
                'action' => $action,
                'target_type' => 'runtime',
                'target_id' => str_starts_with($action, 'boss')
                    ? 'boss'
                    : (str_starts_with($action, 'map_mob')
                        ? 'map_mob'
                        : (str_starts_with($action, 'buff') ? 'buff' : 'shop')),
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
        } catch (\Throwable) {
            //
        }
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

        return collect($decoded)->map(fn($row) => [
            'name' => (string) (($row = is_array($row) ? $row : [])[0] ?? ''),
            'min_x' => (int) ($row[1] ?? 0),
            'min_y' => (int) ($row[2] ?? 0),
            'max_x' => (int) ($row[3] ?? 0),
            'max_y' => (int) ($row[4] ?? 0),
            'is_enter' => (int) ($row[5] ?? 0) === 1,
            'is_offline' => (int) ($row[6] ?? 0) === 1,
            'go_map' => (int) ($row[7] ?? 0),
            'go_x' => (int) ($row[8] ?? 0),
            'go_y' => (int) ($row[9] ?? 0),
        ])->values()->all();
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

        return collect($levels)->filter(fn($level) => is_array($level))->map(fn($level) => [
            'skill_id' => (int) ($level['id'] ?? 0),
            'point' => (int) ($level['point'] ?? 0),
            'cool_down' => (int) ($level['cool_down'] ?? 0),
            'damage' => (int) ($level['damage'] ?? 0),
            'mana_use' => (int) ($level['mana_use'] ?? 0),
        ])->values()->all();
    }
}
