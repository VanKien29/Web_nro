<?php

namespace App\Services\Admin;

use App\Models\AdminActionLog;
use App\Models\Game\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountService extends AdminServiceSupport
{
    public function list(Request $request): array
    {
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);
        $limit = 20;

        $query = Account::query()
            ->select(['id', 'username', 'ban', 'is_admin', 'active', 'cash', 'danap', 'coin', 'diem_da_nhan'])
            ->selectRaw('`DiemDanh` as diem_danh')
            ->with(['player:id,account_id,name,gender,data_point']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhereHas('player', fn($pq) => $pq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $total = $query->count();
        $rows = $query->orderBy('id')->offset(($page - 1) * $limit)->limit($limit)->get()
            ->map(fn($acc) => [
                'id' => (int) $acc->id,
                'username' => $acc->username,
                'ban' => (int) ($acc->ban ?? 0),
                'is_admin' => (int) ($acc->is_admin ?? 0),
                'active' => (int) ($acc->active ?? 0),
                'cash' => (int) ($acc->cash ?? 0),
                'danap' => (int) ($acc->danap ?? 0),
                'coin' => (int) ($acc->coin ?? 0),
                'diem_da_nhan' => (int) ($acc->diem_da_nhan ?? 0),
                'diem_danh' => (int) ($acc->diem_danh ?? 0),
                'player_name' => $acc->player?->name,
                'player_gender' => $acc->player ? (int) $acc->player->gender : null,
                'player_power' => $acc->player ? (int) $acc->player->power : 0,
            ]);

        return [
            'ok' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ];
    }

    public function get(int $id): array
    {
        $account = Account::query()->with('player')->find($id);
        if (!$account) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản không tồn tại'];
        }

        $player = $account->player;

        return [
            'ok' => true,
            'data' => [
                'id' => (int) $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'gmail' => $account->gmail,
                'mkc2' => $account->mkc2,
                'ip_address' => $account->ip_address,
                'create_time' => $account->create_time,
                'update_time' => $account->update_time,
                'last_time_login' => $account->last_time_login,
                'last_time_logout' => $account->last_time_logout,
                'ban' => (int) ($account->ban ?? 0),
                'is_admin' => (int) ($account->is_admin ?? 0),
                'active' => (int) ($account->active ?? 0),
                'cash' => (int) ($account->cash ?? 0),
                'danap' => (int) ($account->danap ?? 0),
                'coin' => (int) ($account->coin ?? 0),
                'diem_da_nhan' => (int) ($account->diem_da_nhan ?? 0),
                'diem_danh' => (int) ($account->DiemDanh ?? $account->diem_danh ?? 0),
                'luotquay' => (int) ($account->luotquay ?? 0),
                'vang' => (int) ($account->vang ?? 0),
                'event_point' => (int) ($account->event_point ?? 0),
                'last_diem_danh' => $account->lastDiemDanh,
                'player' => $player ? [
                    'id' => (int) $player->id,
                    'name' => $player->name,
                    'gender' => (int) ($player->gender ?? 0),
                    'head' => (int) ($player->head ?? 0),
                    'power' => (int) ($player->power ?? 0),
                    'task' => $this->buildTaskSummary($player->data_task ?? null),
                ] : null,
            ],
        ];
    }

    public function activity(int $id): array
    {
        $account = Account::query()->find($id);
        if (!$account) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản không tồn tại'];
        }

        $game = DB::connection('game');
        $topups = $game->table('topup_transactions')
            ->select('id', 'trans_id', 'amount', 'currency', 'source', 'note', 'created_at')
            ->where('user_id', $id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
        $topupSummary = $game->table('topup_transactions')
            ->where('user_id', $id)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount, MAX(created_at) as last_topup_at')
            ->first();

        try {
            $cardLogs = $game->table('trans_log')
                ->select('id', 'seri', 'pin', 'type', 'amount', 'trans_id', 'status', 'created_at', 'updated_at')
                ->where('username', $account->username)
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'seri' => (string) ($row->seri ?? '') !== '' ? substr((string) $row->seri, 0, 4) . '...' . substr((string) $row->seri, -4) : '',
                    'pin' => (string) ($row->pin ?? '') !== '' ? substr((string) $row->pin, 0, 4) . '...' . substr((string) $row->pin, -4) : '',
                    'type' => (string) ($row->type ?? ''),
                    'amount' => (int) ($row->amount ?? 0),
                    'trans_id' => (string) ($row->trans_id ?? ''),
                    'status' => (int) ($row->status ?? 0),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ])
                ->values();
        } catch (\Throwable) {
            $cardLogs = collect();
        }

        $adminLogs = AdminActionLog::query()
            ->where('target_type', 'account')
            ->where('target_id', (string) $id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return [
            'ok' => true,
            'data' => [
                'overview' => [
                    'id' => (int) $account->id,
                    'username' => $account->username,
                    'email' => $account->email,
                    'gmail' => $account->gmail,
                    'ip_address' => $account->ip_address,
                    'create_time' => $account->create_time,
                    'update_time' => $account->update_time,
                    'last_time_login' => $account->last_time_login,
                    'last_time_logout' => $account->last_time_logout,
                    'active' => (int) ($account->active ?? 0),
                    'ban' => (int) ($account->ban ?? 0),
                    'cash' => (int) ($account->cash ?? 0),
                    'danap' => (int) ($account->danap ?? 0),
                    'coin' => (int) ($account->coin ?? 0),
                    'vang' => (int) ($account->vang ?? 0),
                    'event_point' => (int) ($account->event_point ?? 0),
                    'luotquay' => (int) ($account->luotquay ?? 0),
                    'diem_danh' => (int) ($account->DiemDanh ?? $account->diem_danh ?? 0),
                    'mkc2' => $account->mkc2,
                ],
                'topup_summary' => [
                    'total_count' => (int) ($topupSummary->total_count ?? 0),
                    'total_amount' => (int) ($topupSummary->total_amount ?? 0),
                    'last_topup_at' => $topupSummary->last_topup_at ?? null,
                ],
                'topups' => $topups,
                'card_logs' => $cardLogs,
                'admin_logs' => $adminLogs,
            ],
        ];
    }

    public function playerFull(int $id): array
    {
        if (!Account::find($id)) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản không tồn tại'];
        }

        $game = DB::connection('game');
        $columns = ['id', 'account_id', 'name', 'gender', 'head', 'data_point', 'data_inventory', 'data_location', 'data_task'];
        foreach (['power', 'dataBadges'] as $column) {
            if (Schema::connection('game')->hasColumn('player', $column)) {
                $columns[] = $column;
            }
        }

        $player = $game->table('player')->select($columns)->where('account_id', $id)->first();
        if (!$player) {
            return ['ok' => true, 'data' => null, 'message' => 'Tài khoản chưa có nhân vật'];
        }

        $raw = (array) $player;
        $point = $this->decodeArray($raw['data_point'] ?? null);
        $displayFields = $this->playerDisplayFields();

        return [
            'ok' => true,
            'data' => [
                'summary' => [
                    'id' => (int) ($raw['id'] ?? 0),
                    'account_id' => (int) ($raw['account_id'] ?? 0),
                    'name' => $raw['name'] ?? '',
                    'gender' => isset($raw['gender']) ? (int) $raw['gender'] : null,
                    'head' => isset($raw['head']) ? (int) $raw['head'] : null,
                    'power' => isset($raw['power']) ? (int) $raw['power'] : (isset($point[1]) ? (int) $point[1] : 0),
                    'task' => $this->buildTaskSummary($raw['data_task'] ?? null),
                ],
                'parsed' => [
                    'data_inventory' => $this->parseIndexedData($raw['data_inventory'] ?? null, [0 => 'Vàng', 1 => 'Ngọc xanh', 2 => 'Hồng ngọc (ruby)', 3 => 'Coupon', 4 => 'Điểm sự kiện túi']),
                    'data_location' => $this->parseIndexedData($raw['data_location'] ?? null, [0 => 'Map ID', 1 => 'Tọa độ X', 2 => 'Tọa độ Y']),
                    'data_point' => $this->parseIndexedData($raw['data_point'] ?? null, [0 => 'Giới hạn sức mạnh', 1 => 'Sức mạnh', 2 => 'Tiềm năng', 3 => 'Thể lực hiện tại', 4 => 'Thể lực tối đa', 5 => 'HP gốc', 6 => 'MP gốc', 7 => 'Dame gốc', 8 => 'Giáp gốc', 9 => 'Chí mạng gốc', 10 => 'Năng động', 11 => 'HP hiện tại', 12 => 'MP hiện tại']),
                    'data_task' => $this->parseIndexedData($raw['data_task'] ?? null, [0 => 'Task ID (nhiệm vụ chính)', 1 => 'Index nhiệm vụ con', 2 => 'Số lượng đã làm', 3 => 'Mốc thời gian nhiệm vụ']),
                    'dataBadges' => $this->parsePlayerBadges($raw['dataBadges'] ?? null),
                ],
                'fields' => $this->loadPlayerFieldDefinitions($game, $displayFields),
            ],
        ];
    }

    public function playerSection(int $id, string $section): array
    {
        if (!Account::find($id)) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản không tồn tại'];
        }

        $displayFields = $this->playerDisplayFields();
        if (!in_array($section, $displayFields, true)) {
            return ['ok' => false, 'status' => 404, 'message' => 'Field không hợp lệ'];
        }

        if (!Schema::connection('game')->hasColumn('player', $section)) {
            return [
                'ok' => true,
                'data' => [
                    'field' => [
                        'name' => $section,
                        'label' => $this->playerFieldLabel($section),
                        'data_type' => 'missing',
                        'is_long' => true,
                    ],
                    'raw' => null,
                    'size' => 0,
                    'parsed' => $section === 'dataBadges'
                        ? ['type' => 'badge_list', 'count' => 0, 'items' => []]
                        : null,
                    'warning' => "Bảng player chưa có cột {$section}",
                ],
            ];
        }

        $payload = Cache::remember("admin:player-section:{$id}:{$section}", now()->addMinutes(5), function () use ($id, $section, $displayFields) {
            $game = DB::connection('game');
            $player = $game->table('player')->select(['id', 'account_id', $section])->where('account_id', $id)->first();
            if (!$player) {
                return null;
            }

            $definitions = $this->loadPlayerFieldDefinitions($game, $displayFields);
            $field = collect($definitions)->firstWhere('name', $section) ?? [
                'name' => $section,
                'label' => $this->playerFieldLabel($section),
                'data_type' => 'unknown',
                'is_long' => true,
            ];
            $rawValue = $player->{$section} ?? null;

            return [
                'field' => $field,
                'raw' => $rawValue,
                'size' => mb_strlen($this->normalizePlayerValue($rawValue)),
                'parsed' => $this->parsePlayerSection($section, $rawValue),
            ];
        });

        return ['ok' => true, 'data' => $payload];
    }

    public function updateBadges(Request $request, int $id): array
    {
        if (!Schema::connection('game')->hasColumn('player', 'dataBadges')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Bảng player chưa có cột dataBadges'];
        }

        $rows = $request->input('badges', []);
        if (!is_array($rows)) {
            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu badge không hợp lệ'];
        }

        $player = DB::connection('game')->table('player')->where('account_id', $id)->first(['id', 'account_id', 'name', 'dataBadges']);
        if (!$player) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản chưa có nhân vật'];
        }

        $badges = collect($rows)->map(fn($row) => [
            'idBadGes' => max(0, (int) (($row['idBadGes'] ?? $row['id'] ?? 0))),
            'timeofUseBadges' => max(0, (int) (($row['timeofUseBadges'] ?? $row['expires_at'] ?? 0))),
            'isUse' => !empty($row['isUse'] ?? $row['is_use'] ?? false),
        ])->filter(fn($row) => $row['idBadGes'] > 0)->values()->all();

        $before = (array) $player;
        DB::connection('game')->table('player')->where('id', $player->id)->update(['dataBadges' => json_encode($badges, JSON_UNESCAPED_UNICODE)]);
        $after = DB::connection('game')->table('player')->where('id', $player->id)->first(['id', 'account_id', 'name', 'dataBadges']);
        Cache::forget("admin:player-section:{$id}:dataBadges");
        $this->logAdminAction('player.badges.update', 'player', (int) $player->id, "Cập nhật danh hiệu nhân vật {$player->name}", $this->sanitizeLogState($before), $this->sanitizeLogState((array) $after), ['account_id' => $id, 'badge_count' => count($badges)]);

        return ['ok' => true, 'message' => 'Đã cập nhật danh hiệu', 'data' => ['raw' => $after->dataBadges ?? '[]', 'parsed' => $this->parsePlayerBadges($after->dataBadges ?? '[]')]];
    }

    public function create(Request $request): array
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'cash' => 'nullable|integer|min:0',
            'danap' => 'nullable|integer|min:0',
            'coin' => 'nullable|integer|min:0',
            'diem_da_nhan' => 'nullable|integer|min:0',
            'diem_danh' => 'nullable|integer|min:0',
        ]);

        if (Account::where('username', $request->input('username'))->exists()) {
            return ['ok' => false, 'status' => 400, 'message' => 'Tên tài khoản đã tồn tại'];
        }

        $account = Account::create([
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'cash' => (int) $request->input('cash', 0),
            'danap' => (int) $request->input('danap', 0),
            'coin' => (int) $request->input('coin', 0),
            'diem_da_nhan' => (int) $request->input('diem_da_nhan', 0),
            'DiemDanh' => (int) $request->input('diem_danh', 0),
            'ban' => (int) $request->input('ban', 0),
            'is_admin' => (int) $request->input('is_admin', 0),
            'active' => (int) $request->input('active', 1),
        ]);

        $this->logAdminAction('create', 'account', $account->id, "Tạo tài khoản {$account->username}", null, $this->sanitizeLogState($account->fresh()->toArray()));

        return ['ok' => true, 'message' => 'Tạo tài khoản thành công', 'id' => $account->id];
    }

    public function update(Request $request, int $id): array
    {
        $account = Account::find($id);
        if (!$account) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản không tồn tại'];
        }

        $data = $request->only(['username', 'password', 'cash', 'danap', 'coin', 'diem_da_nhan', 'diem_danh', 'ban', 'is_admin', 'active']);
        if (isset($data['username']) && Account::where('username', $data['username'])->where('id', '!=', $id)->exists()) {
            return ['ok' => false, 'status' => 400, 'message' => 'Tên tài khoản đã tồn tại'];
        }
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }
        foreach (['cash', 'danap', 'coin', 'diem_da_nhan', 'ban', 'is_admin', 'active'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }
        if (isset($data['diem_danh'])) {
            $data['DiemDanh'] = (int) $data['diem_danh'];
            unset($data['diem_danh']);
        }
        if (!$data) {
            return ['ok' => false, 'status' => 400, 'message' => 'Không có dữ liệu để cập nhật'];
        }

        $before = $this->sanitizeLogState($account->toArray());
        $account->update($data);
        $account->refresh();
        $this->logAdminAction('update', 'account', $account->id, "Cập nhật tài khoản {$account->username}", $before, $this->sanitizeLogState($account->toArray()));

        return ['ok' => true, 'message' => 'Cập nhật tài khoản thành công'];
    }

    public function delete(int $id): array
    {
        $account = Account::find($id);
        if (!$account) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tài khoản không tồn tại'];
        }

        $before = $this->sanitizeLogState($account->toArray());
        $account->delete();
        $this->logAdminAction('delete', 'account', $id, "Xóa tài khoản {$before['username']}", $before, null);

        return ['ok' => true, 'message' => 'Xóa tài khoản thành công'];
    }

    private function decodeArray($value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function playerDisplayFields(): array
    {
        return ['data_point', 'data_inventory', 'data_location', 'data_task', 'items_body', 'items_bag', 'items_box', 'item_mails_box', 'items_daban', 'dataBadges', 'data_item_time', 'pet', 'giftcode', 'event_point_boss'];
    }

    private function loadPlayerFieldDefinitions($game, array $displayFields): array
    {
        $fields = [];
        try {
            $columns = $game->table('information_schema.COLUMNS')
                ->select('COLUMN_NAME', 'DATA_TYPE')
                ->where('TABLE_SCHEMA', $game->getDatabaseName())
                ->where('TABLE_NAME', 'player')
                ->orderBy('ORDINAL_POSITION')
                ->get();
            foreach ($columns as $col) {
                $name = (string) $col->COLUMN_NAME;
                if (!in_array($name, $displayFields, true)) {
                    continue;
                }
                $fields[] = ['name' => $name, 'label' => $this->playerFieldLabel($name), 'data_type' => strtolower((string) $col->DATA_TYPE), 'is_long' => true];
            }
        } catch (\Throwable) {
            foreach ($displayFields as $name) {
                $fields[] = ['name' => $name, 'label' => $this->playerFieldLabel($name), 'data_type' => 'unknown', 'is_long' => true];
            }
        }

        $displayOrder = array_flip($displayFields);
        usort($fields, fn($a, $b) => ($displayOrder[$a['name']] ?? PHP_INT_MAX) <=> ($displayOrder[$b['name']] ?? PHP_INT_MAX));

        return $fields;
    }

    private function normalizePlayerValue($value): string
    {
        if ($value === null) {
            return '(trống)';
        }
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '(trống)';
    }

    private function parsePlayerSection(string $section, $value): ?array
    {
        return match ($section) {
            'data_inventory' => $this->parseIndexedData($value, [0 => 'Vàng', 1 => 'Ngọc xanh', 2 => 'Hồng ngọc (ruby)', 3 => 'Coupon', 4 => 'Điểm sự kiện túi']),
            'data_location' => $this->parseIndexedData($value, [0 => 'Map ID', 1 => 'Tọa độ X', 2 => 'Tọa độ Y']),
            'data_point' => $this->parseIndexedData($value, [0 => 'Giới hạn sức mạnh', 1 => 'Sức mạnh', 2 => 'Tiềm năng', 3 => 'Thể lực hiện tại', 4 => 'Thể lực tối đa', 5 => 'HP gốc', 6 => 'MP gốc', 7 => 'Dame gốc', 8 => 'Giáp gốc', 9 => 'Chí mạng gốc', 10 => 'Năng động', 11 => 'HP hiện tại', 12 => 'MP hiện tại']),
            'data_task' => $this->parseIndexedData($value, [0 => 'Task ID (nhiệm vụ chính)', 1 => 'Index nhiệm vụ con', 2 => 'Số lượng đã làm', 3 => 'Mốc thời gian nhiệm vụ']),
            'items_body', 'items_bag', 'items_box', 'item_mails_box', 'items_daban' => $this->parsePlayerItemList($value),
            'dataBadges' => $this->parsePlayerBadges($value),
            default => $this->summarizeDecodedArray($value),
        };
    }

    private function parsePlayerBadges($value): ?array
    {
        $decoded = $this->decodeArray($value);
        if (!$decoded) {
            return ['type' => 'badge_list', 'count' => 0, 'items' => []];
        }

        $ids = collect($decoded)
            ->filter(fn($row) => is_array($row))
            ->map(fn($row) => (int) ($row['idBadGes'] ?? $row['idBadges'] ?? $row['id'] ?? 0))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $templates = $this->badgeTemplatesById($ids);
        $now = (int) floor(microtime(true) * 1000);

        $items = [];
        foreach ($decoded as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['idBadGes'] ?? $row['idBadges'] ?? $row['id'] ?? 0);
            $expires = (int) ($row['timeofUseBadges'] ?? 0);
            $template = $templates[$id] ?? [];
            $items[] = [
                'index' => is_int($index) ? $index : (string) $index,
                'label' => $template['name'] ?? ('Badge #' . $id),
                'value' => [
                    'id' => $id,
                    'name' => $template['name'] ?? '',
                    'icon_id' => $template['icon_id'] ?? null,
                    'id_effect' => $template['id_effect'] ?? null,
                    'id_item' => $template['id_item'] ?? null,
                    'options' => $template['options'] ?? [],
                    'expires_at' => $expires,
                    'days_left' => $expires > 0 ? (int) floor(($expires - $now) / 86400000) : null,
                    'is_use' => !empty($row['isUse']),
                ],
            ];
        }

        return ['type' => 'badge_list', 'count' => count($items), 'items' => $items];
    }

    private function badgeTemplatesById(array $ids): array
    {
        if (!$ids || !Schema::connection('game')->hasTable('data_badges')) {
            return [];
        }

        $select = [
            'b.id',
            DB::raw('b.NAME as name'),
            DB::raw('b.idEffect as id_effect'),
            DB::raw('b.idItem as id_item'),
            DB::raw('b.Options as options'),
        ];

        $query = DB::connection('game')->table('data_badges as b');
        if (Schema::connection('game')->hasTable('item_template')) {
            $select[] = 'i.icon_id';
            $query->leftJoin('item_template as i', 'b.idItem', '=', 'i.id');
        } else {
            $select[] = DB::raw('NULL as icon_id');
        }

        return $query->whereIn('b.id', $ids)
            ->select($select)
            ->get()
            ->mapWithKeys(fn($row) => [(int) $row->id => $this->normalizeBadgeTemplate($row)])
            ->all();
    }

    private function normalizeBadgeTemplate($row): array
    {
        $rawOptions = (string) ($row->options ?? '[]');
        $decoded = json_decode($this->fixJson($rawOptions), true);
        $options = collect(is_array($decoded) ? $decoded : [])
            ->map(function ($option) {
                $option = is_array($option) ? $option : [];

                return [
                    'id' => (int) ($option['id'] ?? 0),
                    'param' => (int) ($option['param'] ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => (int) ($row->id ?? 0),
            'name' => (string) ($row->name ?? ''),
            'icon_id' => isset($row->icon_id) ? (int) $row->icon_id : null,
            'id_effect' => (int) ($row->id_effect ?? 0),
            'id_item' => (int) ($row->id_item ?? -1),
            'options' => $options,
        ];
    }

    private function parsePlayerItemList($value): ?array
    {
        $decoded = $this->decodeArray($value);
        if (!$decoded) {
            return null;
        }

        $items = [];
        foreach (array_slice($decoded, 0, 40, true) as $index => $slot) {
            $items[] = [
                'index' => is_int($index) ? $index : (string) $index,
                'label' => is_array($slot) ? ('Item #' . ($slot['temp_id'] ?? $slot['template_id'] ?? $slot[0] ?? -1)) : 'Ô',
                'value' => is_array($slot) ? [
                    'quantity' => $slot['quantity'] ?? $slot['qty'] ?? $slot[1] ?? 1,
                    'options' => is_array($slot['options'] ?? $slot[2] ?? []) ? count($slot['options'] ?? $slot[2] ?? []) : 0,
                ] : $slot,
            ];
        }

        return ['type' => 'item_list', 'count' => count($decoded), 'items' => $items];
    }

    private function summarizeDecodedArray($value): ?array
    {
        $decoded = $this->decodeArray($value);
        if (!$decoded) {
            return null;
        }

        return [
            'type' => array_is_list($decoded) ? 'list' : 'map',
            'count' => count($decoded),
            'items' => collect(array_slice($decoded, 0, 5, true))->map(fn($item, $index) => ['index' => is_int($index) ? $index : (string) $index, 'label' => 'Mục', 'value' => $item])->values()->all(),
        ];
    }

    private function buildTaskSummary($taskValue): array
    {
        $taskData = $this->decodeArray($taskValue);
        $taskId = isset($taskData[0]) ? (int) $taskData[0] : null;
        $taskName = null;
        if ($taskId !== null) {
            try {
                $taskName = DB::connection('game')->table('task_main_template')->where('id', $taskId)->value('NAME');
            } catch (\Throwable) {
                $taskName = null;
            }
        }

        return ['id' => $taskId, 'name' => $taskName, 'index' => isset($taskData[1]) ? (int) $taskData[1] : null, 'count' => isset($taskData[2]) ? (int) $taskData[2] : null, 'last_time' => isset($taskData[3]) ? (int) $taskData[3] : null];
    }

    private function playerFieldLabel(string $name): string
    {
        return [
            'data_point' => 'Dữ liệu chỉ số',
            'data_inventory' => 'Dữ liệu túi đồ',
            'data_location' => 'Dữ liệu vị trí',
            'data_task' => 'Dữ liệu nhiệm vụ chính',
            'items_body' => 'Vật phẩm đang mặc',
            'items_bag' => 'Vật phẩm trong túi',
            'items_box' => 'Vật phẩm trong rương',
            'item_mails_box' => 'Vật phẩm từ thư',
            'items_daban' => 'Vật phẩm đã bán',
            'dataBadges' => 'Danh hiệu đang có',
            'data_item_time' => 'Dữ liệu thời gian hiệu ứng',
            'pet' => 'Dữ liệu đệ tử',
            'giftcode' => 'Giftcode đã nhận',
            'event_point_boss' => 'Điểm sự kiện boss',
        ][$name] ?? $name;
    }

    private function parseIndexedData($value, array $labels): ?array
    {
        $arr = $this->decodeArray($value);
        if (!$arr) {
            return null;
        }

        return [
            'size' => count($arr),
            'items' => collect($labels)->map(fn($label, $index) => ['index' => $index, 'label' => $label, 'value' => array_key_exists($index, $arr) ? $arr[$index] : null])->values()->all(),
        ];
    }
}
