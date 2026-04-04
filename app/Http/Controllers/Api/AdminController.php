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

        $query = Account::select('id', 'username', 'password', 'ban', 'is_admin', 'active', 'cash', 'danap', 'coin');

        if ($search) {
            $query->where('username', 'LIKE', "%{$search}%");
        }

        $total = $query->count();
        $accounts = $query->orderBy('id')->offset(($page - 1) * $limit)->limit($limit)->get();

        return response()->json([
            'ok' => true,
            'data' => $accounts,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }

    // GET /api/admin/accounts/{id}
    public function accountsGet(int $id): JsonResponse
    {
        $account = Account::select('id', 'username', 'password', 'ban', 'is_admin', 'active', 'cash', 'danap', 'coin')
            ->find($id);

        if (!$account) {
            return response()->json(['ok' => false, 'message' => 'Tài khoản không tồn tại'], 404);
        }

        return response()->json(['ok' => true, 'data' => $account]);
    }

    // POST /api/admin/accounts
    public function accountsCreate(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Account::where('username', $request->input('username'))->exists()) {
            return response()->json(['ok' => false, 'message' => 'Tên tài khoản đã tồn tại'], 400);
        }

        $account = Account::create([
            'username' => $request->input('username'),
            'password' => $request->input('password'),
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

        $data = $request->only(['username', 'password', 'cash', 'danap', 'coin', 'ban', 'is_admin', 'active']);

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

    // ========== ITEMS MANAGEMENT ==========

    // GET /api/admin/items — paginated
    public function itemsList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $type = $request->query('type', '');
        $perPage = min((int) $request->query('per_page', 50), 200);
        $page = max((int) $request->query('page', 1), 1);

        $query = DB::connection('game')->table('item_template')
            ->select('id', 'name', 'icon_id', 'type');

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                } else {
                    $q->where('name', 'LIKE', "%{$search}%");
                }
            });
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $types = cache()->remember('item_template_types', 600, function () {
            return DB::connection('game')->table('item_template')
                ->distinct()
                ->orderBy('type')
                ->pluck('type');
        });

        return response()->json([
            'ok' => true,
            'data' => $items,
            'types' => $types,
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
}