<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BadgeService extends AdminServiceSupport
{
    public function list(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        if (!Schema::connection('game')->hasTable('data_badges')) {
            return ['ok' => true, 'data' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
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

        return [
            'ok' => true,
            'data' => $this->attachBadgeAssets($rows),
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'capabilities' => $this->badgeCapabilities(),
        ];
    }

    public function get(int $id): array
    {
        $row = $this->badgeBaseQuery()->where('b.id', $id)->first();
        if (!$row) {
            return ['ok' => false, 'status' => 404, 'message' => 'Badge không tồn tại'];
        }

        return [
            'ok' => true,
            'data' => $this->attachBadgeAssets(collect([$this->normalizeBadgeTemplate($row)]))->first(),
            'capabilities' => $this->badgeCapabilities(),
        ];
    }

    public function create(Request $request): array
    {
        if (!Schema::connection('game')->hasTable('data_badges')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng data_badges'];
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
        $this->logAdminAction('badge.create', 'badge', $id, "Tạo badge {$payload['NAME']}", null, $this->sanitizeLogState((array) $row));

        return ['ok' => true, 'message' => 'Đã tạo badge', 'data' => $normalized];
    }

    public function update(Request $request, int $id): array
    {
        if (!Schema::connection('game')->hasTable('data_badges')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng data_badges'];
        }

        $before = $this->badgeBaseQuery()->where('b.id', $id)->first();
        if (!$before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Badge không tồn tại'];
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
        $this->logAdminAction('badge.update', 'badge', $id, "Cập nhật badge {$payload['NAME']}", $this->sanitizeLogState((array) $before), $this->sanitizeLogState((array) $after));

        return ['ok' => true, 'message' => 'Đã cập nhật badge', 'data' => $normalized];
    }

    public function delete(int $id): array
    {
        if (!Schema::connection('game')->hasTable('data_badges')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng data_badges'];
        }

        $before = $this->badgeBaseQuery()->where('b.id', $id)->first();
        if (!$before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Badge không tồn tại'];
        }

        DB::connection('game')->table('data_badges')->where('id', $id)->delete();
        if (Schema::hasTable('admin_badge_assets')) {
            DB::table('admin_badge_assets')->where('badge_id', $id)->delete();
        }
        $this->logAdminAction('badge.delete', 'badge', $id, "Xóa badge {$before->name}", $this->sanitizeLogState((array) $before), null);

        return ['ok' => true, 'message' => 'Đã xóa badge'];
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
        $select[] = $imageColumn ? DB::raw("b.`{$imageColumn}` as image_url") : DB::raw("'' as image_url");
        $select[] = Schema::connection('game')->hasColumn('data_badges', 'data')
            ? DB::raw('b.`data` as data_raw')
            : DB::raw("'' as data_raw");

        return DB::connection('game')->table('data_badges as b')
            ->leftJoin('item_template as i', 'b.idItem', '=', 'i.id')
            ->select($select);
    }

    private function normalizeBadgeTemplate($row): array
    {
        $raw = (string) ($row->options ?? '[]');
        $decoded = json_decode($this->fixJson($raw), true);
        $options = is_array($decoded) ? $decoded : [];

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
                    'id' => (int) (($option['id'] ?? 0)),
                    'param' => (int) (($option['param'] ?? 0)),
                ];
            })->values()->all(),
        ];
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
                'id' => (int) (($option['id'] ?? 0)),
                'param' => (int) (($option['param'] ?? 0)),
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
            $payload[$imageColumn] = $this->storePublicBadgeImage($request->file('image'));
        } elseif ($imageColumn && $request->filled('image_url')) {
            $payload[$imageColumn] = (string) $request->input('image_url');
        }

        return $payload;
    }

    private function createBadgeItemTemplateIfRequested(Request $request): ?array
    {
        if (!$request->boolean('create_item_template') || !Schema::connection('game')->hasTable('item_template')) {
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
            $game->table('part')->insert([
                'id' => $partId,
                'TYPE' => $partType,
                'DATA' => json_encode([
                    [$iconId, (int) $request->input('part_dx', 0), (int) $request->input('part_dy', 0)],
                    [$baseIcon, 0, 0],
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        $itemName = trim((string) $request->input('item_name', '')) ?: trim((string) $request->input('name', 'Danh hiệu'));
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

        return ['item_id' => $itemId, 'icon_id' => $iconId, 'part_id' => $partId];
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
            $data['image_url'] = $this->storePublicBadgeImage($request->file('image'));
            $hasAssetChange = true;
        } elseif ($request->filled('image_url')) {
            $data['image_url'] = (string) $request->input('image_url');
            $hasAssetChange = true;
        }
        if (!$hasAssetChange) {
            return;
        }

        DB::table('admin_badge_assets')->updateOrInsert(['badge_id' => $badgeId], array_merge($data, ['created_at' => now()]));
    }

    private function storePublicBadgeImage($file): string
    {
        $dir = public_path('uploads/badges');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = 'badge_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return '/uploads/badges/' . $name;
    }
}
