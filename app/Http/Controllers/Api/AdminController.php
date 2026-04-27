<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Game\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    // GET /api/admin/admin-logs
    public function adminLogsList(Request $request): JsonResponse
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

    // GET /api/dashboard/overview
    public function dashboardOverview(): JsonResponse
    {
        $game = DB::connection('game');
        $today = now();
        $todayDate = $today->toDateString();
        $monthKey = $today->format('Y-m');

        $milestoneTables = [
            'moc_nap' => 'Mốc nạp',
            'moc_nap_top' => 'Mốc nạp top',
            'moc_nhiem_vu_top' => 'Mốc nhiệm vụ top',
            'moc_suc_manh_top' => 'Mốc sức mạnh top',
        ];

        $milestoneBreakdown = [];
        $milestoneTotal = 0;
        foreach ($milestoneTables as $table => $label) {
            $count = (int) $game->table($table)->count();
            $milestoneTotal += $count;
            $milestoneBreakdown[] = [
                'table' => $table,
                'label' => $label,
                'count' => $count,
            ];
        }

        $sourceExpr = "COALESCE(NULLIF(source, ''), NULLIF(currency, ''), 'Khác')";

        $stats = [
            'accounts' => (int) $game->table('account')->count(),
            'players' => (int) $game->table('player')->count(),
            'topups' => (int) $game->table('topup_transactions')->count(),
            'items' => (int) $game->table('item_template')->count(),
            'giftcodes' => (int) $game->table('giftcode')->count(),
            'giftcodes_active' => (int) $game->table('giftcode')->where('active', 0)->count(),
            'shops' => (int) $game->table('shop')->count(),
            'shop_tabs' => (int) $game->table('tab_shop')->count(),
            'milestones' => $milestoneTotal,
            'today_revenue' => (int) $game->table('topup_transactions')
                ->whereRaw('DATE(created_at) = ?', [$todayDate])
                ->sum('amount'),
            'today_topups' => (int) $game->table('topup_transactions')
                ->whereRaw('DATE(created_at) = ?', [$todayDate])
                ->count(),
            'month_revenue' => (int) $game->table('topup_transactions')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$monthKey])
                ->sum('amount'),
            'month_topups' => (int) $game->table('topup_transactions')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$monthKey])
                ->count(),
        ];

        $sevenDayRows = $game->table('topup_transactions')
            ->selectRaw('DATE(created_at) as label, SUM(amount) as total, COUNT(*) as count')
            ->whereRaw('created_at >= CURDATE() - INTERVAL 6 DAY')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('label');

        $sevenDayRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $row = $sevenDayRows->get($date);
            $sevenDayRevenue[] = [
                'date' => $date,
                'label' => $today->copy()->subDays($i)->format('d/m'),
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        }

        $thirtyDayRows = $game->table('topup_transactions')
            ->selectRaw('DATE(created_at) as label, SUM(amount) as total, COUNT(*) as count')
            ->whereRaw('created_at >= CURDATE() - INTERVAL 29 DAY')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('label');

        $thirtyDayRevenue = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $row = $thirtyDayRows->get($date);
            $thirtyDayRevenue[] = [
                'date' => $date,
                'label' => $today->copy()->subDays($i)->format('d/m'),
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        }

        $sourceRevenue = $game->table('topup_transactions')
            ->selectRaw($sourceExpr . ' as source_label, SUM(amount) as total, COUNT(*) as count')
            ->whereRaw('created_at >= CURDATE() - INTERVAL 29 DAY')
            ->groupBy(DB::raw($sourceExpr))
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                return [
                    'source' => $row->source_label ?: 'Khác',
                    'total' => (int) ($row->total ?? 0),
                    'count' => (int) ($row->count ?? 0),
                ];
            })
            ->values();

        $recentTransactions = $game->table('topup_transactions')
            ->select('trans_id', 'username', 'amount', 'currency', 'source', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'trans_id' => $row->trans_id,
                    'username' => $row->username,
                    'amount' => (int) ($row->amount ?? 0),
                    'currency' => $row->currency,
                    'source' => $row->source,
                    'created_at' => $row->created_at,
                ];
            })
            ->values();

        $topUsers = $game->table('topup_transactions')
            ->select('username', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('username')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                return [
                    'username' => $row->username,
                    'total' => (int) ($row->total ?? 0),
                    'count' => (int) ($row->count ?? 0),
                ];
            })
            ->values();

        $recentAccounts = $game->table('account as a')
            ->leftJoin('player as p', 'p.account_id', '=', 'a.id')
            ->select([
                'a.id',
                'a.username',
                'a.create_time',
                'a.active',
                'a.ban',
                'a.cash',
                'a.danap',
                'p.name as player_name',
            ])
            ->orderByDesc('a.create_time')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'username' => $row->username,
                    'player_name' => $row->player_name,
                    'create_time' => $row->create_time,
                    'active' => (int) ($row->active ?? 0),
                    'ban' => (int) ($row->ban ?? 0),
                    'cash' => (int) ($row->cash ?? 0),
                    'danap' => (int) ($row->danap ?? 0),
                ];
            })
            ->values();

        $systemSummary = [
            ['label' => 'Tài khoản', 'count' => $stats['accounts'], 'hint' => 'Bảng account'],
            ['label' => 'Nhân vật', 'count' => $stats['players'], 'hint' => 'Bảng player'],
            ['label' => 'Giao dịch nạp', 'count' => $stats['topups'], 'hint' => 'Bảng topup_transactions'],
            ['label' => 'Giftcode', 'count' => $stats['giftcodes'], 'hint' => 'Đang bật: ' . number_format($stats['giftcodes_active'], 0, ',', '.')],
            ['label' => 'Vật phẩm mẫu', 'count' => $stats['items'], 'hint' => 'Bảng item_template'],
            ['label' => 'Shop / Tab', 'count' => $stats['shops'] . ' / ' . $stats['shop_tabs'], 'hint' => 'shop / tab_shop'],
            ['label' => 'Mốc thưởng', 'count' => $stats['milestones'], 'hint' => '4 bảng mốc chính'],
        ];

        return response()->json([
            'ok' => true,
            'stats' => $stats,
            'charts' => [
                'revenue_7d' => $sevenDayRevenue,
                'revenue_30d' => $thirtyDayRevenue,
                'source_revenue_30d' => $sourceRevenue,
            ],
            'tables' => [
                'recent_transactions' => $recentTransactions,
                'top_users' => $topUsers,
                'recent_accounts' => $recentAccounts,
                'system_summary' => $systemSummary,
                'milestone_breakdown' => $milestoneBreakdown,
            ],
        ]);
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
            ->with('player')
            ->find($id);

        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $diemDanh = $account->DiemDanh ?? $account->diem_danh ?? 0;
        $player = $account->player;

        return response()->json([
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
                'diem_danh' => (int) $diemDanh,
                'luotquay' => (int) ($account->luotquay ?? 0),
                'vang' => (int) ($account->vang ?? 0),
                'event_point' => (int) ($account->event_point ?? 0),
                'last_diem_danh' => $account->lastDiemDanh,
                'player' => $player
                    ? [
                        'id' => (int) $player->id,
                        'name' => $player->name,
                        'gender' => (int) ($player->gender ?? 0),
                        'head' => (int) ($player->head ?? 0),
                        'power' => (int) ($player->power ?? 0),
                        'task' => $this->buildTaskSummary($player->data_task ?? null),
                    ]
                    : null,
            ],
        ]);
    }

    // GET /api/admin/accounts/{id}/activity
    public function accountsActivity(int $id): JsonResponse
    {
        $account = Account::query()
            ->find($id);

        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $diemDanh = $account->DiemDanh ?? $account->diem_danh ?? 0;

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

        $cardLogs = collect();
        try {
            $cardLogs = $game->table('trans_log')
                ->select('id', 'seri', 'pin', 'type', 'amount', 'trans_id', 'status', 'created_at', 'updated_at')
                ->where('username', $account->username)
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    $pin = (string) ($row->pin ?? '');
                    $seri = (string) ($row->seri ?? '');
                    return [
                        'id' => (int) $row->id,
                        'seri' => $seri !== '' ? substr($seri, 0, 4) . '...' . substr($seri, -4) : '',
                        'pin' => $pin !== '' ? substr($pin, 0, 4) . '...' . substr($pin, -4) : '',
                        'type' => (string) ($row->type ?? ''),
                        'amount' => (int) ($row->amount ?? 0),
                        'trans_id' => (string) ($row->trans_id ?? ''),
                        'status' => (int) ($row->status ?? 0),
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                })
                ->values();
        } catch (\Throwable $e) {
            $cardLogs = collect();
        }

        $adminLogs = AdminActionLog::query()
            ->where('target_type', 'account')
            ->where('target_id', (string) $id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
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
                    'diem_danh' => (int) $diemDanh,
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
        $playerColumns = [
            'id',
            'account_id',
            'name',
            'gender',
            'head',
            'data_point',
            'data_inventory',
            'data_location',
            'data_task',
        ];

        if (Schema::connection('game')->hasColumn('player', 'power')) {
            $playerColumns[] = 'power';
        }

        $player = $game->table('player')
            ->select($playerColumns)
            ->where('account_id', $id)
            ->first();

        if (!$player) {
            return response()->json([
                'ok' => true,
                'data' => null,
                'message' => 'Tài khoản chưa có nhân vật',
            ]);
        }

        $raw = (array) $player;
        $displayFields = $this->playerDisplayFields();
        $point = $this->decodeArray($raw['data_point'] ?? null);
        $fields = $this->loadPlayerFieldDefinitions($game, $displayFields);

        return response()->json([
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
            ],
        ]);
    }

    public function accountsPlayerSection(int $id, string $section): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $displayFields = $this->playerDisplayFields();
        if (!in_array($section, $displayFields, true)) {
            return response()->json(['ok' => false, 'message' => 'Field không hợp lệ'], 404);
        }

        $cacheKey = "admin:player-section:{$id}:{$section}";
        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($id, $section, $displayFields) {
            $game = DB::connection('game');
            $player = $game->table('player')
                ->select(['id', 'account_id', $section])
                ->where('account_id', $id)
                ->first();

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
            $rawText = $this->normalizePlayerValue($rawValue);

            return [
                'field' => $field,
                'raw' => $rawValue,
                'size' => mb_strlen($rawText),
                'parsed' => $this->parsePlayerSection($section, $rawValue),
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $payload,
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

    private function playerDisplayFields(): array
    {
        return [
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
    }

    private function loadPlayerFieldDefinitions($game, array $displayFields): array
    {
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
                $name = (string) $col->COLUMN_NAME;
                if (!in_array($name, $displayFields, true)) {
                    continue;
                }

                $fields[] = [
                    'name' => $name,
                    'label' => $this->playerFieldLabel($name),
                    'data_type' => strtolower((string) $col->DATA_TYPE),
                    'is_long' => true,
                ];
            }
        } catch (\Throwable $e) {
            foreach ($displayFields as $name) {
                $fields[] = [
                    'name' => $name,
                    'label' => $this->playerFieldLabel($name),
                    'data_type' => 'unknown',
                    'is_long' => true,
                ];
            }
        }

        $displayOrder = array_flip($displayFields);
        usort($fields, function ($a, $b) use ($displayOrder) {
            $aOrder = $displayOrder[$a['name']] ?? PHP_INT_MAX;
            $bOrder = $displayOrder[$b['name']] ?? PHP_INT_MAX;
            return $aOrder <=> $bOrder;
        });

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

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '(trống)';
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function parsePlayerSection(string $section, $value): ?array
    {
        return match ($section) {
            'data_inventory' => $this->parseIndexedData($value, [
                0 => 'Vàng',
                1 => 'Ngọc xanh',
                2 => 'Hồng ngọc (ruby)',
                3 => 'Coupon',
                4 => 'Điểm sự kiện túi',
            ]),
            'data_location' => $this->parseIndexedData($value, [
                0 => 'Map ID',
                1 => 'Tọa độ X',
                2 => 'Tọa độ Y',
            ]),
            'data_point' => $this->parseIndexedData($value, [
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
            'data_task' => $this->parseIndexedData($value, [
                0 => 'Task ID (nhiệm vụ chính)',
                1 => 'Index nhiệm vụ con',
                2 => 'Số lượng đã làm',
                3 => 'Mốc thời gian nhiệm vụ',
            ]),
            default => $this->summarizeDecodedArray($value),
        };
    }

    private function summarizeDecodedArray($value): ?array
    {
        $decoded = $this->decodeArray($value);
        if (empty($decoded)) {
            return null;
        }

        $preview = [];
        foreach (array_slice($decoded, 0, 5, true) as $index => $item) {
            $preview[] = [
                'index' => is_int($index) ? $index : (string) $index,
                'label' => 'Mục',
                'value' => $item,
            ];
        }

        return [
            'type' => array_is_list($decoded) ? 'list' : 'map',
            'count' => count($decoded),
            'items' => $preview,
        ];
    }

    private function buildTaskSummary($taskValue): array
    {
        $taskData = $this->decodeArray($taskValue);
        $taskId = isset($taskData[0]) ? (int) $taskData[0] : null;
        $taskName = null;

        if ($taskId !== null) {
            try {
                $taskName = DB::connection('game')->table('task_main_template')
                    ->where('id', $taskId)
                    ->value('NAME');
            } catch (\Throwable $e) {
                $taskName = null;
            }
        }

        return [
            'id' => $taskId,
            'name' => $taskName,
            'index' => isset($taskData[1]) ? (int) $taskData[1] : null,
            'count' => isset($taskData[2]) ? (int) $taskData[2] : null,
            'last_time' => isset($taskData[3]) ? (int) $taskData[3] : null,
        ];
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

        $this->logAdminAction(
            'create',
            'account',
            $account->id,
            "Tạo tài khoản {$account->username}",
            null,
            $this->sanitizeLogState($account->fresh()->toArray()),
        );

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

        $before = $this->sanitizeLogState($account->toArray());
        $account->update($data);
        $account->refresh();
        $this->logAdminAction(
            'update',
            'account',
            $account->id,
            "Cập nhật tài khoản {$account->username}",
            $before,
            $this->sanitizeLogState($account->toArray()),
        );

        return response()->json(['ok' => true, 'message' => 'Cập nhật tài khoản thành công']);
    }

    // DELETE /api/admin/accounts/{id}
    public function accountsDelete(int $id): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        $before = $this->sanitizeLogState($account->toArray());
        $account->delete();
        $this->logAdminAction(
            'delete',
            'account',
            $id,
            "Xoá tài khoản {$before['username']}",
            $before,
            null,
        );

        return response()->json(['ok' => true, 'message' => 'Xoá tài khoản thành công']);
    }

    // ========== GIFTCODE MANAGEMENT ==========

    // GET /api/admin/giftcodes
    public function giftcodesList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $status = trim((string) $request->query('status', ''));
        $mtv = $request->query('mtv');
        $page = (int) $request->query('page', 1);
        $limit = 20;

        $query = DB::connection('game')->table('giftcode')
            ->select('id', 'code', 'count_left', 'detail', 'expired', 'datecreate', 'mtv', 'active');

        if ($search) {
            $query->where('code', 'LIKE', "%{$search}%");
        }

        if ($mtv !== null && $mtv !== '') {
            $query->where('mtv', (int) $mtv);
        }

        if ($status === 'active') {
            $query->where('active', 0);
        } elseif ($status === 'inactive') {
            $query->where('active', 1);
        } elseif ($status === 'expired') {
            $query->whereNotNull('expired')->where('expired', '<', now());
        } elseif ($status === 'available') {
            $query->where('active', 0)
                ->where('count_left', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expired')->orWhere('expired', '>=', now());
                });
        } elseif ($status === 'depleted') {
            $query->where('count_left', '<=', 0);
        }

        $total = $query->count();
        $giftcodes = $query->orderByDesc('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();
        $itemIcons = $this->itemIconMapForDetails($giftcodes->pluck('detail')->all());

        return response()->json([
            'ok' => true,
            'data' => $giftcodes,
            'item_icons' => $itemIcons,
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

    // GET /api/admin/giftcodes/{id}/activity
    public function giftcodesActivity(int $id): JsonResponse
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return response()->json(['ok' => false, 'message' => 'Giftcode không tồn tại'], 404);
        }

        $logs = AdminActionLog::query()
            ->where('target_type', 'giftcode')
            ->where('target_id', (string) $id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => [
                'giftcode' => $giftcode,
                'admin_logs' => $logs,
            ],
        ]);
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

        $created = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        $this->logAdminAction(
            'create',
            'giftcode',
            $id,
            "Tạo giftcode {$code}",
            null,
            $this->sanitizeLogState((array) $created),
        );

        return response()->json([
            'ok' => true,
            'message' => 'Giftcode created successfully',
            'id' => $id,
        ]);
    }

    // PUT /api/admin/giftcodes/{id}
    public function giftcodesUpdate(Request $request, int $id): JsonResponse
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return response()->json(['ok' => false, 'message' => 'Giftcode không tồn tại'], 404);
        }

        $data = $request->only(['code', 'count_left', 'mtv', 'active', 'detail', 'expired']);

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'No fields to update'], 400);
        }

        if (isset($data['code'])) {
            $exists = DB::connection('game')->table('giftcode')
                ->where('code', $data['code'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json(['ok' => false, 'message' => 'Code đã tồn tại'], 400);
            }
        }
        if (isset($data['count_left'])) $data['count_left'] = (int) $data['count_left'];
        if (isset($data['mtv'])) $data['mtv'] = (int) $data['mtv'];
        if (isset($data['active'])) $data['active'] = (int) $data['active'];

        $updated = DB::connection('game')->table('giftcode')
            ->where('id', $id)
            ->update($data);

        if ($updated !== false) {
            $after = DB::connection('game')->table('giftcode')->where('id', $id)->first();
            if (json_encode($this->sanitizeLogState((array) $giftcode)) !== json_encode($this->sanitizeLogState((array) $after))) {
                $this->logAdminAction(
                    'update',
                    'giftcode',
                    $id,
                    'Cập nhật giftcode ' . ($after->code ?? $giftcode->code ?? $id),
                    $this->sanitizeLogState((array) $giftcode),
                    $this->sanitizeLogState((array) $after),
                );
            }
            return response()->json(['ok' => true, 'message' => 'Giftcode updated successfully']);
        }

        return response()->json(['ok' => false, 'message' => 'Update failed'], 500);
    }

    // POST /api/admin/giftcodes/{id}/clone
    public function giftcodesClone(int $id): JsonResponse
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return response()->json(['ok' => false, 'message' => 'Giftcode không tồn tại'], 404);
        }

        $baseCode = strtoupper((string) $giftcode->code) . '-COPY';
        $newCode = $baseCode;
        $suffix = 1;
        while (DB::connection('game')->table('giftcode')->where('code', $newCode)->exists()) {
            $suffix++;
            $newCode = $baseCode . '-' . $suffix;
        }

        $newId = DB::connection('game')->table('giftcode')->insertGetId([
            'code' => $newCode,
            'count_left' => (int) ($giftcode->count_left ?? 0),
            'detail' => (string) ($giftcode->detail ?? '[]'),
            'expired' => $giftcode->expired,
            'type' => (int) ($giftcode->type ?? 0),
            'mtv' => (int) ($giftcode->mtv ?? 0),
            'active' => 0,
        ]);

        $created = DB::connection('game')->table('giftcode')->where('id', $newId)->first();
        $this->logAdminAction(
            'clone',
            'giftcode',
            $newId,
            "Clone giftcode {$giftcode->code} thành {$newCode}",
            $this->sanitizeLogState((array) $giftcode),
            $this->sanitizeLogState((array) $created),
        );

        return response()->json([
            'ok' => true,
            'message' => 'Đã clone giftcode',
            'id' => $newId,
        ]);
    }

    // DELETE /api/admin/giftcodes/{id}
    public function giftcodesDelete(int $id): JsonResponse
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        DB::connection('game')->table('giftcode')->where('id', $id)->delete();
        if ($giftcode) {
            $this->logAdminAction(
                'delete',
                'giftcode',
                $id,
                'Xoá giftcode ' . $giftcode->code,
                $this->sanitizeLogState((array) $giftcode),
                null,
            );
        }

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
        $rawRows = $query
            ->orderBy('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();
        $itemIcons = $this->itemIconMapForDetails($rawRows->pluck('detail')->all());
        $rows = $rawRows
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
            'item_icons' => $itemIcons,
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
        $lite = $request->boolean('lite');
        $type = null;
        if ($typeInput !== null) {
            $typeStr = trim((string) $typeInput);
            if ($typeStr !== '' && !in_array(strtolower($typeStr), ['undefined', 'null', 'nan'], true)) {
                $type = is_numeric($typeStr) ? (int) $typeStr : $typeStr;
            }
        }
        $perPage = max(1, min((int) $request->query('per_page', 50), 200));
        $page = max((int) $request->query('page', 1), 1);

        $usingWebIndex = $this->webItemIndexReady();
        $query = $usingWebIndex
            ? DB::table('game_item_indexes')->selectRaw($lite
                ? 'id, name, type, icon_id'
                : 'id, name, type, icon_id, part, head, body, leg, description, is_up_to_up')
            : DB::connection('game')->table('item_template')->selectRaw($lite
                ? 'id, NAME as name, TYPE as type, icon_id'
                : 'id, NAME as name, TYPE as type, icon_id, part, head, body, leg, description, is_up_to_up');

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                } else {
                    if ($usingWebIndex) {
                        $q->where(function ($nested) use ($search) {
                            $nested->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('normalized_name', 'LIKE', '%' . mb_strtolower($search) . '%');
                        });
                    } else {
                        $q->where('name', 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $partMap = [];
        if (!$lite) {
            $partIds = [];
            foreach ($items as $item) {
                foreach (['part', 'head', 'body', 'leg'] as $field) {
                    $value = isset($item->{$field}) ? (int) $item->{$field} : -1;
                    if ($value >= 0) {
                        $partIds[$value] = true;
                    }
                }
            }

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
        }

        $items = $items->map(function ($item) use ($partMap, $lite) {
            $item->id = (int) $item->id;
            $item->type = isset($item->type) ? (int) $item->type : null;
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

        $typeOptions = $this->webItemTypeIndexReady()
            ? Cache::remember('admin:item_type_options:web:v1', now()->addMinutes(15), function () {
                return DB::table('game_item_type_indexes')
                    ->orderBy('id')
                    ->get(['id', 'name', 'item_count'])
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'name' => (string) $row->name,
                            'item_count' => (int) ($row->item_count ?? 0),
                        ];
                    })
                    ->values()
                    ->all();
            })
            : Cache::remember('admin:item_type_options:v2', now()->addMinutes(15), function () {
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

        $types = array_map(fn($opt) => (int) $opt['id'], $typeOptions);

        return response()->json([
            'ok' => true,
            'data' => $items,
            'part_map' => $lite ? [] : $partMap,
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
        if (empty($ids)) {
            return response()->json([]);
        }
        $ids = array_values(array_unique(array_slice($ids, 0, 1000)));

        sort($ids);
        $items = DB::connection('game')->table('item_template')
            ->selectRaw('id, NAME as name, TYPE as type, icon_id')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    (int) $item->id => [
                        'id' => (int) $item->id,
                        'name' => (string) ($item->name ?? ''),
                        'type' => isset($item->type) ? (int) $item->type : null,
                        'icon_id' => isset($item->icon_id) ? (int) $item->icon_id : null,
                    ],
                ];
            });

        return response()->json($items);
    }

    // GET /api/admin/items/{id}/options
    public function itemsOptions(int $id): JsonResponse
    {
        $options = $this->webOptionIndexReady()
            ? DB::table('game_item_option_indexes')
            ->select('id', 'name')
            ->orderBy('id')
            ->get()
            : DB::connection('game')->table('item_option_template')
            ->selectRaw('id, NAME as name')
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

    private function itemIconMapForDetails(iterable $details): array
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

    private function sanitizeLogState(?array $state): ?array
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

    private function logAdminAction(
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
        } catch (\Throwable $e) {
            // Logging must never break admin actions.
        }
    }

    private function webItemIndexReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $ready = Schema::hasTable('game_item_indexes')
                && DB::table('game_item_indexes')->exists();
        } catch (\Throwable $e) {
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
            $ready = Schema::hasTable('game_item_type_indexes')
                && DB::table('game_item_type_indexes')->exists();
        } catch (\Throwable $e) {
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
            $ready = Schema::hasTable('game_item_option_indexes')
                && DB::table('game_item_option_indexes')->exists();
        } catch (\Throwable $e) {
            $ready = false;
        }

        return $ready;
    }
}
