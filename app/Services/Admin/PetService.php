<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetService extends AdminServiceSupport
{
    public function __construct(private readonly GameAssetService $assets)
    {
    }

    public function list(Request $request): array
    {
        foreach (['item_template', 'part'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return ['ok' => false, 'status' => 422, 'message' => "Chưa có bảng {$table}"];
            }
        }

        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        $query = DB::connection('game')->table('item_template as i')
            ->select([
                'i.id',
                DB::raw('i.TYPE as type'),
                'i.gender',
                DB::raw('i.NAME as name'),
                'i.description',
                'i.icon_id',
                'i.part',
                'i.head',
                'i.body',
                'i.leg',
            ])
            ->where('i.TYPE', 21);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('i.id', (int) $search)
                        ->orWhere('i.icon_id', (int) $search)
                        ->orWhere('i.head', (int) $search)
                        ->orWhere('i.body', (int) $search)
                        ->orWhere('i.leg', (int) $search);
                }
                $q->orWhere('i.NAME', 'LIKE', "%{$search}%")
                    ->orWhere('i.description', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('i.id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'type' => (int) $row->type,
                'gender' => (int) $row->gender,
                'name' => (string) $row->name,
                'description' => (string) $row->description,
                'icon_id' => (int) $row->icon_id,
                'part' => (int) $row->part,
                'head' => (int) $row->head,
                'body' => (int) $row->body,
                'leg' => (int) $row->leg,
                'icon_url' => $this->assets->gameIconUrl((int) $row->icon_id),
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
                'part_id' => $this->nextGameId('part'),
            ],
        ];
    }

    public function get(int $id): array
    {
        foreach (['item_template', 'part'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return ['ok' => false, 'status' => 422, 'message' => "Chưa có bảng {$table}"];
            }
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->first();
        if (!$item || (int) ($item->TYPE ?? -1) !== 21) {
            return ['ok' => false, 'status' => 404, 'message' => 'Pet không tồn tại'];
        }

        $partData = [];
        foreach (['head', 'body', 'leg'] as $field) {
            $partId = (int) ($item->{$field} ?? -1);
            $part = $partId >= 0 ? $game->table('part')->where('id', $partId)->first(['DATA']) : null;
            $partData[$field . '_data'] = $part ? (string) $part->DATA : '';
        }

        $extraBlocks = [];
        foreach ($this->extraHeadIdsFromComment((string) ($item->comment ?? '')) as $extraHeadId) {
            $part = $game->table('part')->where('id', $extraHeadId)->first(['DATA']);
            if ($part) {
                $extraBlocks[] = (string) $part->DATA;
            }
        }

        return [
            'ok' => true,
            'data' => [
                'id' => (int) $item->id,
                'name' => (string) ($item->NAME ?? ''),
                'description' => (string) ($item->description ?? ''),
                'gender' => (int) ($item->gender ?? 3),
                'icon_id' => (int) ($item->icon_id ?? 0),
                'head' => (int) ($item->head ?? -1),
                'body' => (int) ($item->body ?? -1),
                'leg' => (int) ($item->leg ?? -1),
                'extra_heads' => $this->extraHeadIdsFromComment((string) ($item->comment ?? '')),
                'icon_url' => $this->assets->gameIconUrl((int) ($item->icon_id ?? 0)),
                ...$partData,
                'extra_head_data' => implode("\n\n", $extraBlocks),
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

        $saved = [];
        $createdParts = [];

        try {
            [
                'saved' => $saved,
                'id_map' => $idMap,
                'used_icon_ids' => $iconIds,
                'sprite_ids' => $spriteUploadedIds,
            ] = $this->storePetIconUploads($request);

            $itemIconId = $this->storePetItemIcon($request, $idMap, $iconIds, $saved);
            if ($itemIconId === null) {
                $itemIconId = (int) array_key_first($iconIds);
            }
            if ($itemIconId <= 0) {
                throw new \InvalidArgumentException('Cần upload ít nhất một icon/sprite x4.');
            }

            $headRawLayers = $this->assets->parsePartLayers((string) $request->input('head_data'));
            $bodyRawLayers = $this->assets->parsePartLayers((string) $request->input('body_data'));
            $legRawLayers = $this->assets->parsePartLayers((string) $request->input('leg_data'));
            $extraRawHeads = $this->assets->parseExtraPartLayerBlocks((string) $request->input('extra_head_data', ''));
            $allLayers = array_merge($headRawLayers, $bodyRawLayers, $legRawLayers, ...$extraRawHeads);
            $idMap = $this->assets->completePetSpriteIdMap($idMap, $spriteUploadedIds, $allLayers);

            $headLayers = $this->assets->rewritePetPartLayers($headRawLayers, $idMap);
            $bodyLayers = $this->assets->rewritePetPartLayers($bodyRawLayers, $idMap);
            $legLayers = $this->assets->rewritePetPartLayers($legRawLayers, $idMap);
            $extraHeads = array_map(fn($layers) => $this->assets->rewritePetPartLayers($layers, $idMap), $extraRawHeads);
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu pet không hợp lệ: ' . $e->getMessage()];
        }

        DB::beginTransaction();
        $game->beginTransaction();
        try {
            if ($requestedItemId > 0) {
                $this->ensureItemTemplateContinuity($game, $requestedItemId - 1);
            }

            $nextPartId = $this->nextGameId('part');
            $headId = $nextPartId;
            $extraHeadIds = array_map(fn($offset) => $nextPartId + 1 + $offset, array_keys($extraHeads));
            $bodyId = $nextPartId + 1 + count($extraHeads);
            $legId = $bodyId + 1;

            $partRows = [
                ['id' => $headId, 'TYPE' => 0, 'DATA' => json_encode($headLayers, JSON_UNESCAPED_UNICODE)],
            ];
            foreach ($extraHeads as $offset => $layers) {
                $partRows[] = ['id' => $extraHeadIds[$offset], 'TYPE' => 0, 'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE)];
            }
            $partRows[] = ['id' => $bodyId, 'TYPE' => 1, 'DATA' => json_encode($bodyLayers, JSON_UNESCAPED_UNICODE)];
            $partRows[] = ['id' => $legId, 'TYPE' => 2, 'DATA' => json_encode($legLayers, JSON_UNESCAPED_UNICODE)];

            foreach ($partRows as $row) {
                $game->table('part')->insert($row);
                $createdParts[] = $row['id'];
            }
            $this->syncHeadFrames($game, array_merge([$headId], $extraHeadIds));

            $itemId = $requestedItemId > 0 ? $requestedItemId : $this->nextGameId('item_template');
            $name = trim((string) $request->input('name'));
            $itemRow = [
                'id' => $itemId,
                'TYPE' => 21,
                'gender' => (int) $request->input('gender', 3),
                'NAME' => $name,
                'description' => (string) $request->input('description', 'Pet ' . $name),
                'level' => 0,
                'icon_id' => $itemIconId,
                'part' => 0,
                'is_up_to_up' => 0,
                'power_require' => 0,
                'gold' => 0,
                'gem' => 0,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'is_up_to_up_over_99' => 0,
                'can_trade' => 1,
                'comment' => "Admin pet item. head={$headId};body={$bodyId};leg={$legId};extra_heads=" . implode(',', $extraHeadIds) . ";icon={$itemIconId}",
            ];

            if ($requestedExistingItem && $this->isReservedItemTemplate($requestedExistingItem)) {
                $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
            } else {
                $game->table('item_template')->insert($itemRow);
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
            if ($createdParts) {
                $game->table('part')->whereIn('id', $createdParts)->delete();
            }
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 500, 'message' => 'Không tạo được pet: ' . $e->getMessage()];
        }

        $this->logAdminAction('pet.create', 'item_template', $itemId, 'Tạo pet ' . $name, null, [
            'item' => $itemRow,
            'part_ids' => $createdParts,
            'item_icon_id' => $itemIconId,
            'icon_id_map' => $idMap,
            'saved_files' => $saved,
        ]);

        return [
            'ok' => true,
            'message' => "Đã tạo pet {$name} (#{$itemId})",
            'data' => [
                'item_id' => $itemId,
                'item_icon_id' => $itemIconId,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'extra_heads' => $extraHeadIds,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
            ],
        ];
    }

    public function update(Request $request, int $id): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->first();
        if (!$item || (int) ($item->TYPE ?? -1) !== 21) {
            return ['ok' => false, 'status' => 404, 'message' => 'Pet không tồn tại'];
        }

        $saved = [];
        $oldPartIds = $this->partIdsFromPetItem($item);
        $oldIconIds = [(int) ($item->icon_id ?? 0)];
        if ($oldPartIds) {
            foreach ($game->table('part')->whereIn('id', $oldPartIds)->get(['DATA']) as $partRow) {
                foreach ($this->assets->decodePartData($partRow->DATA ?? '') as $layer) {
                    $oldIconIds[] = (int) ($layer['icon_id'] ?? 0);
                }
            }
        }

        try {
            [
                'saved' => $saved,
                'id_map' => $idMap,
                'used_icon_ids' => $iconIds,
                'sprite_ids' => $spriteUploadedIds,
            ] = $this->storePetIconUploads($request);

            $itemIconId = $this->storePetItemIcon($request, $idMap, $iconIds, $saved) ?? (int) ($item->icon_id ?? 0);

            $headRawLayers = $this->assets->parsePartLayers((string) $request->input('head_data'));
            $bodyRawLayers = $this->assets->parsePartLayers((string) $request->input('body_data'));
            $legRawLayers = $this->assets->parsePartLayers((string) $request->input('leg_data'));
            $extraRawHeads = $this->assets->parseExtraPartLayerBlocks((string) $request->input('extra_head_data', ''));
            $allLayers = array_merge($headRawLayers, $bodyRawLayers, $legRawLayers, ...$extraRawHeads);
            $idMap = $this->assets->completePetSpriteIdMap($idMap, $spriteUploadedIds, $allLayers);

            $headLayers = $this->assets->rewritePetPartLayers($headRawLayers, $idMap);
            $bodyLayers = $this->assets->rewritePetPartLayers($bodyRawLayers, $idMap);
            $legLayers = $this->assets->rewritePetPartLayers($legRawLayers, $idMap);
            $extraHeads = array_map(fn($layers) => $this->assets->rewritePetPartLayers($layers, $idMap), $extraRawHeads);
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu pet không hợp lệ: ' . $e->getMessage()];
        }

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $headId = $this->validExistingPartId((int) ($item->head ?? -1)) ? (int) $item->head : $this->nextFreePartId($game);
            $bodyId = $this->validExistingPartId((int) ($item->body ?? -1)) ? (int) $item->body : $this->nextFreePartId($game, [$headId]);
            $legId = $this->validExistingPartId((int) ($item->leg ?? -1)) ? (int) $item->leg : $this->nextFreePartId($game, [$headId, $bodyId]);

            foreach ([[$headId, 0, $headLayers], [$bodyId, 1, $bodyLayers], [$legId, 2, $legLayers]] as [$partId, $type, $layers]) {
                $game->table('part')->updateOrInsert(['id' => $partId], ['TYPE' => $type, 'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE)]);
            }

            $existingExtraIds = $this->extraHeadIdsFromComment((string) ($item->comment ?? ''));
            $extraIds = [];
            foreach ($extraHeads as $offset => $layers) {
                $extraId = $existingExtraIds[$offset] ?? $this->nextFreePartId($game, array_merge([$headId, $bodyId, $legId], $extraIds));
                while (
                    in_array($extraId, [$headId, $bodyId, $legId], true)
                    || (!in_array($extraId, $existingExtraIds, true) && $game->table('part')->where('id', $extraId)->exists())
                ) {
                    $extraId++;
                }
                $game->table('part')->updateOrInsert(['id' => $extraId], ['TYPE' => 0, 'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE)]);
                $extraIds[] = $extraId;
            }

            $removedExtraIds = array_values(array_diff($existingExtraIds, $extraIds));
            if ($removedExtraIds) {
                $game->table('part')->whereIn('id', $removedExtraIds)->delete();
                if (Schema::connection('game')->hasTable('array_head_2_frames')) {
                    $game->table('array_head_2_frames')->whereIn('id', $removedExtraIds)->delete();
                }
            }
            $this->syncHeadFrames($game, array_merge([$headId], $extraIds));

            $name = trim((string) $request->input('name'));
            $itemRow = [
                'TYPE' => 21,
                'gender' => (int) $request->input('gender', 3),
                'NAME' => $name,
                'description' => (string) $request->input('description', 'Pet ' . $name),
                'icon_id' => $itemIconId,
                'part' => 0,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'comment' => "Admin pet item. head={$headId};body={$bodyId};leg={$legId};extra_heads=" . implode(',', $extraIds) . ";icon={$itemIconId}",
            ];
            $game->table('item_template')->where('id', $id)->update($itemRow);

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

            return ['ok' => false, 'status' => 500, 'message' => 'Không sửa được pet: ' . $e->getMessage()];
        }

        $this->logAdminAction('pet.update', 'item_template', $id, 'Sửa pet ' . $name, $this->sanitizeLogState((array) $item), [
            'item' => $itemRow,
            'extra_heads' => $extraIds,
            'item_icon_id' => $itemIconId,
            'icon_id_map' => $idMap,
            'saved_files' => $saved,
            'deleted_icon_files' => $deletedIconFiles,
        ]);

        return [
            'ok' => true,
            'message' => "Đã sửa pet {$name} (#{$id})",
            'data' => [
                'item_id' => $id,
                'item_icon_id' => $itemIconId,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'extra_heads' => $extraIds,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    public function delete(int $id): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->first();
        if (!$item || (int) ($item->TYPE ?? -1) !== 21) {
            return ['ok' => false, 'status' => 404, 'message' => 'Pet không tồn tại'];
        }

        $partIds = $this->partIdsFromPetItem($item);
        $iconIdsToDelete = [(int) ($item->icon_id ?? 0)];
        if ($partIds) {
            foreach ($game->table('part')->whereIn('id', $partIds)->get(['DATA']) as $partRow) {
                foreach ($this->assets->decodePartData($partRow->DATA ?? '') as $layer) {
                    $iconIdsToDelete[] = (int) ($layer['icon_id'] ?? 0);
                }
            }
        }

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $maxItemIdBeforeDelete = (int) ($game->table('item_template')->max('id') ?? 0);
            if ($partIds) {
                if (Schema::connection('game')->hasTable('array_head_2_frames')) {
                    $game->table('array_head_2_frames')->whereIn('id', $partIds)->delete();
                }
                $game->table('part')->whereIn('id', $partIds)->delete();
            }
            $game->table('item_template')->where('id', $id)->delete();

            $filledItemIds = $this->ensureItemTemplateContinuity($game, max($maxItemIdBeforeDelete, $id));
            $moves = $this->compactPartIds($game, $partIds);
            $safeIconIds = $this->assets->filterUnreferencedIconIds($game, $iconIdsToDelete);
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

            return ['ok' => false, 'status' => 500, 'message' => 'Không xóa được pet: ' . $e->getMessage()];
        }

        $this->logAdminAction('pet.delete', 'item_template', $id, 'Xóa pet ' . ($item->NAME ?? ('#' . $id)), $this->sanitizeLogState((array) $item), [
            'deleted_part_ids' => $partIds,
            'filled_item_template_ids' => $filledItemIds,
            'compacted_part_moves' => $moves,
            'deleted_icon_files' => $deletedIconFiles,
        ]);

        return [
            'ok' => true,
            'message' => 'Đã xóa pet và dữ liệu part liên quan',
            'data' => [
                'deleted_item_id' => $id,
                'deleted_part_ids' => $partIds,
                'filled_item_template_ids' => $filledItemIds,
                'compacted_part_moves' => $moves,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    private function missingTableMessage(): ?string
    {
        foreach (['item_template', 'part'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return "Chưa có bảng {$table}";
            }
        }

        return null;
    }

    private function storePetIconUploads(Request $request): array
    {
        $saved = [];
        $idMap = [];
        $iconIds = [];
        $spriteUploadedIds = [];
        $temporarySourceIconIds = $this->assets->temporaryPartSourceIconIds($request);

        foreach ($this->assets->decodeUploadedImagePayload((string) $request->input('icon_x4_payload', '')) as $payloadFile) {
            $candidate = $this->assets->numericIdFromFilename($payloadFile['name']);
            $id = $this->assets->resolveGameAssetId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, 'icon', $iconIds);
            $saved = array_merge($saved, $this->assets->saveGamePngPyramidFromBytes($payloadFile['bytes'], $payloadFile['name'], 'data/icon', "{$id}.png"));
            if ($candidate !== null) {
                $idMap[$candidate] = $id;
            }
            $iconIds[$id] = true;
            $spriteUploadedIds[] = $id;
        }

        foreach ($this->assets->requestFiles($request, 'icon_x4') as $file) {
            $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
            $id = $this->assets->resolveGameAssetId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, 'icon', $iconIds);
            $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$id}.png"));
            if ($candidate !== null) {
                $idMap[$candidate] = $id;
            }
            $iconIds[$id] = true;
            $spriteUploadedIds[] = $id;
        }

        return [
            'saved' => $saved,
            'id_map' => $idMap,
            'used_icon_ids' => $iconIds,
            'sprite_ids' => $spriteUploadedIds,
        ];
    }

    private function storePetItemIcon(Request $request, array &$idMap, array &$iconIds, array &$saved): ?int
    {
        $itemIconFile = $this->assets->requestFiles($request, 'item_icon_x4')[0] ?? null;
        if (!$itemIconFile) {
            return null;
        }

        $candidate = $this->assets->numericIdFromFilename($itemIconFile->getClientOriginalName());
        if ($candidate !== null && isset($idMap[$candidate])) {
            return (int) $idMap[$candidate];
        }

        $itemIconId = $this->assets->resolveGameAssetId($candidate, 'icon', $iconIds);
        $saved = array_merge($saved, $this->assets->saveGamePngPyramid($itemIconFile, 'data/icon', "{$itemIconId}.png", 96));
        if ($candidate !== null) {
            $idMap[$candidate] = $itemIconId;
        }
        $iconIds[$itemIconId] = true;

        return $itemIconId;
    }

    protected function nextGameId(string $table): int
    {
        return (int) (DB::connection('game')->table($table)->max('id') ?? -1) + 1;
    }

    private function nextFreePartId($game, array $reserved = []): int
    {
        $reserved = array_flip(array_filter(array_map('intval', $reserved), fn($id) => $id >= 0));
        $id = $this->nextGameId('part');
        while (isset($reserved[$id]) || $game->table('part')->where('id', $id)->exists()) {
            $id++;
        }

        return $id;
    }

    private function validExistingPartId(int $partId): bool
    {
        return $partId >= 0;
    }

    private function partIdsFromPetItem(object $item): array
    {
        return array_values(array_filter([
            (int) ($item->head ?? -1),
            (int) ($item->body ?? -1),
            (int) ($item->leg ?? -1),
            ...$this->extraHeadIdsFromComment((string) ($item->comment ?? '')),
        ], fn($partId) => $partId >= 0));
    }

    private function extraHeadIdsFromComment(string $comment): array
    {
        if (!preg_match('/extra_heads=([0-9,]+)/', $comment, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn($value) => (int) $value,
            explode(',', $matches[1]),
        ), fn($id) => $id > 0));
    }

    private function syncHeadFrames($game, array $headIds): void
    {
        if (!Schema::connection('game')->hasTable('array_head_2_frames')) {
            return;
        }

        $headIds = array_values(array_unique(array_filter(array_map('intval', $headIds), fn($id) => $id > 0)));
        if (!$headIds) {
            return;
        }

        $mainHeadId = $headIds[0];
        $game->table('array_head_2_frames')
            ->whereIn('id', $headIds)
            ->where('id', '<>', $mainHeadId)
            ->delete();

        if (count($headIds) < 2) {
            $game->table('array_head_2_frames')->where('id', $mainHeadId)->delete();
            return;
        }

        $game->table('array_head_2_frames')->updateOrInsert(
            ['id' => $mainHeadId],
            ['data' => json_encode($headIds, JSON_UNESCAPED_UNICODE)],
        );
    }

    private function compactPartIds($game, array $deletedIds): array
    {
        $holes = array_values(array_unique(array_filter(array_map('intval', $deletedIds), fn($id) => $id > 0)));
        sort($holes);
        if (!$holes) {
            return [];
        }

        $moves = [];
        foreach ($holes as $hole) {
            if ($game->table('part')->where('id', $hole)->exists()) {
                continue;
            }

            $source = $game->table('part')
                ->where('id', '>', $hole)
                ->orderByDesc('id')
                ->first();
            if (!$source || (int) $source->id <= $hole) {
                continue;
            }

            $sourceId = (int) $source->id;
            $game->table('part')->where('id', $sourceId)->update(['id' => $hole]);

            foreach (['part', 'head', 'body', 'leg'] as $column) {
                if (Schema::connection('game')->hasColumn('item_template', $column)) {
                    $game->table('item_template')->where($column, $sourceId)->update([$column => $hole]);
                }
            }

            if (Schema::connection('game')->hasTable('head_avatar')) {
                $game->table('head_avatar')->where('head_id', $sourceId)->update(['head_id' => $hole]);
            }

            if (Schema::connection('game')->hasTable('array_head_2_frames')) {
                $game->table('array_head_2_frames')->where('id', $sourceId)->update(['id' => $hole]);
                foreach ($game->table('array_head_2_frames')->get(['id', 'data']) as $row) {
                    $heads = $this->decodeHeadFrameIds((string) ($row->data ?? ''));
                    if (!in_array($sourceId, $heads, true)) {
                        continue;
                    }
                    $heads = array_map(fn($headId) => $headId === $sourceId ? $hole : $headId, $heads);
                    $game->table('array_head_2_frames')
                        ->where('id', (int) $row->id)
                        ->update(['data' => json_encode($heads, JSON_UNESCAPED_UNICODE)]);
                }
            }

            $moves[] = ['from' => $sourceId, 'to' => $hole];
        }

        return $moves;
    }

    private function decodeHeadFrameIds(string $raw): array
    {
        $decoded = json_decode($this->fixJson(trim($raw)), true);
        if (is_string($decoded)) {
            $decoded = json_decode($this->fixJson($decoded), true);
        }
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $decoded), fn($id) => $id > 0));
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


    protected function fixJson(?string $value): string
    {
        if (!$value) {
            return '[]';
        }

        $normalized = trim($value);
        $normalized = preg_replace('/,\s*([\]\}])/', '$1', $normalized);
        $normalized = preg_replace('/([\[\{])\s*,/', '$1', $normalized);
        $normalized = preg_replace('/,\s*,/', ',', $normalized);

        return $normalized;
    }
}
