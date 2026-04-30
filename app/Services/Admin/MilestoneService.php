<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilestoneService extends AdminServiceSupport
{
    public function list(Request $request, string $type): array
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return ['ok' => false, 'status' => 404, 'message' => 'Loại bảng không hợp lệ'];
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
        $rawRows = $query->orderBy('id')->offset(($page - 1) * $limit)->limit($limit)->get();
        $rows = $rawRows->map(fn($row) => [
            'id' => (int) $row->id,
            'info' => (string) ($row->info ?? ''),
            'detail' => (string) ($row->detail ?? '[]'),
            'detail_count' => count($this->decodeMilestoneDetail($row->detail ?? null)),
        ])->values();

        return [
            'ok' => true,
            'type' => $resolved['type'],
            'label' => $resolved['label'],
            'data' => $rows,
            'item_icons' => $this->itemIconMapForDetails($rawRows->pluck('detail')->all()),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit),
        ];
    }

    public function get(string $type, int $id): array
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return ['ok' => false, 'status' => 404, 'message' => 'Loại bảng không hợp lệ'];
        }

        $row = DB::connection('game')->table($resolved['table'])->where('id', $id)->first();
        if (!$row) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy mốc quà'];
        }

        return [
            'ok' => true,
            'type' => $resolved['type'],
            'label' => $resolved['label'],
            'data' => [
                'id' => (int) $row->id,
                'info' => (string) ($row->info ?? ''),
                'detail' => (string) ($row->detail ?? '[]'),
            ],
        ];
    }

    public function create(Request $request, string $type): array
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return ['ok' => false, 'status' => 404, 'message' => 'Loại bảng không hợp lệ'];
        }

        $info = trim((string) $request->input('info', ''));
        if ($info === '') {
            return ['ok' => false, 'status' => 400, 'message' => 'Thông tin mốc là bắt buộc'];
        }

        $requestedId = $request->input('id');
        if ($requestedId !== null && $requestedId !== '') {
            $id = (int) $requestedId;
            if ($id <= 0) {
                return ['ok' => false, 'status' => 400, 'message' => 'ID không hợp lệ'];
            }
            if (DB::connection('game')->table($resolved['table'])->where('id', $id)->exists()) {
                return ['ok' => false, 'status' => 400, 'message' => 'ID đã tồn tại'];
            }
        } else {
            $id = ((int) DB::connection('game')->table($resolved['table'])->max('id')) + 1;
        }

        $row = [
            'id' => $id,
            'info' => $info,
            'detail' => $this->normalizeMilestoneDetail($request->input('detail', '[]')),
        ];
        DB::connection('game')->table($resolved['table'])->insert($row);

        $this->logAdminAction('milestone.create', $resolved['table'], $id, 'Tạo mốc quà ' . $info, null, $row);

        return ['ok' => true, 'message' => 'Tạo mốc quà thành công', 'id' => $id];
    }

    public function update(Request $request, string $type, int $id): array
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return ['ok' => false, 'status' => 404, 'message' => 'Loại bảng không hợp lệ'];
        }

        $before = DB::connection('game')->table($resolved['table'])->where('id', $id)->first();
        if (!$before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy mốc quà'];
        }

        $data = [];
        if ($request->has('info')) {
            $data['info'] = trim((string) $request->input('info', ''));
        }
        if ($request->has('detail')) {
            $data['detail'] = $this->normalizeMilestoneDetail($request->input('detail'));
        }
        if (!$data) {
            return ['ok' => false, 'status' => 400, 'message' => 'Không có dữ liệu để cập nhật'];
        }

        DB::connection('game')->table($resolved['table'])->where('id', $id)->update($data);
        $after = DB::connection('game')->table($resolved['table'])->where('id', $id)->first();
        $this->logAdminAction('milestone.update', $resolved['table'], $id, 'Cập nhật mốc quà ' . ($after->info ?? $id), (array) $before, (array) $after);

        return ['ok' => true, 'message' => 'Cập nhật mốc quà thành công'];
    }

    public function delete(string $type, int $id): array
    {
        $resolved = $this->resolveMilestoneType($type);
        if (!$resolved) {
            return ['ok' => false, 'status' => 404, 'message' => 'Loại bảng không hợp lệ'];
        }

        $before = DB::connection('game')->table($resolved['table'])->where('id', $id)->first();
        DB::connection('game')->table($resolved['table'])->where('id', $id)->delete();
        if ($before) {
            $this->logAdminAction('milestone.delete', $resolved['table'], $id, 'Xóa mốc quà ' . ($before->info ?? $id), (array) $before, null);
        }

        return ['ok' => true, 'message' => 'Đã xoá mốc quà'];
    }

    private function resolveMilestoneType(string $type): ?array
    {
        $map = [
            'moc_nap' => ['table' => 'moc_nap', 'label' => 'Mốc nạp'],
            'moc_nap_top' => ['table' => 'moc_nap_top', 'label' => 'Mốc nạp top'],
            'moc_nhiem_vu_top' => ['table' => 'moc_nhiem_vu_top', 'label' => 'Mốc nhiệm vụ top'],
            'moc_suc_manh_top' => ['table' => 'moc_suc_manh_top', 'label' => 'Mốc sức mạnh top'],
        ];

        return isset($map[$type]) ? ['type' => $type, ...$map[$type]] : null;
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

        $decoded = json_decode($this->fixJson($value), true);

        return is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : '[]';
    }
}
