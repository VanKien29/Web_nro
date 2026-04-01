<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopupCardController extends Controller
{
    /**
     * POST /api/topup/log — Create trans_log
     */
    public function create(Request $request): JsonResponse
    {
        $username = strtolower(trim($request->input('username', '')));
        $seri = trim($request->input('seri', ''));
        $pin = trim($request->input('pin', ''));
        $type = trim($request->input('type', ''));
        $amount = (int) $request->input('amount', 0);
        $transId = trim($request->input('trans_id', ''));
        $status = (int) $request->input('status', 0);

        if (empty($username) || empty($seri) || empty($pin) || empty($type) || $amount <= 0 || empty($transId)) {
            return response()->json(['ok' => false, 'error' => 'invalid_input'], 422);
        }

        // Check duplicate
        $exists = DB::connection('game')->table('trans_log')
            ->where('trans_id', $transId)
            ->exists();

        if ($exists) {
            return response()->json(['ok' => true, 'message' => 'already_exists']);
        }

        try {
            $id = DB::connection('game')->table('trans_log')->insertGetId([
                'username' => $username,
                'seri' => $seri,
                'pin' => $pin,
                'type' => $type,
                'amount' => $amount,
                'trans_id' => $transId,
                'status' => $status,
            ]);

            return response()->json([
                'ok' => true,
                'id' => $id,
                'trans_id' => $transId,
            ]);
        } catch (\Throwable $e) {
            Log::error('TopupCard create error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'database_error'], 500);
        }
    }

    /**
     * GET /api/topup/log — Get trans_log by params
     */
    public function get(Request $request): JsonResponse
    {
        $transId = $request->query('trans_id', '');
        $seri = $request->query('seri', '');
        $pin = $request->query('pin', '');
        $type = $request->query('type', '');

        if (empty($transId) || empty($seri) || empty($pin) || empty($type)) {
            return response()->json(['ok' => false, 'error' => 'missing_params'], 422);
        }

        $trans = DB::connection('game')->table('trans_log')
            ->where('trans_id', $transId)
            ->where('seri', $seri)
            ->where('pin', $pin)
            ->where('type', $type)
            ->first();

        if (!$trans) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        return response()->json(['ok' => true, 'data' => $trans]);
    }

    /**
     * PUT /api/topup/log/{transId} — Update trans_log status
     */
    public function update(Request $request, string $transId): JsonResponse
    {
        $status = (int) $request->input('status', -1);
        $amount = (int) $request->input('amount', 0);

        if ($status < 0) {
            return response()->json(['ok' => false, 'error' => 'invalid_status'], 422);
        }

        try {
            $trans = DB::connection('game')->table('trans_log')
                ->where('trans_id', $transId)
                ->where('status', 0)
                ->first();

            if (!$trans) {
                return response()->json(['ok' => false, 'error' => 'not_found'], 404);
            }

            DB::connection('game')->table('trans_log')
                ->where('id', $trans->id)
                ->update(['status' => $status, 'amount' => $amount]);

            // If success (status = 1), credit account to both cash and danap
            if ($status === 1) {
                Account::where('username', $trans->username)
                    ->update([
                        'cash' => DB::raw("cash + {$amount}"),
                        'danap' => DB::raw("danap + {$amount}"),
                    ]);
            }

            return response()->json([
                'ok' => true,
                'trans_id' => $transId,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('TopupCard update error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'database_error'], 500);
        }
    }

    /**
     * GET /api/topup/log/history/{username} — Card topup history
     */
    public function history(string $username): JsonResponse
    {
        try {
            $data = DB::connection('game')->table('trans_log')
                ->where('username', strtolower($username))
                ->orderByDesc('created_at')
                ->limit(50)
                ->select('id', 'seri', 'pin', 'type', 'amount', 'trans_id', 'status', 'created_at')
                ->get();

            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Log::error('TopupCard history error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'database_error'], 500);
        }
    }
}