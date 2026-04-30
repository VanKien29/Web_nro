<?php

namespace App\Services\Admin;

use App\Services\GameRuntimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlayerManagerService extends AdminServiceSupport
{
    private const ITEM_COLUMNS = [
        'body' => 'items_body',
        'bag' => 'items_bag',
        'box' => 'items_box',
    ];

    public function __construct(private readonly GameRuntimeService $runtime)
    {
    }

    public function list(Request $request): array
    {
        $game = DB::connection('game');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(10, min(100, (int) $request->query('per_page', 30)));
        $search = trim((string) $request->query('search', ''));

        $query = $game->table('player as p')
            ->leftJoin('account as a', 'a.id', '=', 'p.account_id')
            ->selectRaw('p.id, p.account_id, p.name, p.gender, p.head, p.data_point, p.data_inventory, p.items_bag, p.items_box, a.username, a.active, a.ban');

        if (Schema::connection('game')->hasColumn('player', 'power')) {
            $query->addSelect('p.power');
        }
        if (Schema::connection('game')->hasColumn('player', 'clan_id')) {
            $query->addSelect('p.clan_id');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('p.id', (int) $search)
                        ->orWhere('p.account_id', (int) $search);
                }
                $q->orWhere('p.name', 'LIKE', "%{$search}%")
                    ->orWhere('a.username', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('p.id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($row) => $this->playerListRow((array) $row))
            ->values();

        return [
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function show(int $id): array
    {
        $player = $this->playerRow($id);
        if (!$player) {
            return ['ok' => false, 'status' => 404, 'message' => 'Nhân vật không tồn tại'];
        }

        return ['ok' => true, 'data' => $this->playerDetail($player)];
    }

    public function updateStats(Request $request, int $id): array
    {
        $game = DB::connection('game');
        $player = $this->playerRow($id);
        if (!$player) {
            return ['ok' => false, 'status' => 404, 'message' => 'Nhân vật không tồn tại'];
        }

        $before = (array) $player;
        $inventory = $this->decodeArray($player->data_inventory ?? null);
        $point = $this->decodeArray($player->data_point ?? null);

        $this->setIndex($inventory, 0, $request->input('gold'));
        $this->setIndex($inventory, 1, $request->input('gem'));
        $this->setIndex($inventory, 2, $request->input('ruby'));

        $power = $this->numberOrNull($request->input('power'));
        $this->setIndex($point, 1, $power);
        $this->setIndex($point, 2, $request->input('potential'));
        $this->setIndex($point, 5, $request->input('hp'));
        $this->setIndex($point, 6, $request->input('ki'));
        $this->setIndex($point, 7, $request->input('dame'));
        $this->setIndex($point, 8, $request->input('def'));
        $this->setIndex($point, 9, $request->input('crit'));

        $updates = [
            'name' => trim((string) $request->input('name', $player->name)),
            'head' => max(0, (int) $request->input('head', $player->head ?? 0)),
            'data_inventory' => json_encode($inventory, JSON_UNESCAPED_UNICODE),
            'data_point' => json_encode($point, JSON_UNESCAPED_UNICODE),
        ];
        if (Schema::connection('game')->hasColumn('player', 'power')) {
            $updates['power'] = $power ?? (int) ($point[1] ?? $player->power ?? 0);
        }

        $game->table('player')->where('id', $id)->update($updates);
        $after = $this->playerRow($id);

        $this->logAdminAction(
            'player.stats.update',
            'player',
            $id,
            'Cập nhật chỉ số nhân vật ' . ($after->name ?? "#{$id}"),
            $this->sanitizeLogState($before),
            $this->sanitizeLogState((array) $after)
        );

        return [
            'ok' => true,
            'message' => 'Đã cập nhật thông tin nhân vật',
            'data' => $this->playerDetail($after),
        ];
    }

    public function inventorySearch(Request $request): array
    {
        $game = DB::connection('game');
        $playerId = (int) $request->query('player_id', 0);
        $search = trim((string) $request->query('search', ''));
        $location = (string) $request->query('location', 'all');
        $limit = max(20, min(500, (int) $request->query('limit', 120)));

        $query = $game->table('player')
            ->select(array_merge(['id', 'name'], array_values(self::ITEM_COLUMNS)));

        if ($playerId > 0) {
            $query->where('id', $playerId);
        } elseif ($search !== '' && is_numeric($search)) {
            $needle = '%' . $search . '%';
            $query->where(function ($q) use ($needle) {
                foreach (self::ITEM_COLUMNS as $column) {
                    $q->orWhere($column, 'LIKE', $needle);
                }
            });
        } elseif ($search === '') {
            $query->limit(80);
        } else {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $players = $query->limit($playerId > 0 ? 1 : 500)->get();
        $results = [];
        $itemIds = [];

        foreach ($players as $player) {
            foreach (self::ITEM_COLUMNS as $key => $column) {
                if ($location !== 'all' && $location !== $key) {
                    continue;
                }
                foreach ($this->parseItemList($player->{$column} ?? null, $key) as $slot) {
                    if (($slot['item_id'] ?? -1) < 0) {
                        continue;
                    }
                    if ($search !== '' && $playerId > 0 && !$this->slotMatches($slot, $search)) {
                        continue;
                    }
                    $slot['player_id'] = (int) $player->id;
                    $slot['player_name'] = (string) $player->name;
                    $results[] = $slot;
                    $itemIds[(int) $slot['item_id']] = true;
                    if (count($results) >= $limit) {
                        break 3;
                    }
                }
            }
        }

        $items = $this->itemTemplates(array_keys($itemIds));
        $results = array_map(function ($slot) use ($items) {
            $item = $items[(int) $slot['item_id']] ?? null;
            $slot['item_name'] = $item['name'] ?? ('Item #' . $slot['item_id']);
            $slot['icon_id'] = $item['icon_id'] ?? null;
            return $slot;
        }, $results);

        return ['ok' => true, 'data' => $results, 'total' => count($results)];
    }

    public function buffInventory(Request $request, int $id): array
    {
        $player = $this->playerRow($id);
        if (!$player) {
            return ['ok' => false, 'status' => 404, 'message' => 'Nhân vật không tồn tại'];
        }

        $itemId = (int) $request->input('temp_id', 0);
        $quantity = max(1, min(999999, (int) $request->input('quantity', 1)));
        $location = $this->normalizeLocation((string) $request->input('location', 'bag'));
        if ($itemId < 0) {
            return ['ok' => false, 'status' => 422, 'message' => 'Item không hợp lệ'];
        }

        $item = DB::connection('game')->table('item_template')->where('id', $itemId)->first(['id', 'NAME', 'icon_id']);
        if (!$item) {
            return ['ok' => false, 'status' => 404, 'message' => 'Item không tồn tại'];
        }

        $column = self::ITEM_COLUMNS[$location];
        $raw = $player->{$column} ?? '[]';
        $list = $this->decodeArray($raw);
        $slotText = $this->makeItemSlot($itemId, $quantity, $request->input('options', []));

        $inserted = false;
        foreach ($list as $index => $slot) {
            $parsed = $this->parseSlot($slot, (int) $index, $location);
            if (($parsed['item_id'] ?? 0) === -1) {
                $list[$index] = $slotText;
                $inserted = true;
                break;
            }
        }
        if (!$inserted) {
            $list[] = $slotText;
        }

        $before = (array) $player;
        DB::connection('game')->table('player')->where('id', $id)->update([
            $column => json_encode($list, JSON_UNESCAPED_UNICODE),
        ]);
        $after = $this->playerRow($id);
        $runtimeSync = $this->syncInventoryRuntime($id, $after);

        $this->logAdminAction(
            'player.inventory.buff',
            'player',
            $id,
            "Buff {$quantity} {$item->NAME} cho {$player->name}",
            $this->sanitizeLogState([$column => $before[$column] ?? null]),
            $this->sanitizeLogState([$column => $after->{$column} ?? null]),
            ['item_id' => $itemId, 'quantity' => $quantity, 'location' => $location]
        );

        return [
            'ok' => true,
            'message' => 'Đã thêm vật phẩm vào hành trang',
            'data' => $this->playerDetail($after),
            'runtime_synced' => $runtimeSync['ok'],
            'warning' => $runtimeSync['ok'] ? null : $runtimeSync['message'],
        ];
    }

    public function revokeInventory(Request $request, int $id): array
    {
        $player = $this->playerRow($id);
        if (!$player) {
            return ['ok' => false, 'status' => 404, 'message' => 'Nhân vật không tồn tại'];
        }

        $itemId = (int) $request->input('temp_id', 0);
        $location = (string) $request->input('location', 'all');
        $locations = $location === 'all'
            ? array_keys(self::ITEM_COLUMNS)
            : [$this->normalizeLocation($location)];

        $updates = [];
        $removed = 0;
        foreach ($locations as $key) {
            $column = self::ITEM_COLUMNS[$key];
            $list = $this->decodeArray($player->{$column} ?? null);
            foreach ($list as $index => $slot) {
                $parsed = $this->parseSlot($slot, (int) $index, $key);
                if ((int) ($parsed['item_id'] ?? -1) === $itemId) {
                    $list[$index] = $this->emptySlot();
                    $removed++;
                }
            }
            $updates[$column] = json_encode($list, JSON_UNESCAPED_UNICODE);
        }

        if ($removed <= 0) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy item cần thu hồi trong hành trang'];
        }

        $before = (array) $player;
        DB::connection('game')->table('player')->where('id', $id)->update($updates);
        $after = $this->playerRow($id);
        $runtimeSync = $this->syncInventoryRuntime($id, $after);

        $this->logAdminAction(
            'player.inventory.revoke',
            'player',
            $id,
            "Thu hồi {$removed} item #{$itemId} từ {$player->name}",
            $this->sanitizeLogState(array_intersect_key($before, $updates)),
            $this->sanitizeLogState(array_intersect_key((array) $after, $updates)),
            ['item_id' => $itemId, 'removed' => $removed, 'location' => $location]
        );

        return [
            'ok' => true,
            'message' => "Đã thu hồi {$removed} vật phẩm",
            'data' => $this->playerDetail($after),
            'removed' => $removed,
            'runtime_synced' => $runtimeSync['ok'],
            'warning' => $runtimeSync['ok'] ? null : $runtimeSync['message'],
        ];
    }

    private function playerListRow(array $row): array
    {
        $inventory = $this->decodeArray($row['data_inventory'] ?? null);
        $point = $this->decodeArray($row['data_point'] ?? null);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'account_id' => (int) ($row['account_id'] ?? 0),
            'username' => (string) ($row['username'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'gender' => isset($row['gender']) ? (int) $row['gender'] : null,
            'head' => isset($row['head']) ? (int) $row['head'] : null,
            'clan_id' => isset($row['clan_id']) ? (int) $row['clan_id'] : null,
            'active' => (int) ($row['active'] ?? 0),
            'ban' => (int) ($row['ban'] ?? 0),
            'power' => (int) ($row['power'] ?? $point[1] ?? 0),
            'gold' => (int) ($inventory[0] ?? 0),
            'gem' => (int) ($inventory[1] ?? 0),
            'ruby' => (int) ($inventory[2] ?? 0),
            'bag_count' => $this->filledSlotCount($row['items_bag'] ?? null),
            'box_count' => $this->filledSlotCount($row['items_box'] ?? null),
        ];
    }

    private function playerDetail(object $player): array
    {
        $inventory = $this->decodeArray($player->data_inventory ?? null);
        $point = $this->decodeArray($player->data_point ?? null);
        $locations = [];
        $ids = [];

        foreach (self::ITEM_COLUMNS as $key => $column) {
            $locations[$key] = $this->parseItemList($player->{$column} ?? null, $key);
            foreach ($locations[$key] as $slot) {
                if (($slot['item_id'] ?? -1) >= 0) {
                    $ids[(int) $slot['item_id']] = true;
                }
            }
        }

        $items = $this->itemTemplates(array_keys($ids));
        foreach ($locations as $key => $slots) {
            $locations[$key] = array_map(function ($slot) use ($items) {
                if (($slot['item_id'] ?? -1) >= 0) {
                    $item = $items[(int) $slot['item_id']] ?? null;
                    $slot['item_name'] = $item['name'] ?? ('Item #' . $slot['item_id']);
                    $slot['icon_id'] = $item['icon_id'] ?? null;
                }
                return $slot;
            }, $slots);
        }

        return [
            'summary' => $this->playerListRow((array) $player),
            'form' => [
                'name' => (string) ($player->name ?? ''),
                'head' => (int) ($player->head ?? 0),
                'power' => (int) ($player->power ?? $point[1] ?? 0),
                'gold' => (int) ($inventory[0] ?? 0),
                'gem' => (int) ($inventory[1] ?? 0),
                'ruby' => (int) ($inventory[2] ?? 0),
                'potential' => (int) ($point[2] ?? 0),
                'hp' => (int) ($point[5] ?? 0),
                'ki' => (int) ($point[6] ?? 0),
                'dame' => (int) ($point[7] ?? 0),
                'def' => (int) ($point[8] ?? 0),
                'crit' => (int) ($point[9] ?? 0),
            ],
            'inventory' => $locations,
        ];
    }

    private function playerRow(int $id): ?object
    {
        $game = DB::connection('game');
        $columns = ['p.id', 'p.account_id', 'p.name', 'p.gender', 'p.head', 'p.data_point', 'p.data_inventory'];
        foreach (array_values(self::ITEM_COLUMNS) as $column) {
            $columns[] = "p.{$column}";
        }
        foreach (['power', 'clan_id'] as $column) {
            if (Schema::connection('game')->hasColumn('player', $column)) {
                $columns[] = "p.{$column}";
            }
        }

        return $game->table('player as p')
            ->leftJoin('account as a', 'a.id', '=', 'p.account_id')
            ->select($columns)
            ->selectRaw('a.username, a.active, a.ban')
            ->where('p.id', $id)
            ->first();
    }

    private function decodeArray($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($this->fixJson($value), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function parseItemList($value, string $location): array
    {
        return collect($this->decodeArray($value))
            ->map(fn($slot, $index) => $this->parseSlot($slot, (int) $index, $location))
            ->values()
            ->all();
    }

    private function parseSlot($slot, int $index, string $location): array
    {
        $raw = $slot;
        if (is_string($slot)) {
            $slot = json_decode($this->fixJson($slot), true);
        }
        if (!is_array($slot)) {
            $slot = [-1, 0, '[]', 0];
        }

        $optionsRaw = $slot[2] ?? '[]';
        $options = is_array($optionsRaw) ? $optionsRaw : $this->decodeArray((string) $optionsRaw);

        return [
            'location' => $location,
            'slot' => $index,
            'item_id' => (int) ($slot[0] ?? -1),
            'quantity' => (int) ($slot[1] ?? 0),
            'options' => collect($options)->filter(fn($option) => is_array($option))->map(fn($option) => [
                'id' => (int) ($option['id'] ?? $option[0] ?? 0),
                'param' => (int) ($option['param'] ?? $option[1] ?? 0),
            ])->values()->all(),
            'time' => (int) ($slot[3] ?? 0),
            'raw' => is_string($raw) ? $raw : json_encode($raw, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function itemTemplates(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id >= 0)));
        if (!$ids) {
            return [];
        }

        return DB::connection('game')->table('item_template')
            ->selectRaw('id, NAME as name, icon_id, TYPE as type, gender')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn($item) => [(int) $item->id => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'icon_id' => isset($item->icon_id) ? (int) $item->icon_id : null,
                'type' => isset($item->type) ? (int) $item->type : null,
                'gender' => isset($item->gender) ? (int) $item->gender : null,
            ]])
            ->all();
    }

    private function filledSlotCount($value): int
    {
        return collect($this->parseItemList($value, 'bag'))
            ->filter(fn($slot) => ($slot['item_id'] ?? -1) >= 0)
            ->count();
    }

    private function slotMatches(array $slot, string $search): bool
    {
        if ($search === '') {
            return true;
        }
        if (is_numeric($search) && (int) $slot['item_id'] === (int) $search) {
            return true;
        }

        return false;
    }

    private function normalizeLocation(string $location): string
    {
        return array_key_exists($location, self::ITEM_COLUMNS) ? $location : 'bag';
    }

    private function makeItemSlot(int $itemId, int $quantity, $options): string
    {
        $options = collect(is_array($options) ? $options : [])
            ->filter(fn($option) => is_array($option))
            ->map(fn($option) => [
                'id' => (int) ($option['id'] ?? 0),
                'param' => (int) ($option['param'] ?? 0),
            ])
            ->values()
            ->all();

        return json_encode([
            $itemId,
            $quantity,
            json_encode($options, JSON_UNESCAPED_UNICODE),
            (int) floor(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function emptySlot(): string
    {
        return json_encode([-1, 0, '[]', (int) floor(microtime(true) * 1000)], JSON_UNESCAPED_UNICODE);
    }

    private function syncInventoryRuntime(int $playerId, ?object $player): array
    {
        if (!$player) {
            return ['ok' => false, 'message' => 'Đã cập nhật DB nhưng không tải lại được dữ liệu để đồng bộ server.'];
        }

        try {
            $response = $this->runtime->syncPlayerInventory([
                'player_id' => $playerId,
                'items_body' => (string) ($player->items_body ?? '[]'),
                'items_bag' => (string) ($player->items_bag ?? '[]'),
                'items_box' => (string) ($player->items_box ?? '[]'),
            ]);

            if (($response['ok'] ?? false) !== true) {
                return [
                    'ok' => false,
                    'message' => 'Đã cập nhật DB nhưng server game chưa đồng bộ realtime: ' . (string) ($response['message'] ?? 'Không rõ lỗi'),
                ];
            }

            return ['ok' => true, 'message' => (string) ($response['message'] ?? 'Đã đồng bộ realtime cho server game.')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Đã cập nhật DB nhưng lỗi đồng bộ runtime: ' . $e->getMessage()];
        }
    }

    private function setIndex(array &$arr, int $index, $value): void
    {
        $number = $this->numberOrNull($value);
        if ($number === null) {
            return;
        }
        while (count($arr) <= $index) {
            $arr[] = 0;
        }
        $arr[$index] = $number;
    }

    private function numberOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) preg_replace('/[^0-9-]/', '', (string) $value);
    }
}
