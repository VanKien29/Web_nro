<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TitleItemService extends AdminServiceSupport
{
    public function __construct(private readonly GameAssetService $assets)
    {
    }

    public function list(Request $request): array
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template'];
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
            ])
            ->where(function ($q) {
                $q->whereIn('i.TYPE', [36, 99])
                    ->orWhere('i.description', 'LIKE', '%Danh hiệu%')
                    ->orWhere('i.comment', 'LIKE', '%Admin title item%');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('i.id', (int) $search)
                        ->orWhere('i.icon_id', (int) $search)
                        ->orWhere('i.part', (int) $search);
                }
                $q->orWhere('i.NAME', 'LIKE', "%{$search}%");
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
                'id_effect' => (int) $row->part >= 0 ? (int) $row->part : null,
                'has_icon_x4' => is_file($this->assets->gameSrcPath('data/icon/x4/' . (int) $row->icon_id . '.png')),
                'icon_url' => $this->assets->gameIconUrl((int) $row->icon_id),
                'has_effect_data' => (int) $row->part >= 0 && is_file($this->assets->gameSrcPath('data/effdata/DataEffect_' . (int) $row->part)),
            ])
            ->values();

        return [
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'paths' => $this->assets->titleAssetPaths(),
        ];
    }

    public function iconPath(int $iconId): ?string
    {
        $path = $this->assets->gameSrcPath("data/icon/x4/{$iconId}.png");
        return is_file($path) ? $path : null;
    }

    public function create(Request $request): array
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template'];
        }

        $game = DB::connection('game');
        $assetIds = [];
        $iconIds = [];
        $effectIds = [];
        $saved = [];
        $created = [];

        try {
            foreach ($this->assets->requestFiles($request, 'icon_x4') as $file) {
                $id = $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($file->getClientOriginalName()), 'icon', $iconIds);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$id}.png", 96));
                $assetIds[$id] = true;
                $iconIds[$id] = true;
            }

            foreach ($this->assets->requestFiles($request, 'effect_x4') as $file) {
                $id = $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($file->getClientOriginalName()), 'effect', $effectIds);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/effect', "ImgEffect_{$id}.png"));
                $assetIds[$id] = true;
                $effectIds[$id] = true;
            }

            foreach ([1, 2, 3, 4] as $zoom) {
                if ($zoom === 4) {
                    continue;
                }

                foreach ($this->assets->requestFiles($request, "icon_x{$zoom}") as $file) {
                    $id = $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($file->getClientOriginalName()), 'icon', $iconIds);
                    $saved[] = $this->assets->saveGameUpload($file, "data/icon/x{$zoom}/{$id}.png");
                    $assetIds[$id] = true;
                    $iconIds[$id] = true;
                }

                foreach ($this->assets->requestFiles($request, "effect_x{$zoom}") as $file) {
                    $id = $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($file->getClientOriginalName()), 'effect', $effectIds);
                    $effectName = str_contains(strtolower($file->getClientOriginalName()), 'imageeffect_')
                        ? "ImageEffect_{$id}.png"
                        : "ImgEffect_{$id}.png";
                    $saved[] = $this->assets->saveGameUpload($file, "data/effect/x{$zoom}/{$effectName}");
                    $assetIds[$id] = true;
                    $effectIds[$id] = true;
                }
            }

            $orderedEffectIds = array_values(array_unique(array_map('intval', array_keys($effectIds))));
            sort($orderedEffectIds);
            foreach ($this->assets->requestFiles($request, 'effect_data_file') as $file) {
                $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
                $id = ($candidate !== null && isset($effectIds[$candidate]))
                    ? $candidate
                    : ($orderedEffectIds[count(array_filter($saved, fn($path) => str_contains($path, 'DataEffect_')))] ?? null);
                if ($id === null) {
                    $id = $this->assets->resolveGameAssetId($candidate, 'effect', $effectIds);
                    $effectIds[$id] = true;
                }
                $saved[] = $this->assets->saveGameUpload($file, "data/effdata/DataEffect_{$id}");
                $assetIds[$id] = true;
                $effectIds[$id] = true;
            }

            if ($request->filled('effect_data_text')) {
                $id = $orderedEffectIds[0] ?? $this->assets->resolveGameAssetId(null, 'effect', $effectIds);
                $path = $this->assets->gameSrcPath("data/effdata/DataEffect_{$id}");
                File::ensureDirectoryExists(dirname($path), 0775, true);
                File::put($path, (string) $request->input('effect_data_text'));
                $saved[] = $path;
                $assetIds[$id] = true;
                $effectIds[$id] = true;
            }

            if (!$assetIds) {
                $assetIds[$this->assets->nextGameEffectId()] = true;
            }

            $iconIds = array_map('intval', array_keys($iconIds));
            $effectIds = array_map('intval', array_keys($effectIds));
            sort($iconIds);
            sort($effectIds);
            $assetPairs = $this->assets->pairTitleAssetIds($iconIds, $effectIds, array_map('intval', array_keys($assetIds)));

            DB::beginTransaction();
            $game->beginTransaction();
            $nextItemId = $this->nextGameId('item_template');
            $baseName = trim((string) $request->input('name'));
            foreach ($assetPairs as $index => $pair) {
                $itemId = $nextItemId + $index;
                $iconId = $pair['icon_id'];
                $partId = $pair['part'];
                $nameSuffix = $iconId === $partId ? $iconId : "{$iconId}-{$partId}";
                $name = count($assetPairs) === 1 ? $baseName : "{$baseName} {$nameSuffix}";
                $row = [
                    'id' => $itemId,
                    'TYPE' => (int) $request->input('type', 99),
                    'gender' => (int) $request->input('gender', 3),
                    'NAME' => $name,
                    'description' => (string) $request->input('description', 'Danh hiệu'),
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
                    'comment' => "Admin title item. icon_id={$iconId};part={$partId}",
                ];
                $game->table('item_template')->insert($row);
                $created[] = [
                    'item_id' => $itemId,
                    'icon_id' => $iconId,
                    'part' => $partId,
                    'name' => $name,
                ];
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
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 500, 'message' => 'Không tạo được danh hiệu: ' . $e->getMessage()];
        }

        $this->logAdminAction(
            'title_item.create',
            'item_template',
            $created[0]['item_id'] ?? 0,
            'Tạo danh hiệu item_template ' . trim((string) $request->input('name')),
            null,
            [
                'created' => $created,
                'saved_files' => $saved,
            ],
        );

        return [
            'ok' => true,
            'message' => 'Đã lưu asset vào src game và tạo ' . count($created) . ' item_template danh hiệu',
            'data' => [
                'created' => $created,
                'saved_files' => $saved,
            ],
        ];
    }

    public function update(Request $request, int $id): array
    {
        if (!Schema::connection('game')->hasTable('item_template')) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template'];
        }

        $game = DB::connection('game');
        $before = $game->table('item_template')->where('id', $id)->first();
        if (!$before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Danh hiệu không tồn tại'];
        }

        $iconId = (int) $before->icon_id;
        $partId = (int) $before->part;
        $saved = [];

        try {
            $iconFile = $this->assets->requestFiles($request, 'icon_x4')[0] ?? null;
            if ($iconFile) {
                $iconId = $iconId > 0 ? $iconId : $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($iconFile->getClientOriginalName()), 'icon', []);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramid($iconFile, 'data/icon', "{$iconId}.png", 96));
            }

            $effectFile = $this->assets->requestFiles($request, 'effect_x4')[0] ?? null;
            if ($effectFile) {
                $partId = $partId > 0 ? $partId : $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($effectFile->getClientOriginalName()), 'effect', []);
                $saved = array_merge($saved, $this->assets->saveGamePngPyramid($effectFile, 'data/effect', "ImgEffect_{$partId}.png"));
            }

            $effectDataFile = $this->assets->requestFiles($request, 'effect_data_file')[0] ?? null;
            if ($effectDataFile) {
                $partId = $partId > 0 ? $partId : $this->assets->resolveGameAssetId($this->assets->numericIdFromFilename($effectDataFile->getClientOriginalName()), 'effect', []);
                $saved[] = $this->assets->saveGameUpload($effectDataFile, "data/effdata/DataEffect_{$partId}");
            } elseif ($request->filled('effect_data_text')) {
                $partId = $partId > 0 ? $partId : $this->assets->resolveGameAssetId(null, 'effect', []);
                $path = $this->assets->gameSrcPath("data/effdata/DataEffect_{$partId}");
                File::ensureDirectoryExists(dirname($path), 0775, true);
                File::put($path, (string) $request->input('effect_data_text'));
                $saved[] = $path;
            }

            $payload = [
                'TYPE' => (int) $request->input('type', $before->TYPE ?? 99),
                'gender' => (int) $request->input('gender', $before->gender ?? 3),
                'NAME' => trim((string) $request->input('name')),
                'description' => (string) $request->input('description', $before->description ?? 'Danh hiệu'),
                'icon_id' => $iconId,
                'part' => $partId,
                'comment' => "Admin title item. icon_id={$iconId};part={$partId}",
            ];

            $game->table('item_template')->where('id', $id)->update($payload);
            $after = $game->table('item_template')->where('id', $id)->first();
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 500, 'message' => 'Không cập nhật được danh hiệu: ' . $e->getMessage()];
        }

        $this->logAdminAction(
            'title_item.update',
            'item_template',
            $id,
            'Cập nhật danh hiệu item_template ' . trim((string) $request->input('name')),
            $this->sanitizeLogState((array) $before),
            $this->sanitizeLogState([
                'row' => (array) $after,
                'saved_files' => $saved,
            ]),
        );

        return [
            'ok' => true,
            'message' => 'Đã cập nhật danh hiệu',
            'data' => [
                'item_id' => $id,
                'icon_id' => $iconId,
                'part' => $partId,
                'saved_files' => $saved,
            ],
        ];
    }

    protected function nextGameId(string $table): int
    {
        return (int) (DB::connection('game')->table($table)->max('id') ?? -1) + 1;
    }
}
