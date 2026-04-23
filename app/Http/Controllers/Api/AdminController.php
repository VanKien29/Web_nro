<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // GET /api/admin/stats
    public function stats(): JsonResponse
    {
        $game = DB::connection('game');

        $accounts = $game->table('account')->count();
        $topups = $game->table('topup_transactions')->count();

        return response()->json([
            'ok' => true,
            'accounts' => $accounts,
            'topups' => $topups,
        ]);
    }

    // GET /api/admin/history
    public function history(Request $request): JsonResponse
    {
        $username = $request->query('username');
        $from = $request->query('from');
        $to = $request->query('to');

        $query = DB::connection('game')->table('topup_transactions')
            ->select('trans_id', 'username', 'amount', 'currency', 'source', 'created_at');

        if ($username) {
            $query->where('username', $username);
        }

        if ($from && $to) {
            $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }

        $data = $query->orderByDesc('created_at')->limit(50)->get();

        return response()->json(['ok' => true, 'data' => $data]);
    }

    // GET /api/admin/revenue
    public function revenue(Request $request): JsonResponse
    {
        $type = $request->query('type', 'week');
        $month = $request->query('month');
        $game = DB::connection('game');

        if ($type === 'month') {
            $query = $game->table('topup_transactions')
                ->select(DB::raw('DATE(created_at) as label'), DB::raw('SUM(amount) as total'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('DATE(created_at)'));

            if ($month) {
                $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
            } else {
                $query->whereRaw('YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())');
            }

            $data = $query->get();
        } else {
            $data = $game->table('topup_transactions')
                ->select(DB::raw('DATE(created_at) as label'), DB::raw('SUM(amount) as total'))
                ->whereRaw('created_at >= CURDATE() - INTERVAL 6 DAY')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('DATE(created_at)'))
                ->get();
        }

        return response()->json(['ok' => true, 'data' => $data]);
    }

    // GET /api/admin/topUsers
    public function topUsers(): JsonResponse
    {
        $data = DB::connection('game')->table('topup_transactions')
            ->select('username', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('username')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json(['ok' => true, 'data' => $data]);
    }

    // GET /api/admin/monthlyRevenue
    public function monthlyRevenue(Request $request): JsonResponse
    {
        $month = $request->query('month');
        $game = DB::connection('game');

        if ($month) {
            $total = $game->table('topup_transactions')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
                ->sum('amount');
        } else {
            $total = $game->table('topup_transactions')
                ->whereRaw('YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())')
                ->sum('amount');
        }

        return response()->json(['ok' => true, 'total' => (int) $total]);
    }

    // GET /api/admin/accounts
    public function accountsList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);
        $limit = 20;

        $query = Account::query()
            ->select([
                'id',
                'username',
                'ban',
                'is_admin',
                'active',
                'cash',
                'danap',
                'coin',
                'diem_da_nhan',
            ])
            ->selectRaw('`DiemDanh` as diem_danh')
            ->with(['player:id,account_id,name,gender,data_point']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhereHas('player', function ($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $total = $query->count();
        $accounts = $query->orderBy('id')->offset(($page - 1) * $limit)->limit($limit)->get();
        $rows = $accounts->map(function ($acc) {
            return [
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
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }

    // GET /api/admin/accounts/{id}
    public function accountsGet(int $id): JsonResponse
    {
        $account = Account::query()
            ->select([
                'id',
                'username',
                'ban',
                'is_admin',
                'active',
                'cash',
                'danap',
                'coin',
                'diem_da_nhan',
            ])
            ->selectRaw('`DiemDanh` as diem_danh')
            ->with(['player:id,account_id,name,gender,head,data_point,data_task'])
            ->find($id);

        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => (int) $account->id,
                'username' => $account->username,
                'ban' => (int) ($account->ban ?? 0),
                'is_admin' => (int) ($account->is_admin ?? 0),
                'active' => (int) ($account->active ?? 0),
                'cash' => (int) ($account->cash ?? 0),
                'danap' => (int) ($account->danap ?? 0),
                'coin' => (int) ($account->coin ?? 0),
                'diem_da_nhan' => (int) ($account->diem_da_nhan ?? 0),
                'diem_danh' => (int) ($account->diem_danh ?? 0),
                'player' => $account->player
                    ? [
                        'id' => (int) $account->player->id,
                        'name' => $account->player->name,
                        'gender' => (int) ($account->player->gender ?? 0),
                        'head' => (int) ($account->player->head ?? 0),
                        'power' => (int) ($account->player->power ?? 0),
                        'task_data' => $account->player->task_data,
                    ]
                    : null,
            ],
        ]);
    }

    // GET /api/admin/accounts/{id}/player-full
    public function accountsPlayerFull(int $id): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $game = DB::connection('game');
        $player = $game->table('player')->where('account_id', $id)->first();

        if (!$player) {
            return response()->json([
                'ok' => true,
                'data' => null,
                'message' => 'Tài khoản chưa có nhân vật',
            ]);
        }

        $raw = (array) $player;
        $displayFields = [
            'data_point',
            'data_inventory',
            'data_location',
            'data_task',
            'items_body',
            'items_bag',
            'items_box',
            'item_mails_box',
            'items_daban',
            'data_item_time',
            'pet',
            'giftcode',
            'event_point_boss',
        ];
        $point = $this->decodeArray($raw['data_point'] ?? null);
        $taskData = $this->decodeArray($raw['data_task'] ?? null);
        $taskId = isset($taskData[0]) ? (int) $taskData[0] : null;
        $taskName = null;
        if ($taskId !== null) {
            $taskName = $game->table('task_main_template')
                ->where('id', $taskId)
                ->value('NAME');
        }

        $fields = [];
        try {
            $dbName = $game->getDatabaseName();
            $columns = $game->table('information_schema.COLUMNS')
                ->select('COLUMN_NAME', 'DATA_TYPE')
                ->where('TABLE_SCHEMA', $dbName)
                ->where('TABLE_NAME', 'player')
                ->orderBy('ORDINAL_POSITION')
                ->get();

            foreach ($columns as $col) {
                $name = $col->COLUMN_NAME;
                if (!in_array($name, $displayFields, true)) {
                    continue;
                }
                $value = $raw[$name] ?? null;
                $valueString = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                $fields[] = [
                    'name' => $name,
                    'label' => $this->playerFieldLabel($name),
                    'data_type' => strtolower((string) $col->DATA_TYPE),
                    'is_long' => $this->isLongPlayerField((string) $col->DATA_TYPE, $valueString),
                ];
            }
        } catch (\Throwable $e) {
            foreach (array_keys($raw) as $name) {
                if (!in_array($name, $displayFields, true)) {
                    continue;
                }
                $value = $raw[$name] ?? null;
                $valueString = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                $fields[] = [
                    'name' => $name,
                    'label' => $this->playerFieldLabel($name),
                    'data_type' => 'unknown',
                    'is_long' => $this->isLongPlayerField('text', $valueString),
                ];
            }
        }
        $displayOrder = array_flip($displayFields);
        usort($fields, function ($a, $b) use ($displayOrder) {
            $aOrder = $displayOrder[$a['name']] ?? PHP_INT_MAX;
            $bOrder = $displayOrder[$b['name']] ?? PHP_INT_MAX;
            return $aOrder <=> $bOrder;
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'summary' => [
                    'id' => (int) ($raw['id'] ?? 0),
                    'account_id' => (int) ($raw['account_id'] ?? 0),
                    'name' => $raw['name'] ?? '',
                    'gender' => isset($raw['gender']) ? (int) $raw['gender'] : null,
                    'head' => isset($raw['head']) ? (int) $raw['head'] : null,
                    'power' => isset($point[1]) ? (int) $point[1] : 0,
                    'task' => [
                        'id' => $taskId,
                        'name' => $taskName,
                        'index' => isset($taskData[1]) ? (int) $taskData[1] : null,
                        'count' => isset($taskData[2]) ? (int) $taskData[2] : null,
                        'last_time' => isset($taskData[3]) ? (int) $taskData[3] : null,
                    ],
                ],
                'parsed' => [
                    'data_inventory' => $this->parseIndexedData($raw['data_inventory'] ?? null, [
                        0 => 'Vàng',
                        1 => 'Ngọc xanh',
                        2 => 'Hồng ngọc (ruby)',
                        3 => 'Coupon',
                        4 => 'Điểm sự kiện túi',
                    ]),
                    'data_location' => $this->parseIndexedData($raw['data_location'] ?? null, [
                        0 => 'Map ID',
                        1 => 'Tọa độ X',
                        2 => 'Tọa độ Y',
                    ]),
                    'data_point' => $this->parseIndexedData($raw['data_point'] ?? null, [
                        0 => 'Giới hạn sức mạnh',
                        1 => 'Sức mạnh',
                        2 => 'Tiềm năng',
                        3 => 'Thể lực hiện tại',
                        4 => 'Thể lực tối đa',
                        5 => 'HP gốc',
                        6 => 'MP gốc',
                        7 => 'Dame gốc',
                        8 => 'Giáp gốc',
                        9 => 'Chí mạng gốc',
                        10 => 'Năng động',
                        11 => 'HP hiện tại',
                        12 => 'MP hiện tại',
                    ]),
                    'data_task' => $this->parseIndexedData($raw['data_task'] ?? null, [
                        0 => 'Task ID (nhiệm vụ chính)',
                        1 => 'Index nhiệm vụ con',
                        2 => 'Số lượng đã làm',
                        3 => 'Mốc thời gian nhiệm vụ',
                    ]),
                ],
                'fields' => $fields,
                'raw' => array_reduce($displayFields, function ($carry, $field) use ($raw) {
                    $carry[$field] = $raw[$field] ?? null;
                    return $carry;
                }, []),
            ],
        ]);
    }

    private function decodeArray($value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isLongPlayerField(string $dataType, ?string $value): bool
    {
        $type = strtolower($dataType);
        if (in_array($type, ['text', 'mediumtext', 'longtext', 'json'], true)) {
            return true;
        }
        if ($value === null) {
            return false;
        }
        return mb_strlen($value) > 180;
    }

    private function playerFieldLabel(string $name): string
    {
        $labels = [
            'data_point' => 'Dữ liệu chỉ số',
            'data_inventory' => 'Dữ liệu túi đồ',
            'data_location' => 'Dữ liệu vị trí',
            'data_task' => 'Dữ liệu nhiệm vụ chính',
            'items_body' => 'Vật phẩm đang mặc',
            'items_bag' => 'Vật phẩm trong túi',
            'items_box' => 'Vật phẩm trong rương',
            'item_mails_box' => 'Vật phẩm từ thư',
            'items_daban' => 'Vật phẩm đã bán',
            'data_item_time' => 'Dữ liệu thời gian hiệu ứng',
            'pet' => 'Dữ liệu đệ tử',
            'giftcode' => 'Giftcode đã nhận',
            'event_point_boss' => 'Điểm sự kiện boss',
        ];

        return $labels[$name] ?? $name;
    }

    private function parseIndexedData($value, array $labels): ?array
    {
        $arr = $this->decodeArray($value);
        if (empty($arr)) {
            return null;
        }

        $items = [];
        foreach ($labels as $index => $label) {
            $items[] = [
                'index' => $index,
                'label' => $label,
                'value' => array_key_exists($index, $arr) ? $arr[$index] : null,
            ];
        }

        return [
            'size' => count($arr),
            'items' => $items,
        ];
    }

    // POST /api/admin/accounts
    public function accountsCreate(Request $request): JsonResponse
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
            return response()->json(['ok' => false, 'message' => 'Tên tài khoản đã tồn tại'], 400);
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

        return response()->json([
            'ok' => true,
            'message' => 'Tạo tài khoản thành công',
            'id' => $account->id,
        ]);
    }

    // PUT /api/admin/accounts/{id}
    public function accountsUpdate(Request $request, int $id): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $data = $request->only([
            'username',
            'password',
            'cash',
            'danap',
            'coin',
            'diem_da_nhan',
            'diem_danh',
            'ban',
            'is_admin',
            'active',
        ]);

        if (isset($data['username'])) {
            $exists = Account::where('username', $data['username'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json(['ok' => false, 'message' => 'Tên tài khoản đã tồn tại'], 400);
            }
        }

        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['cash'])) $data['cash'] = (int) $data['cash'];
        if (isset($data['danap'])) $data['danap'] = (int) $data['danap'];
        if (isset($data['coin'])) $data['coin'] = (int) $data['coin'];
        if (isset($data['diem_da_nhan'])) $data['diem_da_nhan'] = (int) $data['diem_da_nhan'];
        if (isset($data['diem_danh'])) {
            $data['DiemDanh'] = (int) $data['diem_danh'];
            unset($data['diem_danh']);
        }
        if (isset($data['ban'])) $data['ban'] = (int) $data['ban'];
        if (isset($data['is_admin'])) $data['is_admin'] = (int) $data['is_admin'];
        if (isset($data['active'])) $data['active'] = (int) $data['active'];

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'Không có dữ liệu để cập nhật'], 400);
        }

        $account->update($data);

        return response()->json(['ok' => true, 'message' => 'Cập nhật tài khoản thành công']);
    }

    // DELETE /api/admin/accounts/{id}
    public function accountsDelete(int $id): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $account->delete();

        return response()->json(['ok' => true, 'message' => 'Xoá tài khoản thành công']);
    }

    // ========== GIFTCODE MANAGEMENT ==========

    // GET /api/admin/giftcodes
    public function giftcodesList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);
        $limit = 20;

        $query = DB::connection('game')->table('giftcode')
            ->select('id', 'code', 'count_left', 'expired', 'mtv', 'active');

        if ($search) {
            $query->where('code', 'LIKE', "%{$search}%");
        }

        $total = $query->count();
        $giftcodes = $query->orderByDesc('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $giftcodes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }

    // GET /api/admin/giftcodes/{id}
    public function giftcodesGet(int $id): JsonResponse
    {
        $giftcode = DB::connection('game')->table('giftcode')
            ->where('id', $id)
            ->first();

        if (!$giftcode) {
            return response()->json(['ok' => false, 'message' => 'Giftcode not found'], 404);
        }

        return response()->json(['ok' => true, 'data' => $giftcode]);
    }

    // POST /api/admin/giftcodes
    public function giftcodesCreate(Request $request): JsonResponse
    {
        $code = $request->input('code', $request->input('giftcode', ''));
        if (empty($code)) {
            return response()->json(['ok' => false, 'message' => 'Code is required'], 400);
        }

        $exists = DB::connection('game')->table('giftcode')
            ->where('code', $code)
            ->exists();
        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'Code already exists'], 400);
        }

        $id = DB::connection('game')->table('giftcode')->insertGetId([
            'code' => $code,
            'count_left' => (int) $request->input('count_left', 0),
            'mtv' => (int) $request->input('mtv', 0),
            'active' => (int) $request->input('active', 1),
            'detail' => $request->input('detail', '[]'),
            'expired' => $request->input('expired'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Giftcode created successfully',
            'id' => $id,
        ]);
    }

    // PUT /api/admin/giftcodes/{id}
    public function giftcodesUpdate(Request $request, int $id): JsonResponse
    {
        $data = $request->only(['count_left', 'mtv', 'active', 'detail', 'expired']);

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'No fields to update'], 400);
        }

        if (isset($data['count_left'])) $data['count_left'] = (int) $data['count_left'];
        if (isset($data['mtv'])) $data['mtv'] = (int) $data['mtv'];
        if (isset($data['active'])) $data['active'] = (int) $data['active'];

        $updated = DB::connection('game')->table('giftcode')
            ->where('id', $id)
            ->update($data);

        if ($updated) {
            return response()->json(['ok' => true, 'message' => 'Giftcode updated successfully']);
        }

        return response()->json(['ok' => false, 'message' => 'Update failed'], 500);
    }

    // DELETE /api/admin/giftcodes/{id}
    public function giftcodesDelete(int $id): JsonResponse
    {
        DB::connection('game')->table('giftcode')->where('id', $id)->delete();

        return response()->json(['ok' => true, 'message' => 'Giftcode deleted successfully']);
    }

    // ========== MILESTONE REWARD MANAGEMENT ==========

    // GET /api/admin/milestones/{type}
    public function milestonesList(Request $request, string $type): JsonResponse
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return response()->json(['ok' => false, 'message' => 'Loại bảng không hợp lệ'], 404);
        }

        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = 20;

        $query = DB::connection('game')->table($resolved['table'])->select('id', 'info', 'detail');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhere('info', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query
            ->orderBy('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $detailItems = $this->decodeMilestoneDetail($row->detail ?? null);
                return [
                    'id' => (int) $row->id,
                    'info' => (string) ($row->info ?? ''),
                    'detail' => (string) ($row->detail ?? '[]'),
                    'detail_count' => count($detailItems),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'type' => $resolved['type'],
            'label' => $resolved['label'],
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit),
        ]);
    }

    // GET /api/admin/milestones/{type}/{id}
    public function milestonesGet(string $type, int $id): JsonResponse
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return response()->json(['ok' => false, 'message' => 'Loại bảng không hợp lệ'], 404);
        }

        $row = DB::connection('game')->table($resolved['table'])->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy mốc quà'], 404);
        }

        return response()->json([
            'ok' => true,
            'type' => $resolved['type'],
            'label' => $resolved['label'],
            'data' => [
                'id' => (int) $row->id,
                'info' => (string) ($row->info ?? ''),
                'detail' => (string) ($row->detail ?? '[]'),
            ],
        ]);
    }

    // POST /api/admin/milestones/{type}
    public function milestonesCreate(Request $request, string $type): JsonResponse
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return response()->json(['ok' => false, 'message' => 'Loại bảng không hợp lệ'], 404);
        }

        $info = trim((string) $request->input('info', ''));
        if ($info === '') {
            return response()->json(['ok' => false, 'message' => 'Thông tin mốc là bắt buộc'], 400);
        }

        $requestedId = $request->input('id');
        $id = null;
        if ($requestedId !== null && $requestedId !== '') {
            $id = (int) $requestedId;
            if ($id <= 0) {
                return response()->json(['ok' => false, 'message' => 'ID không hợp lệ'], 400);
            }
            $exists = DB::connection('game')->table($resolved['table'])->where('id', $id)->exists();
            if ($exists) {
                return response()->json(['ok' => false, 'message' => 'ID đã tồn tại'], 400);
            }
        } else {
            $id = ((int) DB::connection('game')->table($resolved['table'])->max('id')) + 1;
        }

        DB::connection('game')->table($resolved['table'])->insert([
            'id' => $id,
            'info' => $info,
            'detail' => $this->normalizeMilestoneDetail($request->input('detail', '[]')),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Tạo mốc quà thành công',
            'id' => $id,
        ]);
    }

    // PUT /api/admin/milestones/{type}/{id}
    public function milestonesUpdate(Request $request, string $type, int $id): JsonResponse
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return response()->json(['ok' => false, 'message' => 'Loại bảng không hợp lệ'], 404);
        }

        $exists = DB::connection('game')->table($resolved['table'])->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy mốc quà'], 404);
        }

        $data = [];
        if ($request->has('info')) {
            $data['info'] = trim((string) $request->input('info', ''));
        }
        if ($request->has('detail')) {
            $data['detail'] = $this->normalizeMilestoneDetail($request->input('detail'));
        }

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'Không có dữ liệu để cập nhật'], 400);
        }

        DB::connection('game')->table($resolved['table'])->where('id', $id)->update($data);

        return response()->json(['ok' => true, 'message' => 'Cập nhật mốc quà thành công']);
    }

    // DELETE /api/admin/milestones/{type}/{id}
    public function milestonesDelete(string $type, int $id): JsonResponse
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return response()->json(['ok' => false, 'message' => 'Loại bảng không hợp lệ'], 404);
        }

        DB::connection('game')->table($resolved['table'])->where('id', $id)->delete();
        return response()->json(['ok' => true, 'message' => 'Đã xoá mốc quà']);
    }

    // ========== ITEMS MANAGEMENT ==========

    // GET /api/admin/items — paginated
    public function itemsList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $typeInput = $request->query('type');
        $type = null;
        if ($typeInput !== null) {
            $typeStr = trim((string) $typeInput);
            if ($typeStr !== '' && !in_array(strtolower($typeStr), ['undefined', 'null', 'nan'], true)) {
                $type = is_numeric($typeStr) ? (int) $typeStr : $typeStr;
            }
        }
        $perPage = max(1, min((int) $request->query('per_page', 50), 200));
        $page = max((int) $request->query('page', 1), 1);

        $query = DB::connection('game')->table('item_template')
            ->selectRaw('id, NAME as name, TYPE as type, icon_id, part, head, body, leg, description, is_up_to_up');

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                } else {
                    $q->where('name', 'LIKE', "%{$search}%");
                }
            });
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $partIds = [];
        foreach ($items as $item) {
            foreach (['part', 'head', 'body', 'leg'] as $field) {
                $value = isset($item->{$field}) ? (int) $item->{$field} : -1;
                if ($value >= 0) {
                    $partIds[$value] = true;
                }
            }
        }

        $partMap = [];
        if (!empty($partIds)) {
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
        }

        $items = $items->map(function ($item) use ($partMap) {
            $item->id = (int) $item->id;
            $item->type = isset($item->type) ? (int) $item->type : null;
            $item->icon_id = isset($item->icon_id) ? (int) $item->icon_id : 0;
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

        $typeRows = DB::connection('game')->table('item_template')
            ->selectRaw('type as id, COUNT(*) as item_count')
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        $typeIds = $typeRows->pluck('id')->map(fn ($id) => (int) $id)->values();

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

        $typeOptions = [];
        foreach ($typeIds as $typeId) {
            $name = $nameById[$typeId] ?? ($nameByIndex[$typeId] ?? ('Type ' . $typeId));
            $typeOptions[] = [
                'id' => (int) $typeId,
                'name' => (string) $name,
                'item_count' => (int) ($countByType[$typeId] ?? 0),
            ];
        }

        $types = array_map(fn ($opt) => $opt['id'], $typeOptions);

        return response()->json([
            'ok' => true,
            'data' => $items,
            'part_map' => $partMap,
            'types' => $types,
            'type_options' => $typeOptions,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
    }

    // GET /api/admin/items/batch?ids=1,2,3
    public function itemsBatch(Request $request): JsonResponse
    {
        $idsParam = $request->query('ids', '');
        if (!$idsParam) {
            return response()->json([]);
        }

        $ids = array_map('intval', array_filter(explode(',', $idsParam), 'is_numeric'));
        if (empty($ids) || count($ids) > 200) {
            return response()->json([]);
        }

        $items = DB::connection('game')->table('item_template')
            ->select('id', 'name as name', 'icon_id')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return response()->json($items);
    }

    // GET /api/admin/items/{id}/options
    public function itemsOptions(int $id): JsonResponse
    {
        $options = DB::connection('game')->table('item_option_template')
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        return response()->json(['ok' => true, 'options' => $options]);
    }

    // ========== SHOP MANAGEMENT ==========

    // GET /api/admin/shops
    public function shopsList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');

        $query = DB::connection('game')->table('shop')
            ->leftJoin('npc_template', 'shop.npc_id', '=', 'npc_template.id')
            ->select('shop.id', 'shop.npc_id', 'shop.tag_name', 'shop.type_shop', 'npc_template.NAME as npc_name', 'npc_template.avatar as npc_avatar');

        if ($search) {
            $query->where('shop.tag_name', 'LIKE', "%{$search}%");
        }

        $shops = $query->orderBy('shop.npc_id')->orderBy('shop.id')->get();

        // Attach tabs for each shop
        $shopIds = $shops->pluck('id')->toArray();
        $tabs = DB::connection('game')->table('tab_shop')
            ->whereIn('shop_id', $shopIds)
            ->select('id', 'shop_id', 'tab_name', 'tab_index', 'items')
            ->orderBy('tab_index')
            ->get()
            ->groupBy('shop_id');

        foreach ($shops as $shop) {
            $shopTabs = $tabs->get($shop->id, collect());
            $shop->tabs = $shopTabs->map(function ($tab) {
                $items = [];
                try {
                    $items = json_decode($this->fixJson($tab->items ?? '[]'), true) ?: [];
                } catch (\Throwable $e) {
                    $items = [];
                }
                $tab->item_count = count($items);
                unset($tab->items);
                return $tab;
            })->values();
        }

        return response()->json(['ok' => true, 'data' => $shops]);
    }

    // GET /api/admin/shops/tab/{tabId}
    public function shopTabGet(int $tabId): JsonResponse
    {
        $tab = DB::connection('game')->table('tab_shop')
            ->where('id', $tabId)
            ->first();

        if (!$tab) {
            return response()->json(['ok' => false, 'message' => 'Tab not found'], 404);
        }

        $shop = DB::connection('game')->table('shop')
            ->where('id', $tab->shop_id)
            ->first();

        return response()->json(['ok' => true, 'data' => $tab, 'shop' => $shop]);
    }

    // PUT /api/admin/shops/tab/{tabId}
    public function shopTabUpdate(Request $request, int $tabId): JsonResponse
    {
        $tab = DB::connection('game')->table('tab_shop')
            ->where('id', $tabId)
            ->first();

        if (!$tab) {
            return response()->json(['ok' => false, 'message' => 'Tab not found'], 404);
        }

        $data = $request->only(['tab_name', 'tab_index', 'items']);

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'No fields to update'], 400);
        }

        if (isset($data['tab_index'])) {
            $data['tab_index'] = (int) $data['tab_index'];
        }

        $updated = DB::connection('game')->table('tab_shop')
            ->where('id', $tabId)
            ->update($data);

        if ($updated !== false) {
            return response()->json(['ok' => true, 'message' => 'Cập nhật tab thành công']);
        }

        return response()->json(['ok' => false, 'message' => 'Update failed'], 500);
    }

    /**
     * Fix malformed JSON strings (trailing/leading/double commas).
     */
    private function fixJson(?string $str): string
    {
        if (!$str) return '[]';
        $s = trim($str);
        // Remove trailing commas before ] or }
        $s = preg_replace('/,\s*([\]\}])/', '$1', $s);
        // Remove leading commas after [ or {
        $s = preg_replace('/([\[\{])\s*,/', '$1', $s);
        // Remove double commas
        $s = preg_replace('/,\s*,/', ',', $s);
        return $s;
    }

    private function decodePartData(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $normalized = trim((string) $raw);
        $normalized = str_replace('\\"', '"', $normalized);
        $normalized = $this->fixJson($normalized);

        $decoded = json_decode($normalized, true);
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

    private function resolveMilestoneType(string $type): ?array
    {
        $map = [
            'moc_nap' => ['table' => 'moc_nap', 'label' => 'Mốc nạp'],
            'moc_nap_top' => ['table' => 'moc_nap_top', 'label' => 'Mốc nạp top'],
            'moc_nhiem_vu_top' => ['table' => 'moc_nhiem_vu_top', 'label' => 'Mốc nhiệm vụ top'],
            'moc_suc_manh_top' => ['table' => 'moc_suc_manh_top', 'label' => 'Mốc sức mạnh top'],
        ];

        if (!isset($map[$type])) {
            return null;
        }

        return [
            'type' => $type,
            'table' => $map[$type]['table'],
            'label' => $map[$type]['label'],
        ];
    }

    private function decodeMilestoneDetail($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($this->fixJson($value), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeMilestoneDetail($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (!is_string($value) || trim($value) === '') {
            return '[]';
        }

        $fixed = $this->fixJson($value);
        $decoded = json_decode($fixed, true);
        if (!is_array($decoded)) {
            return '[]';
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
