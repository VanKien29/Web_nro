<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use App\Models\Game\TopupTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopupController extends Controller
{
    /**
     * POST /api/topup/credit — Called by SePay / internal system
     * Protected by topup.secret middleware
     */
    public function credit(Request $request): JsonResponse
    {
        $username = strtolower(trim($request->input('username', '')));
        $transId = trim($request->input('trans_id', ''));
        $amount = (int) $request->input('amount', 0);
        $currency = $request->input('currency', 'cash');
        $note = trim($request->input('note', ''));
        $addBothFields = (bool) $request->input('add_both_fields', false);

        if ($username === '' || $transId === '' || $amount <= 0) {
            $this->logSepay($transId, $username, $amount, 'invalid', $request->all());
            return response()->json(['ok' => false, 'error' => 'invalid_payload'], 422);
        }

        if (!in_array($currency, ['cash', 'danap'], true)) {
            $this->logSepay($transId, $username, $amount, 'invalid', $request->all());
            return response()->json(['ok' => false, 'error' => 'invalid_currency'], 422);
        }

        // Chống cộng trùng
        if (TopupTransaction::where('trans_id', $transId)->exists()) {
            $this->logSepay($transId, $username, $amount, 'duplicate', $request->all());
            return response()->json(['ok' => true, 'status' => 'duplicate']);
        }

        // Tìm user
        $account = Account::where('username', $username)->first();
        if (!$account) {
            $this->logSepay($transId, $username, $amount, 'user_not_found', $request->all());
            return response()->json(['ok' => false, 'error' => 'user_not_found'], 404);
        }

        try {
            DB::connection('game')->transaction(function () use ($account, $transId, $username, $amount, $currency, $note, $addBothFields) {
                TopupTransaction::create([
                    'trans_id' => $transId,
                    'username' => $username,
                    'user_id' => $account->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'source' => 'sepay',
                    'note' => $note,
                ]);

                if ($addBothFields) {
                    $account->increment('cash', $amount);
                    $account->increment('danap', $amount);
                } elseif ($currency === 'cash') {
                    $account->increment('cash', $amount);
                } else {
                    $account->increment('danap', $amount);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Topup credit error: ' . $e->getMessage());
            $this->logSepay($transId, $username, $amount, 'server_error', $request->all());
            return response()->json(['ok' => false, 'error' => 'server_error'], 500);
        }

        $this->logSepay($transId, $username, $amount, 'credited', $request->all());

        return response()->json([
            'ok' => true,
            'status' => 'credited',
            'username' => $username,
            'amount' => $amount,
            'currency' => $currency,
            'trans_id' => $transId,
        ]);
    }

    /**
     * GET /api/topup/history — User's topup history
     * Protected by game.auth middleware
     */
    public function history(Request $request): JsonResponse
    {
        $account = $request->get('game_user');

        $history = TopupTransaction::where('user_id', $account->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['amount', 'currency', 'source', 'trans_id', 'created_at']);

        return response()->json(['ok' => true, 'data' => $history]);
    }

    private function logSepay(string $transId, string $username, int $amount, string $status, array $raw): void
    {
        try {
            DB::connection('game')->table('sepay_logs')->insertOrIgnore([
                'trans_id' => $transId,
                'username' => $username,
                'amount' => $amount,
                'status' => $status,
                'raw_json' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SePay log failed: ' . $e->getMessage());
        }
    }
}
