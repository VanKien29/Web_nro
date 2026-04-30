<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackAccessoryService extends AdminServiceSupport
{
    public function __construct(private readonly GameAssetService $assets)
    {
    }

    public function list(Request $request): array
    {
        foreach (['item_template', 'flag_bag'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return ['ok' => false, 'status' => 422, 'message' => "Chưa có bảng {$table}"];
            }
        }

        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        $query = DB::connection('game')->table('flag_bag as f')
            ->leftJoin('item_template as i', function ($join) {
                $join->on('i.part', '=', 'f.id')
                    ->where('i.TYPE', 11);
            })
            ->select([
                DB::raw('f.id as flag_id'),
                DB::raw('f.NAME as flag_name'),
                'f.icon_data',
                DB::raw('f.icon_id as flag_icon_id'),
                'f.gold',
                'f.gem',
                DB::raw('MIN(i.id) as item_id'),
                DB::raw('MIN(i.gender) as item_gender'),
                DB::raw('MIN(i.description) as item_description'),
                DB::raw('MIN(i.icon_id) as item_icon_id'),
            ])
            ->groupBy('f.id', 'f.NAME', 'f.icon_data', 'f.icon_id', 'f.gold', 'f.gem');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('f.id', (int) $search)
                        ->orWhere('f.icon_id', (int) $search)
                        ->orWhere('i.id', (int) $search)
                        ->orWhere('i.icon_id', (int) $search);
                }
                $q->orWhere('f.NAME', 'LIKE', "%{$search}%")
                    ->orWhere('i.NAME', 'LIKE', "%{$search}%")
                    ->orWhere('f.icon_data', 'LIKE', "%{$search}%");
            });
        }

        $total = DB::connection('game')->query()
            ->fromSub(clone $query, 'back_accessory_rows')
            ->count();
        $rows = $query->orderByDesc('f.id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->flag_id,
                'item_id' => $row->item_id === null ? null : (int) $row->item_id,
                'type' => 11,
                'gender' => $row->item_gender === null ? 3 : (int) $row->item_gender,
                'name' => (string) ($row->flag_name ?? ''),
                'description' => (string) ($row->item_description ?? ''),
                'icon_id' => $row->item_icon_id === null ? (int) $row->flag_icon_id : (int) $row->item_icon_id,
                'part' => (int) $row->flag_id,
                'flag_id' => (int) $row->flag_id,
                'flag_name' => (string) ($row->flag_name ?? ''),
                'icon_data' => (string) ($row->icon_data ?? ''),
                'flag_icon_id' => (int) $row->flag_icon_id,
                'gold' => $row->gold === null ? null : (int) $row->gold,
                'gem' => $row->gem === null ? null : (int) $row->gem,
                'icon_url' => $this->assets->gameIconUrl((int) ($row->item_icon_id ?? $row->flag_icon_id)),
            ])
            ->values();

        return [
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'next' => [
                'item_id' => $this->nextGameId('item_template'),
                'flag_id' => $this->nextFlagBagId(),
            ],
        ];
    }

    public function get(int $flagId): array
    {
        foreach (['item_template', 'flag_bag'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return ['ok' => false, 'status' => 422, 'message' => "Chưa có bảng {$table}"];
            }
        }

        $game = DB::connection('game');
        $flag = $game->table('flag_bag')->where('id', $flagId)->first();
        if (!$flag) {
            return ['ok' => false, 'status' => 404, 'message' => 'Đeo lưng không tồn tại'];
        }

        $item = $game->table('item_template')
            ->where('TYPE', 11)
            ->where('part', $flagId)
            ->orderBy('id')
            ->first();
        $iconId = $item ? (int) ($item->icon_id ?? 0) : (int) ($flag->icon_id ?? 0);

        return [
            'ok' => true,
            'data' => [
                'id' => $flagId,
                'item_id' => $item ? (int) $item->id : null,
                'name' => (string) ($flag->NAME ?? ($item->NAME ?? '')),
                'description' => $item ? (string) ($item->description ?? '') : '',
                'gender' => $item ? (int) ($item->gender ?? 3) : 3,
                'icon_id' => $iconId,
                'part' => $flagId,
                'flag_id' => $flagId,
                'flag_name' => (string) ($flag->NAME ?? ''),
                'icon_data' => (string) ($flag->icon_data ?? ''),
                'flag_icon_id' => (int) ($flag->icon_id ?? 0),
                'gold' => (int) ($flag->gold ?? 0),
                'gem' => (int) ($flag->gem ?? -1),
                'icon_url' => $this->assets->gameIconUrl($iconId),
            ],
        ];
    }

    public function create(Request $request): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $requestedItemId = (int) $request->input('item_id', 0);
        $requestedExistingItem = null;
        if ($requestedItemId > 0) {
            $requestedExistingItem = $game->table('item_template')->where('id', $requestedItemId)->first();
            if ($requestedExistingItem && !$this->isReservedItemTemplate($requestedExistingItem)) {
                return ['ok' => false, 'status' => 422, 'message' => "item_template ID {$requestedItemId} đã tồn tại"];
            }
        }

        $requestedFlagId = $request->filled('flag_id') ? (int) $request->input('flag_id') : null;
        if ($requestedFlagId !== null && $game->table('flag_bag')->where('id', $requestedFlagId)->exists()) {
            return ['ok' => false, 'status' => 422, 'message' => "flag_bag ID {$requestedFlagId} đã tồn tại"];
        }

        $saved = [];
        $idMap = [];
        $used = [];
        $spriteIds = [];
        $itemIconId = null;

        try {
            foreach ($this->assets->decodeUploadedImagePayload((string) $request->input('icon_x4_payload', '')) as $payloadFile) {
                $candidate = $this->assets->numericIdFromFilename($payloadFile['name']);
                $id = $this->assets->resolveGameAssetId($candidate, 'icon', $used);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramidFromBytes($payloadFile['bytes'], $payloadFile['name'], 'data/icon', "{$id}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $id;
                }
                $used[$id] = true;
                $spriteIds[] = $id;
            }

            foreach ($this->assets->requestFiles($request, 'icon_x4') as $file) {
                $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
                $id = $this->assets->resolveGameAssetId($candidate, 'icon', $used);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$id}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $id;
                }
                $used[$id] = true;
                $spriteIds[] = $id;
            }

            if (!$spriteIds) {
                throw new \InvalidArgumentException('Cần upload ít nhất một ảnh sprite đeo lưng x4.');
            }

            $itemIconFile = $this->assets->requestFiles($request, 'item_icon_x4')[0] ?? null;
            if ($itemIconFile) {
                $candidate = $this->assets->numericIdFromFilename($itemIconFile->getClientOriginalName());
                if ($candidate !== null && isset($idMap[$candidate])) {
                    $itemIconId = (int) $idMap[$candidate];
                } else {
                    $itemIconId = $this->assets->resolveGameAssetId($candidate, 'icon', $used);
                    $saved = array_merge($saved, $this->assets->saveGamePngPyramid($itemIconFile, 'data/icon', "{$itemIconId}.png", 96));
                    if ($candidate !== null) {
                        $idMap[$candidate] = $itemIconId;
                    }
                    $used[$itemIconId] = true;
                }
            } else {
                $itemIconId = (int) min($spriteIds);
            }
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);
            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu đeo lưng không hợp lệ: ' . $e->getMessage()];
        }

        sort($spriteIds, SORT_NUMERIC);
        $flagId = $requestedFlagId ?? $this->nextFlagBagId();
        $itemId = $requestedItemId > 0 ? $requestedItemId : $this->nextGameId('item_template');
        $name = trim((string) $request->input('name'));
        $iconData = implode(', ', $spriteIds);
        $createdFlagId = null;
        $createdItemId = null;

        DB::beginTransaction();
        $game->beginTransaction();
        try {
            if ($requestedItemId > 0) {
                $this->ensureItemTemplateContinuity($game, $requestedItemId - 1);
            }

            $flagRow = [
                'id' => $flagId,
                'icon_data' => $iconData,
                'NAME' => $name,
                'gold' => (int) $request->input('gold', 0),
                'gem' => (int) $request->input('gem', -1),
                'icon_id' => $itemIconId,
            ];
            $game->table('flag_bag')->insert($flagRow);
            $createdFlagId = $flagId;

            $itemRow = [
                'id' => $itemId,
                'TYPE' => 11,
                'gender' => (int) $request->input('gender', 3),
                'NAME' => $name,
                'description' => (string) $request->input('description', 'Đeo lưng ' . $name),
                'level' => 0,
                'icon_id' => $itemIconId,
                'part' => $flagId,
                'is_up_to_up' => 0,
                'power_require' => 0,
                'gold' => 0,
                'gem' => 0,
                'head' => -1,
                'body' => -1,
                'leg' => -1,
                'is_up_to_up_over_99' => 0,
                'can_trade' => 1,
                'comment' => "Admin back accessory. flag_bag={$flagId};icon={$itemIconId};icons=" . implode(',', $spriteIds),
            ];

            if ($requestedExistingItem && $this->isReservedItemTemplate($requestedExistingItem)) {
                $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
            } else {
                $game->table('item_template')->insert($itemRow);
                $createdItemId = $itemId;
            }

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            if ($game->transactionLevel() > 0) {
                $game->rollBack();
            }
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($createdFlagId !== null) {
                $game->table('flag_bag')->where('id', $createdFlagId)->delete();
            }
            if ($createdItemId !== null) {
                $game->table('item_template')->where('id', $createdItemId)->delete();
            }
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 500, 'message' => 'Không tạo được đeo lưng: ' . $e->getMessage()];
        }

        $this->logAdminAction('back_accessory.create', 'item_template', $itemId, 'Tạo đeo lưng ' . $name, null, [
            'item' => $itemRow,
            'flag_bag' => $flagRow,
            'icon_id_map' => $idMap,
            'saved_files' => $saved,
        ]);

        return [
            'ok' => true,
            'message' => "Đã tạo đeo lưng {$name} (#{$itemId})",
            'data' => [
                'item_id' => $itemId,
                'flag_id' => $flagId,
                'icon_id' => $itemIconId,
                'icon_data' => $iconData,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
            ],
        ];
    }

    public function update(Request $request, int $flagId): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $flag = $game->table('flag_bag')->where('id', $flagId)->first();
        if (!$flag) {
            return ['ok' => false, 'status' => 404, 'message' => "Không tìm thấy flag_bag #{$flagId}"];
        }

        $item = $game->table('item_template')
            ->where('TYPE', 11)
            ->where('part', $flagId)
            ->orderBy('id')
            ->first();

        $saved = [];
        $idMap = [];
        $used = [];
        $newSpriteIds = [];
        $itemIconId = $item ? (int) ($item->icon_id ?? 0) : (int) ($flag->icon_id ?? 0);
        $oldIconIds = array_merge(
            [$itemIconId, (int) ($flag->icon_id ?? 0)],
            $this->assets->parseFlagBagIconData((string) ($flag->icon_data ?? '')),
        );

        try {
            foreach ($this->assets->decodeUploadedImagePayload((string) $request->input('icon_x4_payload', '')) as $payloadFile) {
                $candidate = $this->assets->numericIdFromFilename($payloadFile['name']);
                $newId = $this->assets->resolveGameAssetId($candidate, 'icon', $used);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramidFromBytes($payloadFile['bytes'], $payloadFile['name'], 'data/icon', "{$newId}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $newId;
                }
                $used[$newId] = true;
                $newSpriteIds[] = $newId;
            }

            foreach ($this->assets->requestFiles($request, 'icon_x4') as $file) {
                $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
                $newId = $this->assets->resolveGameAssetId($candidate, 'icon', $used);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$newId}.png"));
                if ($candidate !== null) {
                    $idMap[$candidate] = $newId;
                }
                $used[$newId] = true;
                $newSpriteIds[] = $newId;
            }

            $itemIconFile = $this->assets->requestFiles($request, 'item_icon_x4')[0] ?? null;
            if ($itemIconFile) {
                $candidate = $this->assets->numericIdFromFilename($itemIconFile->getClientOriginalName());
                if ($candidate !== null && isset($idMap[$candidate])) {
                    $itemIconId = (int) $idMap[$candidate];
                } else {
                    $itemIconId = $this->assets->resolveGameAssetId($candidate, 'icon', $used);
                    $saved = array_merge($saved, $this->assets->saveGamePngPyramid($itemIconFile, 'data/icon', "{$itemIconId}.png", 96));
                    if ($candidate !== null) {
                        $idMap[$candidate] = $itemIconId;
                    }
                    $used[$itemIconId] = true;
                }
            }
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);
            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu đeo lưng không hợp lệ: ' . $e->getMessage()];
        }

        sort($newSpriteIds, SORT_NUMERIC);
        $iconData = $newSpriteIds ? implode(', ', $newSpriteIds) : (string) ($flag->icon_data ?? '');
        $name = trim((string) $request->input('name'));
        $requestedItemId = (int) $request->input('item_id', 0);
        $requestedExistingItem = null;
        if (!$item && $requestedItemId > 0) {
            $requestedExistingItem = $game->table('item_template')->where('id', $requestedItemId)->first();
            if ($requestedExistingItem && !$this->isReservedItemTemplate($requestedExistingItem)) {
                return ['ok' => false, 'status' => 422, 'message' => "item_template ID {$requestedItemId} đã tồn tại"];
            }
        }

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $flagRow = [
                'icon_data' => $iconData,
                'NAME' => $name,
                'gold' => (int) $request->input('gold', 0),
                'gem' => (int) $request->input('gem', -1),
                'icon_id' => $itemIconId,
            ];
            $game->table('flag_bag')->where('id', $flagId)->update($flagRow);

            $itemRow = null;
            if ($item || $requestedItemId > 0) {
                $itemId = $item ? (int) $item->id : $requestedItemId;
                if (!$item) {
                    $this->ensureItemTemplateContinuity($game, $itemId - 1);
                }
                $itemRow = [
                    'TYPE' => 11,
                    'gender' => (int) $request->input('gender', 3),
                    'NAME' => $name,
                    'description' => (string) $request->input('description', 'Đeo lưng ' . $name),
                    'icon_id' => $itemIconId,
                    'part' => $flagId,
                    'head' => -1,
                    'body' => -1,
                    'leg' => -1,
                    'comment' => "Admin back accessory. flag_bag={$flagId};icon={$itemIconId};icons=" . str_replace(' ', '', $iconData),
                ];
                if ($item) {
                    $game->table('item_template')->where('id', $itemId)->update($itemRow);
                } elseif ($requestedExistingItem && $this->isReservedItemTemplate($requestedExistingItem)) {
                    $game->table('item_template')->where('id', $itemId)->update($itemRow);
                } else {
                    $game->table('item_template')->insert(['id' => $itemId, ...$itemRow]);
                }
            }

            $safeIconIds = $this->assets->filterUnreferencedIconIds($game, $oldIconIds);
            $deletedIconFiles = $this->assets->deleteGameIconFiles($safeIconIds);

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            if ($game->transactionLevel() > 0) {
                $game->rollBack();
            }
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 500, 'message' => 'Không sửa được đeo lưng: ' . $e->getMessage()];
        }

        $this->logAdminAction(
            'back_accessory.update',
            'item_template',
            $item ? (int) $item->id : $flagId,
            'Sửa đeo lưng ' . $name,
            ['item' => $item ? $this->sanitizeLogState((array) $item) : null, 'flag_bag' => $this->sanitizeLogState((array) $flag)],
            [
                'item' => $itemRow,
                'flag_bag' => $flagRow,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        );

        return [
            'ok' => true,
            'message' => "Đã sửa đeo lưng {$name} (#{$flagId})",
            'data' => [
                'item_id' => $item ? (int) $item->id : ($requestedItemId > 0 ? $requestedItemId : null),
                'flag_id' => $flagId,
                'icon_id' => $itemIconId,
                'icon_data' => $iconData,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    public function delete(int $flagId): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $flag = $game->table('flag_bag')->where('id', $flagId)->first();
        if (!$flag) {
            return ['ok' => false, 'status' => 404, 'message' => 'Đeo lưng không tồn tại'];
        }

        $items = $game->table('item_template')
            ->where('TYPE', 11)
            ->where('part', $flagId)
            ->get();

        $iconIdsToDelete = [(int) ($flag->icon_id ?? 0)];
        foreach ($items as $item) {
            $iconIdsToDelete[] = (int) ($item->icon_id ?? 0);
        }
        $iconIdsToDelete = array_merge($iconIdsToDelete, $this->assets->parseFlagBagIconData((string) ($flag->icon_data ?? '')));

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        $skippedIconIds = [];
        try {
            $maxItemIdBeforeDelete = (int) ($game->table('item_template')->max('id') ?? 0);
            $deletedItemIds = $items->pluck('id')->map(fn($value) => (int) $value)->all();
            if ($deletedItemIds) {
                $game->table('item_template')->whereIn('id', $deletedItemIds)->delete();
            }
            $game->table('flag_bag')->where('id', $flagId)->delete();
            $filledItemIds = $this->ensureItemTemplateContinuity($game, max(array_merge([$maxItemIdBeforeDelete, 0], $deletedItemIds)));
            $safeIconIds = $this->assets->filterUnreferencedIconIds($game, $iconIdsToDelete);
            $skippedIconIds = array_values(array_diff(
                array_values(array_unique(array_filter(array_map('intval', $iconIdsToDelete), fn($iconId) => $iconId > 0))),
                $safeIconIds,
            ));
            $deletedIconFiles = $this->assets->deleteGameIconFiles($safeIconIds);

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            if ($game->transactionLevel() > 0) {
                $game->rollBack();
            }
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return ['ok' => false, 'status' => 500, 'message' => 'Không xóa được đeo lưng: ' . $e->getMessage()];
        }

        $this->logAdminAction(
            'back_accessory.delete',
            'item_template',
            $flagId,
            'Xóa đeo lưng ' . ($flag->NAME ?? ('#' . $flagId)),
            ['items' => $items->map(fn($item) => $this->sanitizeLogState((array) $item))->all(), 'flag_bag' => $this->sanitizeLogState((array) $flag)],
            [
                'flag_id' => $flagId,
                'filled_item_template_ids' => $filledItemIds,
                'skipped_icon_ids' => $skippedIconIds,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        );

        return [
            'ok' => true,
            'message' => 'Đã xóa đeo lưng, flag_bag và ảnh không còn dùng',
            'data' => [
                'deleted_item_ids' => $deletedItemIds,
                'deleted_flag_id' => $flagId,
                'filled_item_template_ids' => $filledItemIds,
                'skipped_icon_ids' => $skippedIconIds,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    private function missingTableMessage(): ?string
    {
        foreach (['item_template', 'flag_bag'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return "Chưa có bảng {$table}";
            }
        }

        return null;
    }

    protected function nextGameId(string $table): int
    {
        return (int) (DB::connection('game')->table($table)->max('id') ?? -1) + 1;
    }

    private function nextFlagBagId(): int
    {
        $existing = DB::connection('game')->table('flag_bag')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
        $set = array_flip($existing);
        $max = $existing ? max($existing) : -1;

        for ($id = max(0, $max + 1); $id <= 255; $id++) {
            if (!isset($set[$id])) {
                return $id;
            }
        }
        for ($id = 0; $id <= 255; $id++) {
            if (!isset($set[$id])) {
                return $id;
            }
        }

        throw new \RuntimeException('Không còn flag_bag ID trống hợp lệ (0-255).');
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

}
