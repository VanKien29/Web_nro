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
use Illuminate\Support\Facades\File;
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
        if (Schema::connection('game')->hasColumn('player', 'dataBadges')) {
            $playerColumns[] = 'dataBadges';
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
                    'dataBadges' => $this->parsePlayerBadges($raw['dataBadges'] ?? null),
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
            'dataBadges',
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
            'items_body', 'items_bag', 'items_box', 'item_mails_box', 'items_daban' => $this->parsePlayerItemList($value),
            'dataBadges' => $this->parsePlayerBadges($value),
            default => $this->summarizeDecodedArray($value),
        };
    }

    private function parsePlayerBadges($value): ?array
    {
        $decoded = $this->decodeArray($value);
        if (empty($decoded)) {
            return [
                'type' => 'badge_list',
                'count' => 0,
                'items' => [],
            ];
        }

        $badgeIds = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $row['idBadGes'] ?? $row['idBadges'] ?? $row['id'] ?? null;
            if (is_numeric($id)) {
                $badgeIds[(int) $id] = true;
            }
        }

        $templates = $this->badgeTemplatesById(array_keys($badgeIds));
        $items = [];
        foreach ($decoded as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['idBadGes'] ?? $row['idBadges'] ?? $row['id'] ?? 0);
            $expires = (int) ($row['timeofUseBadges'] ?? 0);
            $template = $templates[$id] ?? null;
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
                    'days_left' => $expires > 0 ? (int) floor(($expires - (int) floor(microtime(true) * 1000)) / 86400000) : null,
                    'is_use' => !empty($row['isUse']),
                ],
            ];
        }

        return [
            'type' => 'badge_list',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function badgeTemplatesById(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
        if (empty($ids) || !Schema::connection('game')->hasTable('data_badges')) {
            return [];
        }

        try {
            return DB::connection('game')->table('data_badges as b')
                ->leftJoin('item_template as i', 'b.idItem', '=', 'i.id')
                ->whereIn('b.id', $ids)
                ->get([
                    'b.id',
                    DB::raw('b.NAME as name'),
                    DB::raw('b.idEffect as id_effect'),
                    DB::raw('b.idItem as id_item'),
                    DB::raw('b.Options as options'),
                    'i.icon_id',
                ])
                ->mapWithKeys(fn($row) => [(int) $row->id => $this->normalizeBadgeTemplate($row)])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function normalizeBadgeTemplate($row): array
    {
        $options = [];
        $raw = (string) ($row->options ?? '[]');
        try {
            $decoded = json_decode($this->fixJson($raw), true);
            $options = is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            $options = [];
        }

        return [
            'id' => (int) ($row->id ?? 0),
            'name' => (string) ($row->name ?? ''),
            'id_effect' => (int) ($row->id_effect ?? 0),
            'id_item' => (int) ($row->id_item ?? -1),
            'icon_id' => isset($row->icon_id) ? (int) $row->icon_id : null,
            'image_url' => $this->normalizeBadgeImageUrl((string) ($row->image_url ?? '')),
            'data' => isset($row->data_raw) ? (string) $row->data_raw : '',
            'raw_options' => $raw,
            'options' => collect($options)->map(function ($option) {
                $option = is_array($option) ? $option : [];
                return [
                    'id' => (int) ($option['id'] ?? 0),
                    'param' => (int) ($option['param'] ?? 0),
                ];
            })->values()->all(),
        ];
    }

    private function badgeBaseQuery()
    {
        $select = [
            'b.id',
            DB::raw('b.NAME as name'),
            DB::raw('b.idEffect as id_effect'),
            DB::raw('b.idItem as id_item'),
            DB::raw('b.Options as options'),
            'i.icon_id',
        ];

        $imageColumn = $this->badgeImageColumn();
        if ($imageColumn) {
            $select[] = DB::raw("b.`{$imageColumn}` as image_url");
        } else {
            $select[] = DB::raw("'' as image_url");
        }

        if (Schema::connection('game')->hasColumn('data_badges', 'data')) {
            $select[] = DB::raw('b.`data` as data_raw');
        } else {
            $select[] = DB::raw("'' as data_raw");
        }

        return DB::connection('game')->table('data_badges as b')
            ->leftJoin('item_template as i', 'b.idItem', '=', 'i.id')
            ->select($select);
    }

    private function badgeCapabilities(): array
    {
        return [
            'image_column' => $this->badgeImageColumn(),
            'data_column' => Schema::connection('game')->hasColumn('data_badges', 'data') ? 'data' : null,
            'asset_table' => Schema::hasTable('admin_badge_assets'),
        ];
    }

    private function attachBadgeAssets($badges)
    {
        if (!Schema::hasTable('admin_badge_assets') || $badges->isEmpty()) {
            return $badges;
        }

        $assets = DB::table('admin_badge_assets')
            ->whereIn('badge_id', $badges->pluck('id')->all())
            ->get()
            ->keyBy('badge_id');

        return $badges->map(function ($badge) use ($assets) {
            $asset = $assets->get($badge['id']);
            if (!$asset) {
                return $badge;
            }
            if (!empty($asset->image_url)) {
                $badge['image_url'] = $this->normalizeBadgeImageUrl((string) $asset->image_url);
            }
            if ($asset->data !== null && $asset->data !== '') {
                $badge['data'] = (string) $asset->data;
            }
            return $badge;
        });
    }

    private function badgeImageColumn(): ?string
    {
        foreach (['image_url', 'image', 'image_path', 'avatar', 'icon'] as $column) {
            if (Schema::connection('game')->hasColumn('data_badges', $column)) {
                return $column;
            }
        }
        return null;
    }

    private function normalizeBadgeImageUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }
        return '/' . ltrim($value, '/');
    }

    private function normalizeBadgePayload(Request $request): array
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'id_effect' => 'nullable|integer|min:0',
            'id_item' => 'nullable|integer|min:-1',
            'options' => 'nullable',
            'data' => 'nullable|string|max:200000',
            'image' => 'nullable|file|mimes:png,jpg,jpeg,webp,gif|max:4096',
            'create_item_template' => 'nullable|boolean',
            'item_name' => 'nullable|string|max:255',
            'item_type' => 'nullable|integer',
            'item_gender' => 'nullable|integer|min:0|max:3',
            'item_description' => 'nullable|string|max:255',
            'create_part' => 'nullable|boolean',
            'part_type' => 'nullable|integer|min:0|max:2',
            'part_dx' => 'nullable|integer|min:-32768|max:32767',
            'part_dy' => 'nullable|integer|min:-32768|max:32767',
        ]);

        $optionsInput = $request->input('options', []);
        if (is_string($optionsInput)) {
            $decoded = json_decode($this->fixJson($optionsInput), true);
            $optionsInput = is_array($decoded) ? $decoded : [];
        }
        $options = collect(is_array($optionsInput) ? $optionsInput : [])->map(function ($option) {
            $option = is_array($option) ? $option : [];
            return [
                'id' => (int) ($option['id'] ?? 0),
                'param' => (int) ($option['param'] ?? 0),
            ];
        })->values()->all();

        $payload = [
            'NAME' => (string) $request->input('name', ''),
            'idEffect' => (int) $request->input('id_effect', 0),
            'idItem' => (int) $request->input('id_item', -1),
            'Options' => json_encode($options, JSON_UNESCAPED_UNICODE),
        ];

        if (Schema::connection('game')->hasColumn('data_badges', 'data')) {
            $payload['data'] = (string) $request->input('data', '');
        }

        $imageColumn = $this->badgeImageColumn();
        if ($imageColumn && $request->hasFile('image') && !$request->boolean('create_item_template')) {
            $file = $request->file('image');
            $dir = public_path('uploads/badges');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $name = 'badge_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($file->getClientOriginalExtension());
            $file->move($dir, $name);
            $payload[$imageColumn] = '/uploads/badges/' . $name;
        } elseif ($imageColumn && $request->filled('image_url')) {
            $payload[$imageColumn] = (string) $request->input('image_url');
        }

        return $payload;
    }

    private function createBadgeItemTemplateIfRequested(Request $request): ?array
    {
        if (!$request->boolean('create_item_template')) {
            return null;
        }
        if (!Schema::connection('game')->hasTable('item_template')) {
            return null;
        }

        $game = DB::connection('game');
        $itemId = $this->nextGameId('item_template');
        $iconId = $this->nextBadgeIconId();
        $partId = -1;

        if ($request->hasFile('image')) {
            $this->saveBadgeIconImage($request->file('image'), $iconId);
        }

        if ($request->boolean('create_part') && Schema::connection('game')->hasTable('part')) {
            $partId = $this->nextGameId('part');
            $partType = max(0, min(2, (int) $request->input('part_type', 0)));
            $baseIcon = match ($partType) {
                1 => 16,
                2 => 34,
                default => 20,
            };
            $partData = [
                [
                    $iconId,
                    (int) $request->input('part_dx', 0),
                    (int) $request->input('part_dy', 0),
                ],
                [$baseIcon, 0, 0],
            ];
            $game->table('part')->insert([
                'id' => $partId,
                'TYPE' => $partType,
                'DATA' => json_encode($partData, JSON_UNESCAPED_UNICODE),
            ]);
        }

        $itemName = trim((string) $request->input('item_name', ''));
        if ($itemName === '') {
            $itemName = trim((string) $request->input('name', 'Danh hiệu'));
        }

        $game->table('item_template')->insert([
            'id' => $itemId,
            'TYPE' => (int) $request->input('item_type', 99),
            'gender' => (int) $request->input('item_gender', 3),
            'NAME' => $itemName,
            'description' => (string) $request->input('item_description', 'Danh hiệu'),
            'level' => 0,
            'icon_id' => $iconId,
            'part' => $partId,
            'is_up_to_up' => 0,
            'power_require' => 0,
            'gold' => 0,
            'gem' => 0,
            'head' => -1,
            'body' => -1,
            'leg' => -1,
            'is_up_to_up_over_99' => 0,
            'can_trade' => 1,
            'comment' => (string) $request->input('data', ''),
        ]);

        return [
            'item_id' => $itemId,
            'icon_id' => $iconId,
            'part_id' => $partId,
        ];
    }

    private function nextGameId(string $table): int
    {
        return ((int) (DB::connection('game')->table($table)->max('id') ?? 0)) + 1;
    }

    private function nextBadgeIconId(): int
    {
        $dbMax = (int) (DB::connection('game')->table('item_template')->max('icon_id') ?? 0);
        $fileMax = 0;
        $dir = public_path('assets/frontend/home/v1/images/x4');
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE) ?: [] as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (is_numeric($name)) {
                    $fileMax = max($fileMax, (int) $name);
                }
            }
        }
        return max($dbMax, $fileMax) + 1;
    }

    private function saveBadgeIconImage($file, int $iconId): void
    {
        $dir = public_path('assets/frontend/home/v1/images/x4');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file->move($dir, $iconId . '.png');
    }

    private function saveBadgeAsset(Request $request, int $badgeId): void
    {
        if (!Schema::hasTable('admin_badge_assets')) {
            return;
        }

        $data = ['updated_at' => now()];
        $hasAssetChange = false;

        if ($request->has('data')) {
            $data['data'] = (string) $request->input('data', '');
            $hasAssetChange = true;
        }

        if (!$this->badgeImageColumn() && $request->hasFile('image') && !$request->boolean('create_item_template')) {
            $file = $request->file('image');
            $dir = public_path('uploads/badges');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $name = 'badge_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($file->getClientOriginalExtension());
            $file->move($dir, $name);
            $data['image_url'] = '/uploads/badges/' . $name;
            $hasAssetChange = true;
        } elseif ($request->filled('image_url')) {
            $data['image_url'] = (string) $request->input('image_url');
            $hasAssetChange = true;
        }

        if (!$hasAssetChange) {
            return;
        }

        DB::table('admin_badge_assets')->updateOrInsert(
            ['badge_id' => $badgeId],
            array_merge($data, ['created_at' => now()])
        );
    }

    private function parsePlayerItemList($value): ?array
    {
        $decoded = $this->decodeArray($value);
        if (empty($decoded)) {
            return null;
        }

        $itemIds = [];
        foreach ($decoded as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $itemId = $slot['temp_id'] ?? $slot['template_id'] ?? $slot[0] ?? null;
            if (is_numeric($itemId) && (int) $itemId >= 0) {
                $itemIds[(int) $itemId] = true;
            }
        }

        $templates = [];
        if (!empty($itemIds)) {
            try {
                $templates = DB::connection('game')->table('item_template')
                    ->whereIn('id', array_keys($itemIds))
                    ->get(['id', DB::raw('NAME as name'), DB::raw('TYPE as type'), 'icon_id'])
                    ->keyBy('id')
                    ->all();
            } catch (\Throwable) {
                $templates = [];
            }
        }

        $items = [];
        foreach (array_slice($decoded, 0, 40, true) as $index => $slot) {
            if (!is_array($slot)) {
                $items[] = [
                    'index' => is_int($index) ? $index : (string) $index,
                    'label' => 'Ô',
                    'value' => $slot,
                ];
                continue;
            }

            $itemId = $slot['temp_id'] ?? $slot['template_id'] ?? $slot[0] ?? -1;
            $quantity = $slot['quantity'] ?? $slot['qty'] ?? $slot[1] ?? 1;
            $options = $slot['options'] ?? $slot[2] ?? [];
            $template = is_numeric($itemId) ? ($templates[(int) $itemId] ?? null) : null;
            $optionCount = is_array($options) ? count($options) : 0;

            $items[] = [
                'index' => is_int($index) ? $index : (string) $index,
                'label' => $template
                    ? ('#' . (int) $itemId . ' ' . (string) $template->name)
                    : ('Item #' . $itemId),
                'value' => [
                    'quantity' => is_numeric($quantity) ? (int) $quantity : $quantity,
                    'type' => $template ? (int) $template->type : null,
                    'icon_id' => $template && isset($template->icon_id) ? (int) $template->icon_id : null,
                    'options' => $optionCount,
                ],
            ];
        }

        return [
            'type' => 'item_list',
            'count' => count($decoded),
            'items' => $items,
        ];
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
            'dataBadges' => 'Danh hiệu đang có',
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

    public function badgesList(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        if (!Schema::connection('game')->hasTable('data_badges')) {
            return response()->json(['ok' => true, 'data' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1]);
        }

        $query = $this->badgeBaseQuery();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('b.id', (int) $search);
                }
                $q->orWhere('b.NAME', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('b.id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => $this->normalizeBadgeTemplate($row))
            ->values();
        $rows = $this->attachBadgeAssets($rows);

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'capabilities' => $this->badgeCapabilities(),
        ]);
    }

    public function badgesGet(int $id): JsonResponse
    {
        $row = $this->badgeBaseQuery()->where('b.id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Badge không tồn tại'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->attachBadgeAssets(collect([$this->normalizeBadgeTemplate($row)]))->first(),
            'capabilities' => $this->badgeCapabilities(),
        ]);
    }

    public function badgesCreate(Request $request): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('data_badges')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng data_badges'], 422);
        }

        $payload = $this->normalizeBadgePayload($request);
        $id = $this->nextGameId('data_badges');
        $payload['id'] = $id;
        $createdItem = $this->createBadgeItemTemplateIfRequested($request);
        if ($createdItem) {
            $payload['idItem'] = $createdItem['item_id'];
        }
        DB::connection('game')->table('data_badges')->insert($payload);
        $this->saveBadgeAsset($request, $id);
        $row = $this->badgeBaseQuery()->where('b.id', $id)->first();
        $normalized = $this->attachBadgeAssets(collect([$this->normalizeBadgeTemplate($row)]))->first();

        $this->logAdminAction(
            'badge.create',
            'badge',
            $id,
            "Tạo badge {$payload['NAME']}",
            null,
            $this->sanitizeLogState((array) $row)
        );

        return response()->json([
            'ok' => true,
            'message' => 'Đã tạo badge',
            'data' => $normalized,
        ]);
    }

    public function badgesUpdate(Request $request, int $id): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('data_badges')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng data_badges'], 422);
        }

        $before = $this->badgeBaseQuery()->where('b.id', $id)->first();
        if (!$before) {
            return response()->json(['ok' => false, 'message' => 'Badge không tồn tại'], 404);
        }

        $payload = $this->normalizeBadgePayload($request);
        $createdItem = $this->createBadgeItemTemplateIfRequested($request);
        if ($createdItem) {
            $payload['idItem'] = $createdItem['item_id'];
        }
        DB::connection('game')->table('data_badges')->where('id', $id)->update($payload);
        $this->saveBadgeAsset($request, $id);
        $after = $this->badgeBaseQuery()->where('b.id', $id)->first();
        $normalized = $this->attachBadgeAssets(collect([$this->normalizeBadgeTemplate($after)]))->first();

        $this->logAdminAction(
            'badge.update',
            'badge',
            $id,
            "Cập nhật badge {$payload['NAME']}",
            $this->sanitizeLogState((array) $before),
            $this->sanitizeLogState((array) $after)
        );

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật badge',
            'data' => $normalized,
        ]);
    }

    public function badgesDelete(int $id): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('data_badges')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng data_badges'], 422);
        }

        $before = $this->badgeBaseQuery()->where('b.id', $id)->first();
        if (!$before) {
            return response()->json(['ok' => false, 'message' => 'Badge không tồn tại'], 404);
        }

        DB::connection('game')->table('data_badges')->where('id', $id)->delete();
        if (Schema::hasTable('admin_badge_assets')) {
            DB::table('admin_badge_assets')->where('badge_id', $id)->delete();
        }
        $this->logAdminAction(
            'badge.delete',
            'badge',
            $id,
            "Xóa badge {$before->name}",
            $this->sanitizeLogState((array) $before),
            null
        );

        return response()->json(['ok' => true, 'message' => 'Đã xóa badge']);
    }

    public function titleItemsList(Request $request): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng item_template'], 422);
        }

        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        $query = DB::connection('game')->table('item_template as i')
            ->select([
                'i.id',
                DB::raw('i.TYPE as type'),
                'i.gender',
                DB::raw('i.NAME as name'),
                'i.description',
                'i.icon_id',
                'i.part',
            ])
            ->where(function ($q) {
                $q->whereIn('i.TYPE', [36, 99])
                    ->orWhere('i.description', 'LIKE', '%Danh hiệu%')
                    ->orWhere('i.comment', 'LIKE', '%Admin title item%');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('i.id', (int) $search)
                        ->orWhere('i.icon_id', (int) $search)
                        ->orWhere('i.part', (int) $search);
                }
                $q->orWhere('i.NAME', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('i.id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'type' => (int) $row->type,
                'gender' => (int) $row->gender,
                'name' => (string) $row->name,
                'description' => (string) $row->description,
                'icon_id' => (int) $row->icon_id,
                'part' => (int) $row->part,
                'id_effect' => (int) $row->part >= 0 ? (int) $row->part : null,
                'has_icon_x4' => is_file($this->gameSrcPath('data/icon/x4/' . (int) $row->icon_id . '.png')),
                'icon_url' => is_file($this->gameSrcPath('data/icon/x4/' . (int) $row->icon_id . '.png'))
                    ? '/admin/api/title-items/icon/' . (int) $row->icon_id
                    : null,
                'has_effect_data' => (int) $row->part >= 0 && is_file($this->gameSrcPath('data/effdata/DataEffect_' . (int) $row->part)),
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'paths' => [
                'game_src' => $this->gameSrcPath(),
                'icon' => $this->gameSrcPath('data/icon'),
                'effect' => $this->gameSrcPath('data/effect'),
                'effdata' => $this->gameSrcPath('data/effdata'),
            ],
            'next' => [
                'item_id' => $this->nextGameId('item_template'),
                'icon_id' => $this->nextGameAssetId('icon', 'item_template', 'icon_id'),
                'effect_id' => $this->nextGameEffectId(),
                'part_id' => Schema::connection('game')->hasTable('part') ? $this->nextGameId('part') : null,
            ],
        ]);
    }

    public function titleItemIcon(int $iconId)
    {
        $path = $this->gameSrcPath("data/icon/x4/{$iconId}.png");
        if (!is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    public function titleItemsCreate(Request $request): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng item_template'], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|integer',
            'gender' => 'nullable|integer|min:0|max:3',
            'effect_data_text' => 'nullable|string|max:400000',
            'effect_data_file' => 'nullable',
            'icon_x1' => 'nullable',
            'icon_x2' => 'nullable',
            'icon_x3' => 'nullable',
            'icon_x4' => 'nullable',
            'effect_x1' => 'nullable',
            'effect_x2' => 'nullable',
            'effect_x3' => 'nullable',
            'effect_x4' => 'nullable',
        ]);

        $game = DB::connection('game');
        $assetIds = [];
        $iconIds = [];
        $effectIds = [];
        $saved = [];
        $created = [];

        foreach ($this->requestFiles($request, 'icon_x4') as $file) {
            $id = $this->resolveGameAssetId($this->numericIdFromFilename($file->getClientOriginalName()), 'icon', $iconIds);
            $saved = array_merge($saved, $this->saveGamePngPyramid($file, 'data/icon', "{$id}.png", 96));
            $assetIds[$id] = true;
            $iconIds[$id] = true;
        }

        foreach ($this->requestFiles($request, 'effect_x4') as $file) {
            $id = $this->resolveGameAssetId($this->numericIdFromFilename($file->getClientOriginalName()), 'effect', $effectIds);
            $saved = array_merge($saved, $this->saveGamePngPyramid($file, 'data/effect', "ImgEffect_{$id}.png"));
            $assetIds[$id] = true;
            $effectIds[$id] = true;
        }

        foreach ([1, 2, 3, 4] as $zoom) {
            if ($zoom === 4) {
                continue;
            }

            foreach ($this->requestFiles($request, "icon_x{$zoom}") as $file) {
                $id = $this->resolveGameAssetId($this->numericIdFromFilename($file->getClientOriginalName()), 'icon', $iconIds);
                $saved[] = $this->saveGameUpload($file, "data/icon/x{$zoom}/{$id}.png");
                $assetIds[$id] = true;
                $iconIds[$id] = true;
            }

            foreach ($this->requestFiles($request, "effect_x{$zoom}") as $file) {
                $id = $this->resolveGameAssetId($this->numericIdFromFilename($file->getClientOriginalName()), 'effect', $effectIds);
                $effectName = str_contains(strtolower($file->getClientOriginalName()), 'imageeffect_')
                    ? "ImageEffect_{$id}.png"
                    : "ImgEffect_{$id}.png";
                $saved[] = $this->saveGameUpload($file, "data/effect/x{$zoom}/{$effectName}");
                $assetIds[$id] = true;
                $effectIds[$id] = true;
            }
        }

        $orderedEffectIds = array_values(array_unique(array_map('intval', array_keys($effectIds))));
        sort($orderedEffectIds);
        foreach ($this->requestFiles($request, 'effect_data_file') as $file) {
            $candidate = $this->numericIdFromFilename($file->getClientOriginalName());
            $id = ($candidate !== null && isset($effectIds[$candidate]))
                ? $candidate
                : ($orderedEffectIds[count(array_filter($saved, fn($path) => str_contains($path, 'DataEffect_')))] ?? null);
            if ($id === null) {
                $id = $this->resolveGameAssetId($candidate, 'effect', $effectIds);
                $effectIds[$id] = true;
            }
            $saved[] = $this->saveGameUpload($file, "data/effdata/DataEffect_{$id}");
            $assetIds[$id] = true;
            $effectIds[$id] = true;
        }

        if ($request->filled('effect_data_text')) {
            $id = $orderedEffectIds[0] ?? $this->resolveGameAssetId(null, 'effect', $effectIds);
            $path = $this->gameSrcPath("data/effdata/DataEffect_{$id}");
            File::ensureDirectoryExists(dirname($path), 0775, true);
            File::put($path, (string) $request->input('effect_data_text'));
            $saved[] = $path;
            $assetIds[$id] = true;
            $effectIds[$id] = true;
        }

        if (!$assetIds) {
            $assetIds[$this->nextGameEffectId()] = true;
        }

        $iconIds = array_map('intval', array_keys($iconIds));
        $effectIds = array_map('intval', array_keys($effectIds));
        sort($iconIds);
        sort($effectIds);
        $assetPairs = $this->pairTitleAssetIds($iconIds, $effectIds, array_map('intval', array_keys($assetIds)));

        DB::beginTransaction();
        $game->beginTransaction();
        try {
            $nextItemId = $this->nextGameId('item_template');
            $baseName = trim((string) $request->input('name'));
            foreach ($assetPairs as $index => $pair) {
                $itemId = $nextItemId + $index;
                $iconId = $pair['icon_id'];
                $partId = $pair['part'];
                $nameSuffix = $iconId === $partId ? $iconId : "{$iconId}-{$partId}";
                $name = count($assetPairs) === 1 ? $baseName : "{$baseName} {$nameSuffix}";
                $row = [
                    'id' => $itemId,
                    'TYPE' => (int) $request->input('type', 99),
                    'gender' => (int) $request->input('gender', 3),
                    'NAME' => $name,
                    'description' => (string) $request->input('description', 'Danh hiệu'),
                    'level' => 0,
                    'icon_id' => $iconId,
                    'part' => $partId,
                    'is_up_to_up' => 0,
                    'power_require' => 0,
                    'gold' => 0,
                    'gem' => 0,
                    'head' => -1,
                    'body' => -1,
                    'leg' => -1,
                    'is_up_to_up_over_99' => 0,
                    'can_trade' => 1,
                    'comment' => "Admin title item. icon_id={$iconId};part={$partId}",
                ];
                $game->table('item_template')->insert($row);
                $created[] = [
                    'item_id' => $itemId,
                    'icon_id' => $iconId,
                    'part' => $partId,
                    'name' => $name,
                ];
            }

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            $game->rollBack();
            DB::rollBack();
            foreach ($saved as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return response()->json(['ok' => false, 'message' => 'Không tạo được danh hiệu: ' . $e->getMessage()], 500);
        }

        $this->logAdminAction(
            'title_item.create',
            'item_template',
            $created[0]['item_id'] ?? 0,
            'Tạo danh hiệu item_template ' . trim((string) $request->input('name')),
            null,
            [
                'created' => $created,
                'saved_files' => $saved,
            ],
        );
        return response()->json([
            'ok' => true,
            'message' => 'Đã lưu asset vào src game và tạo ' . count($created) . ' item_template danh hiệu',
            'data' => [
                'created' => $created,
                'saved_files' => $saved,
            ],
        ]);
    }

    public function titleItemsUpdate(Request $request, int $id): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng item_template'], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|integer',
            'gender' => 'nullable|integer|min:0|max:3',
            'effect_data_text' => 'nullable|string|max:400000',
            'effect_data_file' => 'nullable',
            'icon_x4' => 'nullable',
            'effect_x4' => 'nullable',
        ]);

        $game = DB::connection('game');
        $before = $game->table('item_template')->where('id', $id)->first();
        if (!$before) {
            return response()->json(['ok' => false, 'message' => 'Danh hiệu không tồn tại'], 404);
        }

        $iconId = (int) $before->icon_id;
        $partId = (int) $before->part;
        $saved = [];

        try {
            $iconFile = $this->requestFiles($request, 'icon_x4')[0] ?? null;
            if ($iconFile) {
                $iconId = $iconId > 0 ? $iconId : $this->resolveGameAssetId($this->numericIdFromFilename($iconFile->getClientOriginalName()), 'icon', []);
                $saved = array_merge($saved, $this->saveGamePngPyramid($iconFile, 'data/icon', "{$iconId}.png", 96));
            }

            $effectFile = $this->requestFiles($request, 'effect_x4')[0] ?? null;
            if ($effectFile) {
                $partId = $partId > 0 ? $partId : $this->resolveGameAssetId($this->numericIdFromFilename($effectFile->getClientOriginalName()), 'effect', []);
                $saved = array_merge($saved, $this->saveGamePngPyramid($effectFile, 'data/effect', "ImgEffect_{$partId}.png"));
            }

            $effectDataFile = $this->requestFiles($request, 'effect_data_file')[0] ?? null;
            if ($effectDataFile) {
                $partId = $partId > 0 ? $partId : $this->resolveGameAssetId($this->numericIdFromFilename($effectDataFile->getClientOriginalName()), 'effect', []);
                $saved[] = $this->saveGameUpload($effectDataFile, "data/effdata/DataEffect_{$partId}");
            } elseif ($request->filled('effect_data_text')) {
                $partId = $partId > 0 ? $partId : $this->resolveGameAssetId(null, 'effect', []);
                $path = $this->gameSrcPath("data/effdata/DataEffect_{$partId}");
                File::ensureDirectoryExists(dirname($path), 0775, true);
                File::put($path, (string) $request->input('effect_data_text'));
                $saved[] = $path;
            }

            $payload = [
                'TYPE' => (int) $request->input('type', $before->TYPE ?? 99),
                'gender' => (int) $request->input('gender', $before->gender ?? 3),
                'NAME' => trim((string) $request->input('name')),
                'description' => (string) $request->input('description', $before->description ?? 'Danh hiệu'),
                'icon_id' => $iconId,
                'part' => $partId,
                'comment' => "Admin title item. icon_id={$iconId};part={$partId}",
            ];

            $game->table('item_template')->where('id', $id)->update($payload);
            $after = $game->table('item_template')->where('id', $id)->first();
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Không cập nhật được danh hiệu: ' . $e->getMessage()], 500);
        }

        $this->logAdminAction(
            'title_item.update',
            'item_template',
            $id,
            'Cập nhật danh hiệu item_template ' . trim((string) $request->input('name')),
            $this->sanitizeLogState((array) $before),
            $this->sanitizeLogState([
                'row' => (array) $after,
                'saved_files' => $saved,
            ]),
        );
        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật danh hiệu',
            'data' => [
                'item_id' => $id,
                'icon_id' => $iconId,
                'part' => $partId,
                'saved_files' => $saved,
            ],
        ]);
    }

    public function costumesList(Request $request): JsonResponse
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bảng item_template'], 422);
        }

        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        $query = DB::connection('game')->table('item_template as i')
            ->select([
                'i.id',
                DB::raw('i.TYPE as type'),
                'i.gender',
                DB::raw('i.NAME as name'),
                'i.description',
                'i.icon_id',
                'i.part',
                'i.head',
                'i.body',
                'i.leg',
            ])
            ->where('i.TYPE', 5);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('i.id', (int) $search)
                        ->orWhere('i.icon_id', (int) $search)
                        ->orWhere('i.head', (int) $search)
                        ->orWhere('i.body', (int) $search)
                        ->orWhere('i.leg', (int) $search);
                }
                $q->orWhere('i.NAME', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('i.id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'type' => (int) $row->type,
                'gender' => (int) $row->gender,
                'name' => (string) $row->name,
                'description' => (string) $row->description,
                'icon_id' => (int) $row->icon_id,
                'part' => (int) $row->part,
                'head' => (int) $row->head,
                'body' => (int) $row->body,
                'leg' => (int) $row->leg,
                'icon_url' => is_file($this->gameSrcPath('data/icon/x4/' . (int) $row->icon_id . '.png'))
                    ? '/admin/api/title-items/icon/' . (int) $row->icon_id
                    : null,
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'next' => [
                'item_id' => $this->nextGameId('item_template'),
                'part_id' => Schema::connection('game')->hasTable('part') ? $this->nextGameId('part') : null,
                'icon_id' => $this->nextCostumeIconId(),
            ],
        ]);
    }

    public function costumesCreate(Request $request): JsonResponse
    {
        foreach (['item_template', 'part', 'head_avatar', 'array_head_2_frames'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return response()->json(['ok' => false, 'message' => "Chưa có bảng {$table}"], 422);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'item_id' => 'nullable|integer|min:1',
            'gender' => 'nullable|integer|min:0|max:3',
            'head_data' => 'required|string|max:400000',
            'body_data' => 'required|string|max:400000',
            'leg_data' => 'required|string|max:400000',
            'extra_head_data' => 'nullable|string|max:400000',
            'icon_x4' => 'nullable',
            'icon_x4_payload' => 'nullable|string',
            'item_icon_x4' => 'nullable',
            'avatar_x4' => 'nullable',
        ]);

        $requestedItemId = (int) $request->input('item_id', 0);
        $requestedExistingItem = null;
        if ($requestedItemId > 0) {
            $requestedExistingItem = DB::connection('game')->table('item_template')->where('id', $requestedItemId)->first();
            if ($requestedExistingItem && !$this->isReservedItemTemplate($requestedExistingItem)) {
                return response()->json(['ok' => false, 'message' => "item_template ID {$requestedItemId} đã tồn tại"], 422);
            }
        }

        $game = DB::connection('game');
        $saved = [];
        $idMap = [];
        $iconIds = [];
        $spriteUploadedIds = [];
        $temporarySourceIconIds = $this->temporaryCostumeSourceIconIds($request);
        $itemIconId = null;
        $itemIconCandidate = null;
        $avatarId = null;

        try {
            foreach ($this->decodeUploadedImagePayload((string) $request->input('icon_x4_payload', '')) as $payloadFile) {
                $candidate = $this->numericIdFromFilename($payloadFile['name']);
                $id = $this->resolveCostumeIconId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, $iconIds);
                $saved = array_merge($saved, $this->saveGamePngPyramidFromBytes($payloadFile['bytes'], $payloadFile['name'], 'data/icon', "{$id}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $id;
                }
                $iconIds[$id] = true;
                $spriteUploadedIds[] = $id;
            }

            foreach ($this->requestFiles($request, 'icon_x4') as $file) {
                $candidate = $this->numericIdFromFilename($file->getClientOriginalName());
                $id = $this->resolveCostumeIconId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, $iconIds);
                $saved = array_merge($saved, $this->saveGamePngPyramid($file, 'data/icon', "{$id}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $id;
                }
                $iconIds[$id] = true;
                $spriteUploadedIds[] = $id;
            }

            $itemIconFile = $this->requestFiles($request, 'item_icon_x4')[0] ?? null;
            if ($itemIconFile) {
                $itemIconCandidate = $this->numericIdFromFilename($itemIconFile->getClientOriginalName());
                if ($itemIconCandidate !== null && isset($idMap[$itemIconCandidate])) {
                    $itemIconId = (int) $idMap[$itemIconCandidate];
                } else {
                    $itemIconId = $this->resolveCostumeIconId($itemIconCandidate, $iconIds);
                    $saved = array_merge($saved, $this->saveGamePngPyramid($itemIconFile, 'data/icon', "{$itemIconId}.png", 96));
                    if ($itemIconCandidate !== null) {
                        $idMap[$itemIconCandidate] = $itemIconId;
                    }
                    $iconIds[$itemIconId] = true;
                }
            }

            $avatarFile = $this->requestFiles($request, 'avatar_x4')[0] ?? null;
            if ($avatarFile) {
                $candidate = $this->numericIdFromFilename($avatarFile->getClientOriginalName());
                $avatarId = $this->resolveCostumeIconId($candidate, $iconIds);
                $saved = array_merge($saved, $this->saveGamePngPyramid($avatarFile, 'data/icon', "{$avatarId}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $avatarId;
                }
                $iconIds[$avatarId] = true;
            } elseif (!empty($iconIds)) {
                $avatarId = (int) array_key_first($iconIds);
            } else {
                return response()->json(['ok' => false, 'message' => 'Cần upload ít nhất một icon hoặc avatar x4'], 422);
            }
            $itemIconId ??= $avatarId;

            $headRawLayers = $this->parsePartLayers((string) $request->input('head_data'));
            $bodyRawLayers = $this->parsePartLayers((string) $request->input('body_data'));
            $legRawLayers = $this->parsePartLayers((string) $request->input('leg_data'));
            $extraRawHeads = $this->parseExtraPartLayerBlocks((string) $request->input('extra_head_data', ''));
            $idMap = $this->completeSequentialSpriteIdMap($idMap, $spriteUploadedIds, array_merge($headRawLayers, $bodyRawLayers, $legRawLayers, ...$extraRawHeads));

            $headLayers = $this->rewritePartLayers($headRawLayers, $idMap);
            $bodyLayers = $this->rewritePartLayers($bodyRawLayers, $idMap);
            $legLayers = $this->rewritePartLayers($legRawLayers, $idMap);
            $extraHeads = array_map(fn($layers) => $this->rewritePartLayers($layers, $idMap), $extraRawHeads);
        } catch (\Throwable $e) {
            foreach ($saved as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return response()->json(['ok' => false, 'message' => 'Dữ liệu cải trang không hợp lệ: ' . $e->getMessage()], 422);
        }

        DB::beginTransaction();
        $game->beginTransaction();
        try {
            if ($requestedItemId > 0) {
                $this->ensureItemTemplateContinuity($game, $requestedItemId - 1);
            }

            $nextPartId = $this->nextGameId('part');
            $headId = $nextPartId;
            $extraHeadIds = array_map(fn($offset) => $nextPartId + 1 + $offset, array_keys($extraHeads));
            $bodyId = $nextPartId + 1 + count($extraHeads);
            $legId = $bodyId + 1;
            $createdParts = [];

            $partRows = [
                ['id' => $headId, 'TYPE' => 0, 'DATA' => json_encode($headLayers, JSON_UNESCAPED_UNICODE)],
            ];

            foreach ($extraHeads as $offset => $layers) {
                $partRows[] = [
                    'id' => $extraHeadIds[$offset],
                    'TYPE' => 0,
                    'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE),
                ];
            }
            $partRows[] = ['id' => $bodyId, 'TYPE' => 1, 'DATA' => json_encode($bodyLayers, JSON_UNESCAPED_UNICODE)];
            $partRows[] = ['id' => $legId, 'TYPE' => 2, 'DATA' => json_encode($legLayers, JSON_UNESCAPED_UNICODE)];

            foreach ($partRows as $row) {
                $game->table('part')->insert($row);
                $createdParts[] = $row['id'];
                if ((int) $row['TYPE'] === 0) {
                    $game->table('head_avatar')->updateOrInsert(
                        ['head_id' => $row['id']],
                        ['avatar_id' => $avatarId],
                    );
                }
            }
            $this->syncCostumeHeadFrames($game, array_merge([$headId], $extraHeadIds));

            $itemId = $requestedItemId > 0 ? $requestedItemId : $this->nextGameId('item_template');
            $name = trim((string) $request->input('name'));
            $itemRow = [
                'id' => $itemId,
                'TYPE' => 5,
                'gender' => (int) $request->input('gender', 3),
                'NAME' => $name,
                'description' => (string) $request->input('description', 'Cải trang ' . $name),
                'level' => 0,
                'icon_id' => $itemIconId,
                'part' => 0,
                'is_up_to_up' => 0,
                'power_require' => 0,
                'gold' => 0,
                'gem' => 0,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'is_up_to_up_over_99' => 0,
                'can_trade' => 1,
                'comment' => "Admin costume item. head={$headId};body={$bodyId};leg={$legId};extra_heads=" . implode(',', $extraHeadIds) . ";icon={$itemIconId};avatar={$avatarId}",
            ];
            if ($requestedExistingItem && $this->isReservedItemTemplate($requestedExistingItem)) {
                $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
            } else {
                $game->table('item_template')->insert($itemRow);
            }

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            $game->rollBack();
            DB::rollBack();
            foreach ($saved as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return response()->json(['ok' => false, 'message' => 'Không tạo được cải trang: ' . $e->getMessage()], 500);
        }

        $this->logAdminAction(
            'costume.create',
            'item_template',
            $itemId,
            'Tạo cải trang ' . $name,
            null,
            [
                'item' => $itemRow,
                'part_ids' => $createdParts,
                'item_icon_id' => $itemIconId,
                'avatar_id' => $avatarId,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
            ],
        );
        return response()->json([
            'ok' => true,
            'message' => "Đã tạo cải trang {$name} (#{$itemId})",
            'data' => [
                'item_id' => $itemId,
                'item_icon_id' => $itemIconId,
                'avatar_id' => $avatarId,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'extra_heads' => $extraHeadIds,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
            ],
        ]);
    }

    public function costumesGet($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json(['ok' => false, 'message' => 'ID cải trang không hợp lệ'], 422);
        }
        $id = (int) $id;

        foreach (['item_template', 'part', 'head_avatar', 'array_head_2_frames'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return response()->json(['ok' => false, 'message' => "Chưa có bảng {$table}"], 422);
            }
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->first();
        if (!$item || (int) ($item->TYPE ?? -1) !== 5) {
            return response()->json(['ok' => false, 'message' => 'Cải trang không tồn tại'], 404);
        }

        $partData = [];
        foreach (['head', 'body', 'leg'] as $field) {
            $partId = (int) ($item->{$field} ?? -1);
            $part = $partId >= 0 ? $game->table('part')->where('id', $partId)->first(['DATA']) : null;
            $partData[$field . '_data'] = $part ? (string) $part->DATA : '';
        }

        $extraBlocks = [];
        foreach ($this->costumeExtraHeadIdsFromComment((string) ($item->comment ?? '')) as $extraHeadId) {
            $part = $game->table('part')->where('id', $extraHeadId)->first(['DATA']);
            if ($part) {
                $extraBlocks[] = (string) $part->DATA;
            }
        }

        $avatarId = $this->costumeAvatarIdFromComment((string) ($item->comment ?? ''));
        if ($avatarId <= 0 && (int) ($item->head ?? -1) >= 0) {
            $avatarId = (int) ($game->table('head_avatar')->where('head_id', (int) $item->head)->value('avatar_id') ?? 0);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => (int) $item->id,
                'name' => (string) ($item->NAME ?? ''),
                'description' => (string) ($item->description ?? ''),
                'gender' => (int) ($item->gender ?? 3),
                'icon_id' => (int) ($item->icon_id ?? 0),
                'avatar_id' => $avatarId,
                'head' => (int) ($item->head ?? -1),
                'body' => (int) ($item->body ?? -1),
                'leg' => (int) ($item->leg ?? -1),
                'extra_heads' => $this->costumeExtraHeadIdsFromComment((string) ($item->comment ?? '')),
                'icon_url' => is_file($this->gameSrcPath('data/icon/x4/' . (int) ($item->icon_id ?? 0) . '.png'))
                    ? '/admin/api/title-items/icon/' . (int) ($item->icon_id ?? 0)
                    : null,
                ...$partData,
                'extra_head_data' => implode("\n\n", $extraBlocks),
            ],
        ]);
    }

    public function costumesUpdate(Request $request, $id): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json(['ok' => false, 'message' => 'ID cải trang không hợp lệ'], 422);
        }
        $id = (int) $id;

        foreach (['item_template', 'part', 'head_avatar', 'array_head_2_frames'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return response()->json(['ok' => false, 'message' => "Chưa có bảng {$table}"], 422);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|min:0|max:3',
            'head_data' => 'required|string|max:400000',
            'body_data' => 'required|string|max:400000',
            'leg_data' => 'required|string|max:400000',
            'extra_head_data' => 'nullable|string|max:400000',
            'icon_x4' => 'nullable',
            'icon_x4_payload' => 'nullable|string',
            'item_icon_x4' => 'nullable',
            'avatar_x4' => 'nullable',
        ]);

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->first();
        if (!$item || (int) ($item->TYPE ?? -1) !== 5) {
            return response()->json(['ok' => false, 'message' => 'Cải trang không tồn tại'], 404);
        }

        $saved = [];
        $idMap = [];
        $iconIds = [];
        $spriteUploadedIds = [];
        $temporarySourceIconIds = $this->temporaryCostumeSourceIconIds($request);
        $itemIconId = (int) ($item->icon_id ?? 0);
        $avatarId = $this->costumeAvatarIdFromComment((string) ($item->comment ?? ''));
        if ($avatarId <= 0 && (int) ($item->head ?? -1) >= 0) {
            $avatarId = (int) ($game->table('head_avatar')->where('head_id', (int) $item->head)->value('avatar_id') ?? 0);
        }
        $oldIconIds = [$itemIconId, $avatarId];
        $oldPartIds = array_values(array_filter([
            (int) ($item->head ?? -1),
            (int) ($item->body ?? -1),
            (int) ($item->leg ?? -1),
            ...$this->costumeExtraHeadIdsFromComment((string) ($item->comment ?? '')),
        ], fn($partId) => $partId >= 0));
        if ($oldPartIds) {
            foreach ($game->table('part')->whereIn('id', $oldPartIds)->get(['DATA']) as $partRow) {
                foreach ($this->decodePartData($partRow->DATA ?? '') as $layer) {
                    $oldIconIds[] = (int) ($layer['icon_id'] ?? 0);
                }
            }
        }

        try {
            foreach ($this->decodeUploadedImagePayload((string) $request->input('icon_x4_payload', '')) as $payloadFile) {
                $candidate = $this->numericIdFromFilename($payloadFile['name']);
                $newId = $this->resolveCostumeIconId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, $iconIds);
                $saved = array_merge($saved, $this->saveGamePngPyramidFromBytes($payloadFile['bytes'], $payloadFile['name'], 'data/icon', "{$newId}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $newId;
                }
                $iconIds[$newId] = true;
                $spriteUploadedIds[] = $newId;
            }

            foreach ($this->requestFiles($request, 'icon_x4') as $file) {
                $candidate = $this->numericIdFromFilename($file->getClientOriginalName());
                $newId = $this->resolveCostumeIconId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, $iconIds);
                $saved = array_merge($saved, $this->saveGamePngPyramid($file, 'data/icon', "{$newId}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $newId;
                }
                $iconIds[$newId] = true;
                $spriteUploadedIds[] = $newId;
            }

            $itemIconFile = $this->requestFiles($request, 'item_icon_x4')[0] ?? null;
            if ($itemIconFile) {
                $candidate = $this->numericIdFromFilename($itemIconFile->getClientOriginalName());
                if ($candidate !== null && isset($idMap[$candidate])) {
                    $itemIconId = (int) $idMap[$candidate];
                } else {
                    $itemIconId = $this->resolveCostumeIconId($candidate, $iconIds);
                    $saved = array_merge($saved, $this->saveGamePngPyramid($itemIconFile, 'data/icon', "{$itemIconId}.png", 96));
                    if ($candidate !== null) {
                        $idMap[$candidate] = $itemIconId;
                    }
                    $iconIds[$itemIconId] = true;
                }
            }

            $avatarFile = $this->requestFiles($request, 'avatar_x4')[0] ?? null;
            if ($avatarFile) {
                $candidate = $this->numericIdFromFilename($avatarFile->getClientOriginalName());
                $avatarId = $this->resolveCostumeIconId($candidate, $iconIds);
                $saved = array_merge($saved, $this->saveGamePngPyramid($avatarFile, 'data/icon', "{$avatarId}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $avatarId;
                }
                $iconIds[$avatarId] = true;
            }

            if ($avatarId <= 0) {
                $avatarId = $itemIconId;
            }

            $headRawLayers = $this->parsePartLayers((string) $request->input('head_data'));
            $bodyRawLayers = $this->parsePartLayers((string) $request->input('body_data'));
            $legRawLayers = $this->parsePartLayers((string) $request->input('leg_data'));
            $extraRawHeads = $this->parseExtraPartLayerBlocks((string) $request->input('extra_head_data', ''));
            $idMap = $this->completeSequentialSpriteIdMap($idMap, $spriteUploadedIds, array_merge($headRawLayers, $bodyRawLayers, $legRawLayers, ...$extraRawHeads));

            $headLayers = $this->rewritePartLayers($headRawLayers, $idMap);
            $bodyLayers = $this->rewritePartLayers($bodyRawLayers, $idMap);
            $legLayers = $this->rewritePartLayers($legRawLayers, $idMap);
            $extraHeads = array_map(fn($layers) => $this->rewritePartLayers($layers, $idMap), $extraRawHeads);
        } catch (\Throwable $e) {
            foreach ($saved as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return response()->json(['ok' => false, 'message' => 'Dữ liệu cải trang không hợp lệ: ' . $e->getMessage()], 422);
        }

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $headId = (int) $item->head;
            $bodyId = (int) $item->body;
            $legId = (int) $item->leg;
            foreach ([[$headId, 0, $headLayers], [$bodyId, 1, $bodyLayers], [$legId, 2, $legLayers]] as [$partId, $type, $layers]) {
                $game->table('part')->updateOrInsert(
                    ['id' => $partId],
                    ['TYPE' => $type, 'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE)],
                );
            }

            $existingExtraIds = $this->costumeExtraHeadIdsFromComment((string) ($item->comment ?? ''));
            $extraIds = [];
            foreach ($extraHeads as $offset => $layers) {
                $extraId = $existingExtraIds[$offset] ?? $this->nextGameId('part');
                while (
                    in_array($extraId, [$headId, $bodyId, $legId], true)
                    || (!in_array($extraId, $existingExtraIds, true) && $game->table('part')->where('id', $extraId)->exists())
                ) {
                    $extraId++;
                }
                $game->table('part')->updateOrInsert(
                    ['id' => $extraId],
                    ['TYPE' => 0, 'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE)],
                );
                $extraIds[] = $extraId;
            }

            $removedExtraIds = array_values(array_diff($existingExtraIds, $extraIds));
            if ($removedExtraIds) {
                $game->table('head_avatar')->whereIn('head_id', $removedExtraIds)->delete();
                $game->table('part')->whereIn('id', $removedExtraIds)->delete();
                $game->table('array_head_2_frames')->whereIn('id', $removedExtraIds)->delete();
            }

            foreach (array_merge([$headId], $extraIds) as $partId) {
                $game->table('head_avatar')->updateOrInsert(
                    ['head_id' => $partId],
                    ['avatar_id' => $avatarId],
                );
            }
            $this->syncCostumeHeadFrames($game, array_merge([$headId], $extraIds));

            $name = trim((string) $request->input('name'));
            $itemRow = [
                'TYPE' => 5,
                'gender' => (int) $request->input('gender', 3),
                'NAME' => $name,
                'description' => (string) $request->input('description', 'Cải trang ' . $name),
                'icon_id' => $itemIconId,
                'part' => 0,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'comment' => "Admin costume item. head={$headId};body={$bodyId};leg={$legId};extra_heads=" . implode(',', $extraIds) . ";icon={$itemIconId};avatar={$avatarId}",
            ];
            $game->table('item_template')->where('id', $id)->update($itemRow);

            $safeIconIds = $this->filterUnreferencedCostumeIconIds($game, $oldIconIds);
            $deletedIconFiles = $this->deleteGameIconFiles($safeIconIds);

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            $game->rollBack();
            DB::rollBack();
            foreach ($saved as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return response()->json(['ok' => false, 'message' => 'Không sửa được cải trang: ' . $e->getMessage()], 500);
        }

        $this->logAdminAction(
            'costume.update',
            'item_template',
            $id,
            'Sửa cải trang ' . $name,
            $this->sanitizeLogState((array) $item),
            [
                'item' => $itemRow,
                'extra_heads' => $extraIds,
                'item_icon_id' => $itemIconId,
                'avatar_id' => $avatarId,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        );
        return response()->json([
            'ok' => true,
            'message' => "Đã sửa cải trang {$name} (#{$id})",
            'data' => [
                'item_id' => $id,
                'item_icon_id' => $itemIconId,
                'avatar_id' => $avatarId,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'extra_heads' => $extraIds,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ]);
    }

    public function costumesDelete($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json(['ok' => false, 'message' => 'ID cải trang không hợp lệ'], 422);
        }
        $id = (int) $id;

        foreach (['item_template', 'part', 'head_avatar', 'array_head_2_frames'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return response()->json(['ok' => false, 'message' => "Chưa có bảng {$table}"], 422);
            }
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->first();
        if (!$item) {
            return response()->json(['ok' => false, 'message' => 'Cải trang không tồn tại'], 404);
        }

        $partIds = [];
        foreach (['head', 'body', 'leg'] as $field) {
            $value = isset($item->{$field}) ? (int) $item->{$field} : -1;
            if ($value >= 0) {
                $partIds[$value] = true;
            }
        }

        foreach ($this->costumeExtraHeadIdsFromComment((string) ($item->comment ?? '')) as $extraHeadId) {
            $partIds[$extraHeadId] = true;
        }

        $avatarIdFromComment = $this->costumeAvatarIdFromComment((string) ($item->comment ?? ''));
        if ($avatarIdFromComment > 0) {
            $sameAvatarHeadIds = $game->table('head_avatar')
                ->where('avatar_id', $avatarIdFromComment)
                ->pluck('head_id')
                ->all();
            foreach ($sameAvatarHeadIds as $headId) {
                $headId = (int) $headId;
                if ($headId >= 0 && $this->looksLikeCostumeExtraHead($game, $headId, $partIds)) {
                    $partIds[$headId] = true;
                }
            }
        }

        $deletedParts = array_keys($partIds);
        sort($deletedParts);
        $iconIdsToDelete = [(int) ($item->icon_id ?? 0)];
        if ($deletedParts) {
            $partsForDelete = $game->table('part')->whereIn('id', $deletedParts)->get(['DATA']);
            foreach ($partsForDelete as $partRow) {
                foreach ($this->decodePartData($partRow->DATA ?? '') as $layer) {
                    $iconIdsToDelete[] = (int) ($layer['icon_id'] ?? 0);
                }
            }

            $avatarIds = $game->table('head_avatar')
                ->whereIn('head_id', $deletedParts)
                ->pluck('avatar_id')
                ->all();
            foreach ($avatarIds as $avatarId) {
                $iconIdsToDelete[] = (int) $avatarId;
            }
        }
        $iconIdsToDelete = array_values(array_unique(array_filter($iconIdsToDelete, fn($iconId) => $iconId > 0)));

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $maxItemIdBeforeDelete = (int) ($game->table('item_template')->max('id') ?? 0);
            if ($deletedParts) {
                $game->table('head_avatar')->whereIn('head_id', $deletedParts)->delete();
                $game->table('array_head_2_frames')->whereIn('id', $deletedParts)->delete();
                $game->table('part')->whereIn('id', $deletedParts)->delete();
            }

            $game->table('item_template')->where('id', $id)->delete();
            $filledItemIds = $this->ensureItemTemplateContinuity($game, max($maxItemIdBeforeDelete, $id));

            $moves = $this->compactPartIds($game, $deletedParts);
            $safeIconIds = $this->filterUnreferencedCostumeIconIds($game, $iconIdsToDelete);
            $deletedIconFiles = $this->deleteGameIconFiles($safeIconIds);

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            $game->rollBack();
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => 'Không xóa được cải trang: ' . $e->getMessage()], 500);
        }

        $this->logAdminAction(
            'costume.delete',
            'item_template',
            $id,
            'Xóa cải trang ' . ($item->NAME ?? ('#' . $id)),
            $this->sanitizeLogState((array) $item),
            [
                'deleted_part_ids' => $deletedParts,
                'filled_item_template_ids' => $filledItemIds,
                'compacted_part_moves' => $moves,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        );
        return response()->json([
            'ok' => true,
            'message' => 'Đã xóa cải trang và dồn lại ID part',
            'data' => [
                'deleted_item_id' => $id,
                'deleted_part_ids' => $deletedParts,
                'filled_item_template_ids' => $filledItemIds,
                'compacted_part_moves' => $moves,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ]);
    }

    private function gameSrcPath(string $child = ''): string
    {
        $candidates = array_filter([
            env('GAME_SRC_PATH'),
            rtrim((string) getenv('USERPROFILE'), '\\/') . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'SRC' . DIRECTORY_SEPARATOR . 'SRC',
            base_path('../../../../SRC/SRC'),
            base_path('../../../SRC'),
        ]);

        foreach ($candidates as $candidate) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
            if (is_dir($path . DIRECTORY_SEPARATOR . 'data')) {
                return rtrim($path, DIRECTORY_SEPARATOR) . ($child !== '' ? DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $child) : '');
            }
        }

        $fallback = rtrim((string) ($candidates[0] ?? base_path()), DIRECTORY_SEPARATOR);
        return $fallback . ($child !== '' ? DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $child) : '');
    }

    private function nextGameAssetId(string $assetDir, string $table, string $column): int
    {
        $dbMax = (int) (DB::connection('game')->table($table)->max($column) ?? 0);
        $fileMax = 0;
        $dir = $this->gameSrcPath("data/{$assetDir}/x4");
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.png') ?: [] as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (is_numeric($name)) {
                    $fileMax = max($fileMax, (int) $name);
                }
            }
        }
        return max($dbMax, $fileMax) + 1;
    }

    private function nextGameEffectId(): int
    {
        $dbMax = (int) (DB::connection('game')->table('item_template')->where('part', '>=', 0)->max('part') ?? 0);
        $fileMax = 0;
        $dir = $this->gameSrcPath('data/effdata');
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . 'DataEffect_*') ?: [] as $file) {
                $name = preg_replace('/\D+/', '', pathinfo($file, PATHINFO_FILENAME));
                if (is_numeric($name)) {
                    $fileMax = max($fileMax, (int) $name);
                }
            }
        }
        return max($dbMax, $fileMax) + 1;
    }

    private function saveGameUpload($file, string $relativePath): string
    {
        $path = $this->gameSrcPath($relativePath);
        File::ensureDirectoryExists(dirname($path), 0775, true);
        $file->move(dirname($path), basename($path));
        return $path;
    }

    private function saveGamePngPyramid($file, string $baseDir, string $filename, ?int $maxX4Size = null): array
    {
        $source = @imagecreatefrompng($file->getRealPath());
        if (!$source) {
            throw new \RuntimeException('File ' . $file->getClientOriginalName() . ' không phải PNG hợp lệ.');
        }

        return $this->saveGamePngPyramidResource($source, $baseDir, $filename, $maxX4Size);
    }

    private function saveGamePngPyramidFromBytes(string $bytes, string $originalName, string $baseDir, string $filename, ?int $maxX4Size = null): array
    {
        $source = @imagecreatefromstring($bytes);
        if (!$source) {
            throw new \RuntimeException('File ' . $originalName . ' không phải PNG hợp lệ.');
        }

        return $this->saveGamePngPyramidResource($source, $baseDir, $filename, $maxX4Size);
    }

    private function saveGamePngPyramidResource($source, string $baseDir, string $filename, ?int $maxX4Size = null): array
    {
        imagesavealpha($source, true);
        $width = imagesx($source);
        $height = imagesy($source);
        $baseScale = 1.0;
        if ($maxX4Size && max($width, $height) > $maxX4Size) {
            $baseScale = $maxX4Size / max($width, $height);
        }
        $saved = [];

        foreach ([4 => 1, 3 => 0.75, 2 => 0.5, 1 => 0.25] as $zoom => $scale) {
            $targetWidth = max(1, (int) round($width * $baseScale * $scale));
            $targetHeight = max(1, (int) round($height * $baseScale * $scale));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            $path = $this->gameSrcPath($baseDir . "/x{$zoom}/{$filename}");
            File::ensureDirectoryExists(dirname($path), 0775, true);
            imagepng($target, $path);
            imagedestroy($target);
            $saved[] = $path;
        }

        imagedestroy($source);
        return $saved;
    }

    private function decodeUploadedImagePayload(string $payload): array
    {
        $payload = trim($payload);
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Payload ảnh không hợp lệ.');
        }

        $files = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry) || empty($entry['name']) || empty($entry['data'])) {
                continue;
            }
            $name = basename(str_replace('\\', '/', (string) $entry['name']));
            if (!str_ends_with(strtolower($name), '.png')) {
                throw new \RuntimeException('Chỉ hỗ trợ PNG trong payload ảnh: ' . $name);
            }
            $bytes = base64_decode((string) $entry['data'], true);
            if ($bytes === false) {
                throw new \RuntimeException('Không decode được ảnh: ' . $name);
            }
            $files[] = ['name' => $name, 'bytes' => $bytes];
        }

        return $files;
    }

    private function requestFiles(Request $request, string $field): array
    {
        $files = $request->file($field);
        if (!$files) {
            return [];
        }
        return is_array($files) ? array_values(array_filter($files)) : [$files];
    }

    private function numericIdFromFilename(string $filename): ?int
    {
        $name = pathinfo(str_replace('\\', '/', $filename), PATHINFO_FILENAME);
        if (preg_match('/(\d+)(?!.*\d)/', $name, $matches)) {
            $id = (int) $matches[1];
            return $id > 0 && $id <= 32767 ? $id : null;
        }
        return null;
    }

    private function resolveGameAssetId(?int $candidate, string $kind, array $used): int
    {
        if ($candidate !== null && $candidate > 0 && !isset($used[$candidate]) && $this->gameAssetIdAvailable($candidate, $kind)) {
            return $candidate;
        }

        $id = $candidate !== null && $candidate > 0 && $candidate <= 32767
            ? $candidate + 1
            : $this->nextFreeGameAssetStart($kind);
        while (!$this->gameAssetIdAvailable($id, $kind) || isset($used[$id])) {
            $id++;
            if ($id > 32767) {
                throw new \RuntimeException('Không còn ID trống hợp lệ cho ' . $kind . ' (1-32767).');
            }
        }
        return $id;
    }

    private function nextCostumeIconId(): int
    {
        $fileMax = $this->maxNumericAssetFileId('data/icon/x4', '/^(\d+)$/');
        $templateMax = (int) (DB::connection('game')->table('item_template')->max('icon_id') ?? 0);
        $avatarMax = Schema::connection('game')->hasTable('head_avatar')
            ? (int) (DB::connection('game')->table('head_avatar')->max('avatar_id') ?? 0)
            : 0;
        return max(1, min(32767, max($fileMax, $templateMax, $avatarMax) + 1));
    }

    private function resolveCostumeIconId(?int $candidate, array $used): int
    {
        if ($candidate !== null && $candidate > 0 && !isset($used[$candidate]) && $this->costumeIconIdAvailable($candidate)) {
            return $candidate;
        }

        $id = $candidate !== null && $candidate > 0 && $candidate <= 32767
            ? $candidate + 1
            : $this->nextCostumeIconId();
        while (!$this->costumeIconIdAvailable($id) || isset($used[$id])) {
            $id++;
            if ($id > 32767) {
                throw new \RuntimeException('Không còn icon ID trống hợp lệ cho cải trang (1-32767).');
            }
        }
        return $id;
    }

    private function costumeIconIdAvailable(int $id): bool
    {
        if ($id <= 0 || $id > 32767) {
            return false;
        }

        foreach ([1, 2, 3, 4] as $zoom) {
            if (is_file($this->gameSrcPath("data/icon/x{$zoom}/{$id}.png"))) {
                return false;
            }
        }
        return true;
    }

    private function parsePartLayers(string $raw): array
    {
        $normalized = trim($raw);
        if ($normalized === '') {
            throw new \InvalidArgumentException('DATA part không được để trống.');
        }

        $decoded = json_decode($this->fixJson($normalized), true);
        if (is_string($decoded)) {
            $decoded = json_decode($this->fixJson($decoded), true);
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('DATA part phải là JSON dạng [[icon_id,dx,dy], ...].');
        }

        $layers = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry) || count($entry) < 3) {
                throw new \InvalidArgumentException('Mỗi layer part phải có đủ 3 giá trị [icon_id, dx, dy].');
            }
            $layers[] = [
                (int) $entry[0],
                (int) $entry[1],
                (int) $entry[2],
            ];
        }

        if (!$layers) {
            throw new \InvalidArgumentException('DATA part không có layer hợp lệ.');
        }

        return $layers;
    }

    private function parseExtraPartLayerBlocks(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $blocks = preg_split('/\R{2,}/', $raw) ?: [];
        if (count($blocks) === 1) {
            $decoded = json_decode($this->fixJson($raw), true);
            if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0][0]) && is_array($decoded[0][0])) {
                return array_map(fn($block) => $this->parsePartLayers(json_encode($block, JSON_UNESCAPED_UNICODE)), $decoded);
            }
        }

        return array_values(array_filter(array_map(function ($block) {
            $block = trim((string) $block);
            return $block === '' ? null : $this->parsePartLayers($block);
        }, $blocks)));
    }

    private function rewritePartLayers(array $layers, array $idMap): array
    {
        return array_map(function ($entry) use ($idMap) {
            $iconId = (int) $entry[0];
            return [
                $idMap[$iconId] ?? $iconId,
                (int) $entry[1],
                (int) $entry[2],
            ];
        }, $layers);
    }

    private function completeSequentialSpriteIdMap(array $idMap, array $uploadedIds, array $layers): array
    {
        $uploadedIds = array_values(array_unique(array_filter(array_map('intval', $uploadedIds), fn($id) => $id > 0)));
        if (!$uploadedIds) {
            return $idMap;
        }

        $sourceIds = [];
        foreach ($layers as $entry) {
            if (!is_array($entry) || !isset($entry[0])) {
                continue;
            }
            $sourceId = (int) $entry[0];
            if ($sourceId > 0 && !isset($idMap[$sourceId])) {
                $sourceIds[$sourceId] = true;
            }
        }

        $sourceIds = array_keys($sourceIds);
        sort($sourceIds);
        $availableUploadedIds = $uploadedIds;
        foreach ($sourceIds as $index => $sourceId) {
            if (!isset($availableUploadedIds[$index])) {
                break;
            }
            $idMap[$sourceId] = $availableUploadedIds[$index];
        }

        return $idMap;
    }

    private function temporaryCostumeSourceIconIds(Request $request): array
    {
        $raw = implode("\n", [
            (string) $request->input('head_data', ''),
            (string) $request->input('body_data', ''),
            (string) $request->input('leg_data', ''),
            (string) $request->input('extra_head_data', ''),
        ]);

        $ids = [];
        if (preg_match_all('/\[\s*(\d+)\s*,\s*-?\d+\s*,\s*-?\d+\s*\]/', $raw, $matches)) {
            foreach ($matches[1] as $value) {
                $id = (int) $value;
                if ($id > 0 && $id < 1000) {
                    $ids[$id] = true;
                }
            }
        }

        return $ids;
    }

    private function compactPartIds($game, array $deletedIds): array
    {
        $holes = array_values(array_unique(array_filter(array_map('intval', $deletedIds), fn($id) => $id > 0)));
        sort($holes);
        if (!$holes) {
            return [];
        }

        $moves = [];
        foreach ($holes as $hole) {
            if ($game->table('part')->where('id', $hole)->exists()) {
                continue;
            }

            $source = $game->table('part')
                ->where('id', '>', $hole)
                ->orderByDesc('id')
                ->first();

            if (!$source || (int) $source->id <= $hole) {
                continue;
            }

            $sourceId = (int) $source->id;
            $game->table('part')->where('id', $sourceId)->update(['id' => $hole]);

            foreach (['part', 'head', 'body', 'leg'] as $column) {
                if (Schema::connection('game')->hasColumn('item_template', $column)) {
                    $game->table('item_template')
                        ->where($column, $sourceId)
                        ->update([$column => $hole]);
                }
            }

            $game->table('head_avatar')
                ->where('head_id', $sourceId)
                ->update(['head_id' => $hole]);

            if (Schema::connection('game')->hasTable('array_head_2_frames')) {
                $game->table('array_head_2_frames')
                    ->where('id', $sourceId)
                    ->update(['id' => $hole]);

                foreach ($game->table('array_head_2_frames')->get(['id', 'data']) as $row) {
                    $heads = $this->decodeHeadFrameIds((string) ($row->data ?? ''));
                    if (!in_array($sourceId, $heads, true)) {
                        continue;
                    }
                    $heads = array_map(fn($headId) => $headId === $sourceId ? $hole : $headId, $heads);
                    $game->table('array_head_2_frames')
                        ->where('id', (int) $row->id)
                        ->update(['data' => json_encode($heads, JSON_UNESCAPED_UNICODE)]);
                }
            }

            $moves[] = ['from' => $sourceId, 'to' => $hole];
        }

        return $moves;
    }

    private function syncCostumeHeadFrames($game, array $headIds): void
    {
        if (!Schema::connection('game')->hasTable('array_head_2_frames')) {
            return;
        }

        $headIds = array_values(array_unique(array_filter(array_map('intval', $headIds), fn($id) => $id > 0)));
        if (!$headIds) {
            return;
        }

        $mainHeadId = $headIds[0];
        $game->table('array_head_2_frames')
            ->whereIn('id', $headIds)
            ->where('id', '<>', $mainHeadId)
            ->delete();

        if (count($headIds) < 2) {
            $game->table('array_head_2_frames')->where('id', $mainHeadId)->delete();
            return;
        }

        $game->table('array_head_2_frames')->updateOrInsert(
            ['id' => $mainHeadId],
            ['data' => json_encode($headIds, JSON_UNESCAPED_UNICODE)],
        );
    }

    private function decodeHeadFrameIds(string $raw): array
    {
        $decoded = json_decode($this->fixJson(trim($raw)), true);
        if (is_string($decoded)) {
            $decoded = json_decode($this->fixJson($decoded), true);
        }
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $decoded), fn($id) => $id > 0));
    }

    private function ensureItemTemplateContinuity($game, int $targetId): array
    {
        if ($targetId < 0) {
            return [];
        }

        $existing = $game->table('item_template')
            ->whereBetween('id', [0, $targetId])
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
        $set = array_flip($existing);
        $inserted = [];

        for ($id = 0; $id <= $targetId; $id++) {
            if (isset($set[$id])) {
                continue;
            }

            $row = [
                'id' => $id,
                'TYPE' => 0,
                'gender' => 3,
                'NAME' => "__reserved_item_template_{$id}",
                'description' => 'Reserved placeholder created by admin panel',
                'level' => 0,
                'icon_id' => 0,
                'part' => -1,
                'is_up_to_up' => 0,
                'power_require' => 0,
                'gold' => 0,
                'gem' => 0,
                'head' => -1,
                'body' => -1,
                'leg' => -1,
                'is_up_to_up_over_99' => 0,
                'can_trade' => 0,
                'comment' => 'Admin reserved placeholder to keep item_template IDs contiguous',
            ];

            foreach (array_keys($row) as $column) {
                if (!Schema::connection('game')->hasColumn('item_template', $column)) {
                    unset($row[$column]);
                }
            }

            $game->table('item_template')->insert($row);
            $inserted[] = $id;
        }

        return $inserted;
    }

    private function isReservedItemTemplate(object $item): bool
    {
        $name = (string) ($item->NAME ?? $item->name ?? '');
        $comment = (string) ($item->comment ?? '');
        return str_starts_with($name, '__reserved_item_template_')
            || str_contains($comment, 'Admin reserved placeholder');
    }

    private function costumeExtraHeadIdsFromComment(string $comment): array
    {
        if (!preg_match('/extra_heads=([^;]*)/', $comment, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn($value) => (int) trim($value),
            explode(',', $matches[1]),
        ), fn($id) => $id > 0));
    }

    private function costumeAvatarIdFromComment(string $comment): int
    {
        return preg_match('/avatar=(\d+)/', $comment, $matches)
            ? (int) $matches[1]
            : 0;
    }

    private function looksLikeCostumeExtraHead($game, int $headId, array $knownPartIds): bool
    {
        if (isset($knownPartIds[$headId])) {
            return true;
        }

        $part = $game->table('part')->where('id', $headId)->first(['TYPE']);
        if (!$part || (int) $part->TYPE !== 0) {
            return false;
        }

        $referencedByItem = $game->table('item_template')
            ->where(function ($q) use ($headId) {
                foreach (['part', 'head', 'body', 'leg'] as $column) {
                    if (Schema::connection('game')->hasColumn('item_template', $column)) {
                        $q->orWhere($column, $headId);
                    }
                }
            })
            ->exists();

        return !$referencedByItem;
    }

    private function filterUnreferencedCostumeIconIds($game, array $iconIds): array
    {
        $iconIds = array_values(array_unique(array_filter(array_map('intval', $iconIds), fn($id) => $id > 0)));
        if (!$iconIds) {
            return [];
        }

        $referenced = [];
        $templateIconIds = $game->table('item_template')
            ->whereIn('icon_id', $iconIds)
            ->pluck('icon_id')
            ->all();
        foreach ($templateIconIds as $iconId) {
            $referenced[(int) $iconId] = true;
        }

        if (Schema::connection('game')->hasTable('head_avatar')) {
            $avatarIds = $game->table('head_avatar')
                ->whereIn('avatar_id', $iconIds)
                ->pluck('avatar_id')
                ->all();
            foreach ($avatarIds as $iconId) {
                $referenced[(int) $iconId] = true;
            }
        }

        $parts = $game->table('part')->get(['DATA']);
        foreach ($parts as $part) {
            foreach ($this->decodePartData($part->DATA ?? '') as $layer) {
                $iconId = (int) ($layer['icon_id'] ?? 0);
                if (in_array($iconId, $iconIds, true)) {
                    $referenced[$iconId] = true;
                }
            }
        }

        return array_values(array_filter($iconIds, fn($iconId) => !isset($referenced[$iconId])));
    }

    private function deleteGameIconFiles(array $iconIds): array
    {
        $deleted = [];
        foreach (array_unique(array_map('intval', $iconIds)) as $iconId) {
            if ($iconId <= 0) {
                continue;
            }
            foreach ([1, 2, 3, 4] as $zoom) {
                $path = $this->gameSrcPath("data/icon/x{$zoom}/{$iconId}.png");
                if (is_file($path) && @unlink($path)) {
                    $deleted[] = $path;
                }
            }
        }
        return $deleted;
    }

    private function gameAssetIdAvailable(int $id, string $kind): bool
    {
        if ($id <= 0 || $id > 32767) {
            return false;
        }

        if ($kind === 'icon') {
            if (DB::connection('game')->table('item_template')->where('icon_id', $id)->exists()) {
                return false;
            }
            foreach ([1, 2, 3, 4] as $zoom) {
                if (is_file($this->gameSrcPath("data/icon/x{$zoom}/{$id}.png"))) {
                    return false;
                }
            }
            return true;
        }

        if (DB::connection('game')->table('item_template')->where('part', $id)->exists()) {
            return false;
        }
        if (is_file($this->gameSrcPath("data/effdata/DataEffect_{$id}"))) {
            return false;
        }
        foreach ([1, 2, 3, 4] as $zoom) {
            if (
                is_file($this->gameSrcPath("data/effect/x{$zoom}/ImgEffect_{$id}.png")) ||
                is_file($this->gameSrcPath("data/effect/x{$zoom}/ImageEffect_{$id}.png"))
            ) {
                return false;
            }
        }
        return true;
    }

    private function nextFreeGameAssetStart(string $kind): int
    {
        if ($kind === 'icon') {
            $dbMax = (int) (DB::connection('game')->table('item_template')->max('icon_id') ?? 0);
            $fileMax = $this->maxNumericAssetFileId('data/icon/x4', '/^(\d+)$/');
            return max(1, min(32767, max($dbMax, $fileMax) + 1));
        }

        $dbMax = (int) (DB::connection('game')->table('item_template')->where('part', '>=', 0)->max('part') ?? 0);
        $effectMax = $this->maxNumericAssetFileId('data/effect/x4', '/(?:ImgEffect_|ImageEffect_)?(\d+)$/i');
        $dataMax = $this->maxNumericAssetFileId('data/effdata', '/DataEffect_(\d+)$/i');
        return max(1, min(32767, max($dbMax, $effectMax, $dataMax) + 1));
    }

    private function maxNumericAssetFileId(string $relativeDir, string $pattern): int
    {
        $max = 0;
        $dir = $this->gameSrcPath($relativeDir);
        if (!is_dir($dir)) {
            return 0;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match($pattern, $name, $matches)) {
                $id = (int) $matches[1];
                if ($id > 0 && $id <= 32767) {
                    $max = max($max, $id);
                }
            }
        }
        return $max;
    }

    private function pairTitleAssetIds(array $iconIds, array $effectIds, array $fallbackIds): array
    {
        $iconIds = array_values(array_unique(array_map('intval', $iconIds)));
        $effectIds = array_values(array_unique(array_map('intval', $effectIds)));
        $fallbackIds = array_values(array_unique(array_map('intval', $fallbackIds)));
        sort($iconIds);
        sort($effectIds);
        sort($fallbackIds);

        if ($iconIds && $effectIds) {
            $count = max(count($iconIds), count($effectIds));
            $pairs = [];
            for ($i = 0; $i < $count; $i++) {
                $iconId = $iconIds[$i] ?? $iconIds[count($iconIds) - 1];
                $effectId = $effectIds[$i] ?? $effectIds[count($effectIds) - 1];
                $pairs[] = ['icon_id' => $iconId, 'part' => $effectId];
            }
            return $pairs;
        }

        $ids = $iconIds ?: ($effectIds ?: $fallbackIds);
        return array_map(fn($id) => ['icon_id' => $id, 'part' => $id], $ids);
    }

    public function accountsBadgesUpdate(Request $request, int $id): JsonResponse
    {
        if (!Schema::connection('game')->hasColumn('player', 'dataBadges')) {
            return response()->json(['ok' => false, 'message' => 'Bảng player chưa có cột dataBadges'], 422);
        }

        $rows = $request->input('badges', []);
        if (!is_array($rows)) {
            return response()->json(['ok' => false, 'message' => 'Dữ liệu badge không hợp lệ'], 422);
        }

        $player = DB::connection('game')->table('player')
            ->where('account_id', $id)
            ->first(['id', 'account_id', 'name', 'dataBadges']);

        if (!$player) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản chưa có nhân vật'], 404);
        }

        $badges = collect($rows)->map(function ($row) {
            $row = is_array($row) ? $row : [];
            return [
                'idBadGes' => max(0, (int) ($row['idBadGes'] ?? $row['id'] ?? 0)),
                'timeofUseBadges' => max(0, (int) ($row['timeofUseBadges'] ?? $row['expires_at'] ?? 0)),
                'isUse' => !empty($row['isUse'] ?? $row['is_use'] ?? false),
            ];
        })->filter(fn($row) => $row['idBadGes'] > 0)->values()->all();

        $before = (array) $player;
        DB::connection('game')->table('player')
            ->where('id', $player->id)
            ->update(['dataBadges' => json_encode($badges, JSON_UNESCAPED_UNICODE)]);

        $after = DB::connection('game')->table('player')
            ->where('id', $player->id)
            ->first(['id', 'account_id', 'name', 'dataBadges']);

        Cache::forget("admin:player-section:{$id}:dataBadges");
        $this->logAdminAction(
            'player.badges.update',
            'player',
            (int) $player->id,
            "Cập nhật danh hiệu nhân vật {$player->name}",
            $this->sanitizeLogState($before),
            $this->sanitizeLogState((array) $after),
            ['account_id' => $id, 'badge_count' => count($badges)]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật danh hiệu',
            'data' => [
                'raw' => $after->dataBadges ?? '[]',
                'parsed' => $this->parsePlayerBadges($after->dataBadges ?? '[]'),
            ],
        ]);
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
        $genderInput = $request->query('gender');
        $lite = $request->boolean('lite');
        $type = null;
        if ($typeInput !== null) {
            $typeStr = trim((string) $typeInput);
            if ($typeStr !== '' && !in_array(strtolower($typeStr), ['undefined', 'null', 'nan'], true)) {
                $type = is_numeric($typeStr) ? (int) $typeStr : $typeStr;
            }
        }
        $gender = null;
        if ($genderInput !== null) {
            $genderStr = trim((string) $genderInput);
            if ($genderStr !== '' && !in_array(strtolower($genderStr), ['undefined', 'null', 'nan'], true)) {
                $gender = is_numeric($genderStr) ? (int) $genderStr : $genderStr;
            }
        }
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
                } else {
                    if ($usingWebIndex) {
                        $q->where(function ($nested) use ($search) {
                            $nested->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('normalized_name', 'LIKE', '%' . mb_strtolower($search) . '%');
                        });
                    } else {
                        $q->where('NAME', 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        if ($type !== null) {
            $query->where('type', $type);
        }
        if ($gender !== null) {
            $query->where('gender', $gender);
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
            });

        return response()->json($items);
    }

    // PUT /api/admin/items/{id}
    public function itemsUpdate(Request $request, int $id): JsonResponse
    {
        $game = DB::connection('game');
        $table = $game->table('item_template');
        $before = $table->where('id', $id)->first();

        if (!$before) {
            return response()->json(['ok' => false, 'message' => 'Item không tồn tại'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|integer',
            'gender' => 'nullable|integer|min:0|max:3',
            'icon_id' => 'required|integer|min:0',
            'part' => 'nullable|integer|min:-1',
            'head' => 'nullable|integer|min:-1',
            'body' => 'nullable|integer|min:-1',
            'leg' => 'nullable|integer|min:-1',
            'description' => 'nullable|string|max:2000',
            'is_up_to_up' => 'nullable|boolean',
        ]);

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

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'Không có cột item nào có thể cập nhật'], 422);
        }

        $table->where('id', $id)->update($data);
        Cache::forget('admin:item_type_options:v2');
        Cache::forget('admin:item_type_options:web:v1');

        $after = $game->table('item_template')->where('id', $id)->first();
        $this->logAdminAction(
            'item.update',
            'item',
            $id,
            "Cập nhật item #{$id} " . ($data['NAME'] ?? ($before->NAME ?? '')),
            $this->sanitizeLogState((array) $before),
            $this->sanitizeLogState((array) $after)
        );

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật item',
            'data' => $after,
        ]);
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
