<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function stats(): array
    {
        $game = DB::connection('game');

        return [
            'ok' => true,
            'accounts' => $game->table('account')->count(),
            'topups' => $game->table('topup_transactions')->count(),
        ];
    }

    public function history(Request $request): array
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

        return [
            'ok' => true,
            'data' => $query->orderByDesc('created_at')->limit(50)->get(),
        ];
    }

    public function revenue(Request $request): array
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

        return ['ok' => true, 'data' => $data];
    }

    public function topUsers(): array
    {
        $data = DB::connection('game')->table('topup_transactions')
            ->select('username', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('username')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return ['ok' => true, 'data' => $data];
    }

    public function monthlyRevenue(Request $request): array
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

        return ['ok' => true, 'total' => (int) $total];
    }

    public function overview(): array
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

        $sevenDayRevenue = $this->dailyRevenueSeries(6);
        $thirtyDayRevenue = $this->dailyRevenueSeries(29);

        $sourceRevenue = $game->table('topup_transactions')
            ->selectRaw($sourceExpr . ' as source_label, SUM(amount) as total, COUNT(*) as count')
            ->whereRaw('created_at >= CURDATE() - INTERVAL 29 DAY')
            ->groupBy(DB::raw($sourceExpr))
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn($row) => [
                'source' => $row->source_label ?: 'Khác',
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ])
            ->values();

        $recentTransactions = $game->table('topup_transactions')
            ->select('trans_id', 'username', 'amount', 'currency', 'source', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'trans_id' => $row->trans_id,
                'username' => $row->username,
                'amount' => (int) ($row->amount ?? 0),
                'currency' => $row->currency,
                'source' => $row->source,
                'created_at' => $row->created_at,
            ])
            ->values();

        $topUsers = $game->table('topup_transactions')
            ->select('username', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('username')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($row) => [
                'username' => $row->username,
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ])
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
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'username' => $row->username,
                'player_name' => $row->player_name,
                'create_time' => $row->create_time,
                'active' => (int) ($row->active ?? 0),
                'ban' => (int) ($row->ban ?? 0),
                'cash' => (int) ($row->cash ?? 0),
                'danap' => (int) ($row->danap ?? 0),
            ])
            ->values();

        return [
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
                'system_summary' => $this->systemSummary($stats),
                'milestone_breakdown' => $milestoneBreakdown,
            ],
        ];
    }

    private function dailyRevenueSeries(int $daysBack): array
    {
        $game = DB::connection('game');
        $today = now();
        $rows = $game->table('topup_transactions')
            ->selectRaw('DATE(created_at) as label, SUM(amount) as total, COUNT(*) as count')
            ->whereRaw("created_at >= CURDATE() - INTERVAL {$daysBack} DAY")
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('label');

        $series = [];
        for ($i = $daysBack; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $row = $rows->get($date->toDateString());
            $series[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        }

        return $series;
    }

    private function systemSummary(array $stats): array
    {
        return [
            ['label' => 'Tài khoản', 'count' => $stats['accounts'], 'hint' => 'Bảng account'],
            ['label' => 'Nhân vật', 'count' => $stats['players'], 'hint' => 'Bảng player'],
            ['label' => 'Giao dịch nạp', 'count' => $stats['topups'], 'hint' => 'Bảng topup_transactions'],
            ['label' => 'Giftcode', 'count' => $stats['giftcodes'], 'hint' => 'Đang bật: ' . number_format($stats['giftcodes_active'], 0, ',', '.')],
            ['label' => 'Vật phẩm mẫu', 'count' => $stats['items'], 'hint' => 'Bảng item_template'],
            ['label' => 'Shop / Tab', 'count' => $stats['shops'] . ' / ' . $stats['shop_tabs'], 'hint' => 'shop / tab_shop'],
            ['label' => 'Mốc thưởng', 'count' => $stats['milestones'], 'hint' => '4 bảng mốc chính'],
        ];
    }
}
