<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GameCatalogService extends AdminServiceSupport
{
    public function listItems(Request $request): array
    {
        $search = $request->query('search', '');
        $type = $this->normalizeFilterValue($request->query('type'));
        $gender = $this->normalizeFilterValue($request->query('gender'));
        $lite = $request->boolean('lite');
        $perPage = max(1, min((int) $request->query('per_page', 50), 200));
        $page = max((int) $request->query('page', 1), 1);

        $usingWebIndex = $this->webItemIndexReady();
        $webIndexHasGender = false;
        if ($usingWebIndex) {
            try {
                $webIndexHasGender = Schema::hasColumn('game_item_indexes', 'gender');
            } catch (\Throwable) {
                $webIndexHasGender = false;
            }

            if ($gender !== null && !$webIndexHasGender) {
                $usingWebIndex = false;
            }
        }

        $query = $usingWebIndex
            ? DB::table('game_item_indexes')->selectRaw($lite
                ? ('id, name, type, icon_id' . ($webIndexHasGender ? ', gender' : ''))
                : ('id, name, type, icon_id, part, head, body, leg, description, is_up_to_up' . ($webIndexHasGender ? ', gender' : '')))
            : DB::connection('game')->table('item_template')->selectRaw($lite
                ? 'id, NAME as name, TYPE as type, gender, icon_id'
                : 'id, NAME as name, TYPE as type, gender, icon_id, part, head, body, leg, description, is_up_to_up');

        if ($search) {
            $query->where(function ($q) use ($search, $usingWebIndex) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                    return;
                }

                if ($usingWebIndex) {
                    $q->where(function ($nested) use ($search) {
                        $nested->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('normalized_name', 'LIKE', '%' . mb_strtolower($search) . '%');
                    });
                    return;
                }

                $q->where('NAME', 'LIKE', "%{$search}%");
            });
        }

        if ($type !== null) {
            $query->where('type', $type);
        }
        if ($gender !== null) {
            $query->where('gender', $gender);
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $partMap = $lite ? [] : $this->partMapForItems($items);

        $items = $items->map(function ($item) use ($partMap, $lite) {
            $item->id = (int) $item->id;
            $item->type = isset($item->type) ? (int) $item->type : null;
            $item->gender = isset($item->gender) ? (int) $item->gender : null;
            $item->icon_id = isset($item->icon_id) ? (int) $item->icon_id : 0;

            if ($lite) {
                return $item;
            }

            $item->part = isset($item->part) ? (int) $item->part : -1;
            $item->head = isset($item->head) ? (int) $item->head : -1;
            $item->body = isset($item->body) ? (int) $item->body : -1;
            $item->leg = isset($item->leg) ? (int) $item->leg : -1;
            $item->is_up_to_up = !empty($item->is_up_to_up);
            $item->part_preview = [
                'part' => $item->part >= 0 ? ($partMap[$item->part] ?? null) : null,
                'head' => $item->head >= 0 ? ($partMap[$item->head] ?? null) : null,
                'body' => $item->body >= 0 ? ($partMap[$item->body] ?? null) : null,
                'leg' => $item->leg >= 0 ? ($partMap[$item->leg] ?? null) : null,
            ];

            return $item;
        })->values();

        $typeOptions = $this->itemTypeOptions($usingWebIndex);
        $types = array_map(fn($option) => (int) $option['id'], $typeOptions);

        return [
            'ok' => true,
            'data' => $items,
            'part_map' => $partMap,
            'types' => $types,
            'type_options' => $typeOptions,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function batchItems(string $idsParam): array
    {
        if ($idsParam === '') {
            return [];
        }

        $ids = array_map('intval', array_filter(explode(',', $idsParam), 'is_numeric'));
        if (!$ids) {
            return [];
        }

        $ids = array_values(array_unique(array_slice($ids, 0, 1000)));
        sort($ids);

        return DB::connection('game')->table('item_template')
            ->selectRaw('id, NAME as name, TYPE as type, gender, icon_id')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    (int) $item->id => [
                        'id' => (int) $item->id,
                        'name' => (string) ($item->name ?? ''),
                        'type' => isset($item->type) ? (int) $item->type : null,
                        'gender' => isset($item->gender) ? (int) $item->gender : null,
                        'icon_id' => isset($item->icon_id) ? (int) $item->icon_id : null,
                    ],
                ];
            })
            ->all();
    }

    public function updateItem(Request $request, int $id): array
    {
        $game = DB::connection('game');
        $table = $game->table('item_template');
        $before = $table->where('id', $id)->first();

        if (!$before) {
            return [
                'ok' => false,
                'status' => 404,
                'message' => 'Item không tồn tại',
            ];
        }

        $data = [
            'NAME' => (string) $request->input('name', ''),
            'TYPE' => (int) $request->input('type', 0),
            'gender' => (int) $request->input('gender', 3),
            'icon_id' => (int) $request->input('icon_id', 0),
            'part' => (int) $request->input('part', -1),
            'head' => (int) $request->input('head', -1),
            'body' => (int) $request->input('body', -1),
            'leg' => (int) $request->input('leg', -1),
            'description' => (string) $request->input('description', ''),
            'is_up_to_up' => $request->boolean('is_up_to_up') ? 1 : 0,
        ];

        foreach (array_keys($data) as $column) {
            if (!Schema::connection('game')->hasColumn('item_template', $column)) {
                unset($data[$column]);
            }
        }

        if (!$data) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Không có cột item nào có thể cập nhật',
            ];
        }

        $table->where('id', $id)->update($data);
        $this->clearItemTypeOptionCache();

        $after = $game->table('item_template')->where('id', $id)->first();
        $this->logAdminAction(
            'item.update',
            'item',
            $id,
            "Cập nhật item #{$id} " . ($data['NAME'] ?? ($before->NAME ?? '')),
            $this->sanitizeLogState((array) $before),
            $this->sanitizeLogState((array) $after)
        );

        return [
            'ok' => true,
            'message' => 'Đã cập nhật item',
            'data' => $after,
        ];
    }

    public function itemOptions(): array
    {
        return $this->optionQuery()
            ->orderBy('id')
            ->get()
            ->map(fn($option) => [
                'id' => (int) $option->id,
                'name' => (string) $option->name,
            ])
            ->all();
    }

    public function searchItems(string $query, int $limit = 30): array
    {
        $query = trim($query);
        $usingWebIndex = $this->webItemIndexReady();
        $builder = $usingWebIndex
            ? DB::table('game_item_indexes')->select('id', 'name', 'icon_id')
            : DB::connection('game')->table('item_template')->select('id', 'NAME as name', 'icon_id');

        if ($query !== '') {
            if (is_numeric($query)) {
                $builder->where('id', (int) $query);
            } elseif ($usingWebIndex) {
                $builder->where(function ($nested) use ($query) {
                    $nested->where('name', 'like', "%{$query}%")
                        ->orWhere('normalized_name', 'like', '%' . mb_strtolower($query) . '%');
                });
            } else {
                $builder->where('NAME', 'like', "%{$query}%");
            }
        }

        return $builder->orderBy('id')
            ->limit(max(1, min($limit, 200)))
            ->get()
            ->map(fn($item) => [
                'id' => (int) $item->id,
                'name' => (string) ($item->name ?? ''),
                'icon_id' => isset($item->icon_id) ? (int) $item->icon_id : 0,
            ])
            ->all();
    }

    public function optionsForRequest(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 30), 1), 200);
        $page = max((int) $request->query('page', 1), 1);
        $paginate = $request->boolean('paginate') || $request->has('page') || $search !== '';

        $builder = $this->optionQuery();

        if ($search !== '') {
            if (is_numeric($search)) {
                $builder->where('id', (int) $search);
            } elseif ($this->webOptionIndexReady()) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('normalized_name', 'like', '%' . mb_strtolower($search) . '%');
                });
            } else {
                $builder->where('NAME', 'like', "%{$search}%");
            }
        }

        if ($paginate) {
            $total = (clone $builder)->count();
            $options = $builder->orderBy('id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return [
                'paginated' => true,
                'payload' => [
                    'ok' => true,
                    'data' => $options,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $perPage),
                ],
            ];
        }

        $options = Cache::remember('admin:item_option_template:all:v2', now()->addMinutes(30), function () use ($builder) {
            return $builder->orderBy('id')
                ->get()
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                ])
                ->values()
                ->all();
        });

        return [
            'paginated' => false,
            'payload' => $options,
        ];
    }

    private function normalizeFilterValue(mixed $value): int|string|null
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || in_array(strtolower($normalized), ['undefined', 'null', 'nan'], true)) {
            return null;
        }

        return is_numeric($normalized) ? (int) $normalized : $normalized;
    }

    private function partMapForItems(iterable $items): array
    {
        $partIds = [];
        foreach ($items as $item) {
            foreach (['part', 'head', 'body', 'leg'] as $field) {
                $value = isset($item->{$field}) ? (int) $item->{$field} : -1;
                if ($value >= 0) {
                    $partIds[$value] = true;
                }
            }
        }

        if (!$partIds) {
            return [];
        }

        $partMap = [];
        $parts = DB::connection('game')->table('part')
            ->whereIn('id', array_keys($partIds))
            ->orderBy('id')
            ->get(['id', 'TYPE as type', 'DATA as data']);

        foreach ($parts as $part) {
            $layers = $this->decodePartData($part->data);
            $partMap[(int) $part->id] = [
                'id' => (int) $part->id,
                'type' => (int) $part->type,
                'type_name' => $this->partTypeName((int) $part->type),
                'layers' => $layers,
                'layer_count' => count($layers),
            ];
        }

        return $partMap;
    }

    private function itemTypeOptions(bool $usingWebIndex): array
    {
        if ($usingWebIndex && $this->webItemTypeIndexReady()) {
            return Cache::remember('admin:item_type_options:web:v1', now()->addMinutes(15), function () {
                return DB::table('game_item_type_indexes')
                    ->orderBy('id')
                    ->get(['id', 'name', 'item_count'])
                    ->map(fn($row) => [
                        'id' => (int) $row->id,
                        'name' => (string) $row->name,
                        'item_count' => (int) ($row->item_count ?? 0),
                    ])
                    ->values()
                    ->all();
            });
        }

        return Cache::remember('admin:item_type_options:v3', now()->addMinutes(15), function () {
            $typeRows = DB::connection('game')->table('item_template')
                ->selectRaw('type as id, COUNT(*) as item_count')
                ->whereNotNull('type')
                ->groupBy('type')
                ->orderBy('type')
                ->get();

            $typeIds = $typeRows->pluck('id')->map(fn($id) => (int) $id)->values();

            $nameById = DB::connection('game')->table('type_item')
                ->whereIn('id', $typeIds)
                ->where('NAME', '<>', '.')
                ->pluck('NAME', 'id');

            $nameByIndex = [];
            $indexRows = DB::connection('game')->table('type_item')
                ->whereIn('index_body', $typeIds)
                ->where('index_body', '>=', 0)
                ->where('NAME', '<>', '.')
                ->orderBy('id')
                ->get(['index_body', 'NAME']);

            foreach ($indexRows as $row) {
                $idx = (int) $row->index_body;
                if (!isset($nameByIndex[$idx])) {
                    $nameByIndex[$idx] = (string) $row->NAME;
                }
            }

            $countByType = [];
            foreach ($typeRows as $row) {
                $countByType[(int) $row->id] = (int) $row->item_count;
            }

            $options = [];
            foreach ($typeIds as $typeId) {
                $name = $nameById[$typeId] ?? ($nameByIndex[$typeId] ?? ('Type ' . $typeId));
                $options[] = [
                    'id' => (int) $typeId,
                    'name' => (string) $name,
                    'item_count' => (int) ($countByType[$typeId] ?? 0),
                ];
            }

            return $options;
        });
    }

    private function optionQuery()
    {
        return $this->webOptionIndexReady()
            ? DB::table('game_item_option_indexes')->select('id', 'name')
            : DB::connection('game')->table('item_option_template')->select('id', 'NAME as name');
    }

    private function clearItemTypeOptionCache(): void
    {
        Cache::forget('admin:item_type_options:v2');
        Cache::forget('admin:item_type_options:v3');
        Cache::forget('admin:item_type_options:web:v1');
    }

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

    private function decodePartData(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $normalized = trim($raw);
        $normalized = str_replace('\\"', '"', $normalized);
        $decoded = json_decode($this->fixJson($normalized), true);
        if (is_string($decoded)) {
            $decoded = json_decode($this->fixJson($decoded), true);
        }

        if (!is_array($decoded)) {
            return [];
        }

        $layers = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $layers[] = [
                'icon_id' => isset($entry[0]) ? (int) $entry[0] : 0,
                'dx' => isset($entry[1]) ? (int) $entry[1] : 0,
                'dy' => isset($entry[2]) ? (int) $entry[2] : 0,
            ];
        }

        return $layers;
    }

    private function partTypeName(int $type): string
    {
        return match ($type) {
            0 => 'Đầu',
            1 => 'Thân',
            2 => 'Chân',
            default => 'TYPE ' . $type,
        };
    }

    private function webItemIndexReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            if (!Schema::hasTable('game_item_indexes')) {
                $ready = false;
                return $ready;
            }

            $indexCount = DB::table('game_item_indexes')->count();
            if ($indexCount <= 0) {
                $ready = false;
                return $ready;
            }

            $gameCount = DB::connection('game')->table('item_template')->count();
            $ready = $indexCount === $gameCount;
        } catch (\Throwable) {
            $ready = false;
        }

        return $ready;
    }

    private function webItemTypeIndexReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            if (!Schema::hasTable('game_item_type_indexes')) {
                $ready = false;
                return $ready;
            }

            $indexCount = DB::table('game_item_type_indexes')->count();
            if ($indexCount <= 0) {
                $ready = false;
                return $ready;
            }

            $gameCount = DB::connection('game')->table('item_template')
                ->whereNotNull('TYPE')
                ->selectRaw('COUNT(DISTINCT TYPE) as aggregate')
                ->value('aggregate');

            $ready = $indexCount === (int) $gameCount;
        } catch (\Throwable) {
            $ready = false;
        }

        return $ready;
    }

    private function webOptionIndexReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            if (!Schema::hasTable('game_item_option_indexes')) {
                $ready = false;
                return $ready;
            }

            $indexCount = DB::table('game_item_option_indexes')->count();
            if ($indexCount <= 0) {
                $ready = false;
                return $ready;
            }

            $gameCount = DB::connection('game')->table('item_option_template')->count();
            $ready = $indexCount === $gameCount;
        } catch (\Throwable) {
            $ready = false;
        }

        return $ready;
    }
}
