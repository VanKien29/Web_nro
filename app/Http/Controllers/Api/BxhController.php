<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BxhController extends Controller
{
    public function index(): JsonResponse
    {
        $game = DB::connection('game');

        // TOP NẠP
        $topNap = $game->table('account as a')
            ->join('player as p', 'a.id', '=', 'p.account_id')
            ->where('a.active', 1)
            ->orderByDesc('a.danap')
            ->orderByDesc('a.cash')
            ->limit(10)
            ->select('p.name as player_name', DB::raw('COALESCE(a.danap, 0) as danap'))
            ->get();

        // TOP SỨC MẠNH
        $topPower = $game->table('account as a')
            ->join('player as p', 'a.id', '=', 'p.account_id')
            ->where('a.active', 1)
            ->whereNotNull('p.data_point')
            ->where('p.data_point', '!=', '')
            ->select(
                'p.name as player_name',
                DB::raw("CAST(JSON_EXTRACT(p.data_point, '\$[1]') AS UNSIGNED) as power")
            )
            ->orderByDesc('power')
            ->limit(10)
            ->get();

        // TOP NHIỆM VỤ
        $rows = $game->table('account as a')
            ->join('player as p', 'a.id', '=', 'p.account_id')
            ->where('a.active', 1)
            ->whereNotNull('p.data_task')
            ->where('p.data_task', '!=', '')
            ->select(
                'p.name as player_name',
                DB::raw("CAST(JSON_EXTRACT(p.data_task, '\$[0]') AS UNSIGNED) as task_sort"),
                'p.data_task'
            )
            ->orderByDesc('task_sort')
            ->limit(10)
            ->get();

        $topTask = $rows->map(function ($row) {
            $task = json_decode($row->data_task, true);
            return [
                'player_name' => $row->player_name,
                'task' => [
                    'id' => $task[0] ?? 0,
                    'step' => $task[1] ?? 0,
                    'sub' => $task[2] ?? 0,
                    'state' => $task[3] ?? 0,
                    'raw' => $row->data_task,
                ],
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'top_nap' => $topNap,
                'top_power' => $topPower,
                'top_task' => $topTask,
            ],
        ]);
    }
}
