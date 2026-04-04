<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $game = DB::connection('game');

        $stats = [
            'accounts' => $game->table('account')->count(),
            'topups' => $game->table('topup_transactions')->count(),
            'today_revenue' => (int) $game->table('topup_transactions')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'month_revenue' => (int) $game->table('topup_transactions')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        $latestTopups = $game->table('topup_transactions')
            ->select('trans_id', 'username', 'amount', 'currency', 'source', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $topUsers = $game->table('topup_transactions')
            ->select('username', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as topup_count'))
            ->groupBy('username')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'latestTopups' => $latestTopups,
            'topUsers' => $topUsers,
        ]);
    }
}