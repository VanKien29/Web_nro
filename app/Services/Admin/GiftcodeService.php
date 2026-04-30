<?php

namespace App\Services\Admin;

use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftcodeService extends AdminServiceSupport
{
    public function list(Request $request): array
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
                ->where(fn($q) => $q->whereNull('expired')->orWhere('expired', '>=', now()));
        } elseif ($status === 'depleted') {
            $query->where('count_left', '<=', 0);
        }

        $total = $query->count();
        $giftcodes = $query->orderByDesc('id')->offset(($page - 1) * $limit)->limit($limit)->get();

        return [
            'ok' => true,
            'data' => $giftcodes,
            'item_icons' => $this->itemIconMapForDetails($giftcodes->pluck('detail')->all()),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ];
    }

    public function get(int $id): array
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return ['ok' => false, 'status' => 404, 'message' => 'Giftcode không tồn tại'];
        }

        return ['ok' => true, 'data' => $giftcode];
    }

    public function activity(int $id): array
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return ['ok' => false, 'status' => 404, 'message' => 'Giftcode không tồn tại'];
        }

        $logs = AdminActionLog::query()
            ->where('target_type', 'giftcode')
            ->where('target_id', (string) $id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return ['ok' => true, 'data' => ['giftcode' => $giftcode, 'admin_logs' => $logs]];
    }

    public function create(Request $request): array
    {
        $code = $request->input('code', $request->input('giftcode', ''));
        if (empty($code)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Code là bắt buộc'];
        }

        if (DB::connection('game')->table('giftcode')->where('code', $code)->exists()) {
            return ['ok' => false, 'status' => 400, 'message' => 'Code đã tồn tại'];
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
        $this->logAdminAction('create', 'giftcode', $id, "Tạo giftcode {$code}", null, $this->sanitizeLogState((array) $created));

        return ['ok' => true, 'message' => 'Tạo giftcode thành công', 'id' => $id];
    }

    public function update(Request $request, int $id): array
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return ['ok' => false, 'status' => 404, 'message' => 'Giftcode không tồn tại'];
        }

        $data = $request->only(['code', 'count_left', 'mtv', 'active', 'detail', 'expired']);
        if (!$data) {
            return ['ok' => false, 'status' => 400, 'message' => 'Không có dữ liệu để cập nhật'];
        }

        if (isset($data['code']) && DB::connection('game')->table('giftcode')->where('code', $data['code'])->where('id', '!=', $id)->exists()) {
            return ['ok' => false, 'status' => 400, 'message' => 'Code đã tồn tại'];
        }
        if (isset($data['count_left'])) {
            $data['count_left'] = (int) $data['count_left'];
        }
        if (isset($data['mtv'])) {
            $data['mtv'] = (int) $data['mtv'];
        }
        if (isset($data['active'])) {
            $data['active'] = (int) $data['active'];
        }

        $updated = DB::connection('game')->table('giftcode')->where('id', $id)->update($data);
        if ($updated === false) {
            return ['ok' => false, 'status' => 500, 'message' => 'Cập nhật giftcode thất bại'];
        }

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

        return ['ok' => true, 'message' => 'Cập nhật giftcode thành công'];
    }

    public function clone(int $id): array
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        if (!$giftcode) {
            return ['ok' => false, 'status' => 404, 'message' => 'Giftcode không tồn tại'];
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
        $this->logAdminAction('clone', 'giftcode', $newId, "Clone giftcode {$giftcode->code} thành {$newCode}", $this->sanitizeLogState((array) $giftcode), $this->sanitizeLogState((array) $created));

        return ['ok' => true, 'message' => 'Đã clone giftcode', 'id' => $newId];
    }

    public function delete(int $id): array
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->first();
        DB::connection('game')->table('giftcode')->where('id', $id)->delete();
        if ($giftcode) {
            $this->logAdminAction('delete', 'giftcode', $id, 'Xóa giftcode ' . $giftcode->code, $this->sanitizeLogState((array) $giftcode), null);
        }

        return ['ok' => true, 'message' => 'Đã xóa giftcode'];
    }
}
