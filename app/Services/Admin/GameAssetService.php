<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class GameAssetService
{
    private ?string $gameSrcRootCache = null;
    private ?array $usedGameIconIdsCache = null;
    private ?int $usedGameIconMaxCache = null;
    private array $assetFileMaxCache = [];

    public function gameSrcPath(string $child = ''): string
    {
        if ($this->gameSrcRootCache !== null) {
            return $this->gameSrcRootCache . ($child !== '' ? DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $child) : '');
        }

        $candidates = array_filter([
            env('GAME_SRC_PATH'),
            rtrim((string) getenv('USERPROFILE'), '\\/') . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'SRC' . DIRECTORY_SEPARATOR . 'SRC',
            base_path('../../../../SRC/SRC'),
            base_path('../../../SRC'),
        ]);

        foreach ($candidates as $candidate) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
            if (is_dir($path . DIRECTORY_SEPARATOR . 'data')) {
                $this->gameSrcRootCache = rtrim($path, DIRECTORY_SEPARATOR);
                return $this->gameSrcRootCache . ($child !== '' ? DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $child) : '');
            }
        }

        $this->gameSrcRootCache = rtrim((string) ($candidates[0] ?? base_path()), DIRECTORY_SEPARATOR);
        return $this->gameSrcRootCache . ($child !== '' ? DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $child) : '');
    }

    public function gameIconUrl(int $iconId): ?string
    {
        if ($iconId <= 0 || !is_file($this->gameSrcPath("data/icon/x4/{$iconId}.png"))) {
            return null;
        }

        return "/assets/game-icons/x4/{$iconId}.png";
    }

    public function titleAssetPaths(): array
    {
        return [
            'game_src' => $this->gameSrcPath(),
            'icon' => $this->gameSrcPath('data/icon'),
            'effect' => $this->gameSrcPath('data/effect'),
            'effdata' => $this->gameSrcPath('data/effdata'),
        ];
    }

    public function requestFiles(Request $request, string $field): array
    {
        $files = $request->file($field);
        if (!$files) {
            return [];
        }

        return is_array($files) ? array_values(array_filter($files)) : [$files];
    }

    public function numericIdFromFilename(string $filename): ?int
    {
        $name = pathinfo(str_replace('\\', '/', $filename), PATHINFO_FILENAME);
        if (preg_match('/(\d+)(?!.*\d)/', $name, $matches)) {
            $id = (int) $matches[1];
            return $id > 0 && $id <= 32767 ? $id : null;
        }

        return null;
    }

    public function resolveGameAssetId(?int $candidate, string $kind, array $used): int
    {
        if ($candidate !== null && $candidate > 0 && !isset($used[$candidate]) && $this->gameAssetIdAvailable($candidate, $kind)) {
            return $candidate;
        }

        $id = $candidate !== null && $candidate > 0 && $candidate <= 32767
            ? $candidate + 1
            : $this->nextFreeGameAssetStart($kind);

        while (!$this->gameAssetIdAvailable($id, $kind) || isset($used[$id])) {
            $id++;
            if ($id > 32767) {
                throw new \RuntimeException('Không còn ID trống hợp lệ cho ' . $kind . ' (1-32767).');
            }
        }

        return $id;
    }

    public function nextGameEffectId(): int
    {
        $dbMax = (int) (DB::connection('game')->table('item_template')->where('part', '>=', 0)->max('part') ?? 0);
        $fileMax = 0;
        $dir = $this->gameSrcPath('data/effdata');
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . 'DataEffect_*') ?: [] as $file) {
                $name = preg_replace('/\D+/', '', pathinfo($file, PATHINFO_FILENAME));
                if (is_numeric($name)) {
                    $fileMax = max($fileMax, (int) $name);
                }
            }
        }

        return max($dbMax, $fileMax) + 1;
    }

    public function saveGameUpload($file, string $relativePath): string
    {
        $path = $this->gameSrcPath($relativePath);
        File::ensureDirectoryExists(dirname($path), 0775, true);
        $file->move(dirname($path), basename($path));
        return $path;
    }

    public function saveGamePngPyramid($file, string $baseDir, string $filename, ?int $maxX4Size = null): array
    {
        $source = @imagecreatefrompng($file->getRealPath());
        if (!$source) {
            throw new \RuntimeException('File ' . $file->getClientOriginalName() . ' không phải PNG hợp lệ.');
        }

        return $this->saveGamePngPyramidResource($source, $baseDir, $filename, $maxX4Size);
    }

    public function saveGamePngPyramidFromBytes(string $bytes, string $originalName, string $baseDir, string $filename, ?int $maxX4Size = null): array
    {
        $source = @imagecreatefromstring($bytes);
        if (!$source) {
            throw new \RuntimeException('File ' . $originalName . ' không phải PNG hợp lệ.');
        }

        return $this->saveGamePngPyramidResource($source, $baseDir, $filename, $maxX4Size);
    }

    public function decodeUploadedImagePayload(string $payload): array
    {
        $payload = trim($payload);
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Payload ảnh không hợp lệ.');
        }

        $files = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry) || empty($entry['name']) || empty($entry['data'])) {
                continue;
            }

            $name = basename(str_replace('\\', '/', (string) $entry['name']));
            if (!str_ends_with(strtolower($name), '.png')) {
                throw new \RuntimeException('Chỉ hỗ trợ PNG trong payload ảnh: ' . $name);
            }

            $bytes = base64_decode((string) $entry['data'], true);
            if ($bytes === false) {
                throw new \RuntimeException('Không decode được ảnh: ' . $name);
            }

            $files[] = ['name' => $name, 'bytes' => $bytes];
        }

        return $files;
    }

    public function pairTitleAssetIds(array $iconIds, array $effectIds, array $fallbackIds): array
    {
        $iconIds = array_values(array_unique(array_map('intval', $iconIds)));
        $effectIds = array_values(array_unique(array_map('intval', $effectIds)));
        $fallbackIds = array_values(array_unique(array_map('intval', $fallbackIds)));
        sort($iconIds);
        sort($effectIds);
        sort($fallbackIds);

        if ($iconIds && $effectIds) {
            $count = max(count($iconIds), count($effectIds));
            $pairs = [];
            for ($i = 0; $i < $count; $i++) {
                $iconId = $iconIds[$i] ?? $iconIds[count($iconIds) - 1];
                $effectId = $effectIds[$i] ?? $effectIds[count($effectIds) - 1];
                $pairs[] = ['icon_id' => $iconId, 'part' => $effectId];
            }

            return $pairs;
        }

        $ids = $iconIds ?: ($effectIds ?: $fallbackIds);
        return array_map(fn($id) => ['icon_id' => $id, 'part' => $id], $ids);
    }

    public function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function parseFlagBagIconData(string $raw): array
    {
        if (!preg_match_all('/\d+/', $raw, $matches)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            fn($value) => (int) $value,
            $matches[0],
        ), fn($id) => $id > 0 && $id <= 32767)));
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    public function filterUnreferencedIconIds($game, array $iconIds): array
    {
        $iconIds = array_values(array_unique(array_filter(array_map('intval', $iconIds), fn($id) => $id > 0)));
        if (!$iconIds) {
            return [];
        }
        $wanted = array_flip($iconIds);

        $referenced = [];
        $templateIconIds = $game->table('item_template')
            ->whereIn('icon_id', $iconIds)
            ->pluck('icon_id')
            ->all();
        foreach ($templateIconIds as $iconId) {
            $referenced[(int) $iconId] = true;
        }

        if (Schema::connection('game')->hasTable('head_avatar')) {
            $avatarIds = $game->table('head_avatar')
                ->whereIn('avatar_id', $iconIds)
                ->pluck('avatar_id')
                ->all();
            foreach ($avatarIds as $iconId) {
                $referenced[(int) $iconId] = true;
            }
        }

        if (Schema::connection('game')->hasTable('part')) {
            foreach ($game->table('part')->get(['DATA']) as $part) {
                foreach ($this->decodePartData($part->DATA ?? '') as $layer) {
                    $iconId = (int) ($layer['icon_id'] ?? 0);
                    if (isset($wanted[$iconId])) {
                        $referenced[$iconId] = true;
                    }
                }
            }
        }

        if (Schema::connection('game')->hasTable('flag_bag')) {
            $flagIconIds = $game->table('flag_bag')
                ->whereIn('icon_id', $iconIds)
                ->pluck('icon_id')
                ->all();
            foreach ($flagIconIds as $iconId) {
                $referenced[(int) $iconId] = true;
            }

            foreach ($game->table('flag_bag')->get(['icon_data']) as $flagRow) {
                foreach ($this->parseFlagBagIconData((string) ($flagRow->icon_data ?? '')) as $iconId) {
                    if (isset($wanted[$iconId])) {
                        $referenced[$iconId] = true;
                    }
                }
            }
        }

        return array_values(array_filter($iconIds, fn($iconId) => !isset($referenced[$iconId])));
    }

    public function deleteGameIconFiles(array $iconIds): array
    {
        $deleted = [];
        foreach (array_unique(array_map('intval', $iconIds)) as $iconId) {
            if ($iconId <= 0) {
                continue;
            }

            foreach ([1, 2, 3, 4] as $zoom) {
                $path = $this->gameSrcPath("data/icon/x{$zoom}/{$iconId}.png");
                if (is_file($path) && @unlink($path)) {
                    $deleted[] = $path;
                }
            }
        }

        return $deleted;
    }

    public function parsePartLayers(string $raw): array
    {
        $normalized = trim($raw);
        if ($normalized === '') {
            throw new \InvalidArgumentException('DATA part không được để trống.');
        }

        $decoded = json_decode($this->fixJson($normalized), true);
        if (is_string($decoded)) {
            $decoded = json_decode($this->fixJson($decoded), true);
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('DATA part phải là JSON dạng [[icon_id,dx,dy], ...].');
        }

        $layers = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry) || count($entry) < 3) {
                throw new \InvalidArgumentException('Mỗi layer part phải có đủ 3 giá trị [icon_id, dx, dy].');
            }

            $layers[] = [
                (int) $entry[0],
                (int) $entry[1],
                (int) $entry[2],
            ];
        }

        if (!$layers) {
            throw new \InvalidArgumentException('DATA part không có layer hợp lệ.');
        }

        return $layers;
    }

    public function parseExtraPartLayerBlocks(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $blocks = preg_split('/\R{2,}/', $raw) ?: [];
        if (count($blocks) === 1) {
            $decoded = json_decode($this->fixJson($raw), true);
            if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0][0]) && is_array($decoded[0][0])) {
                return array_map(fn($block) => $this->parsePartLayers(json_encode($block, JSON_UNESCAPED_UNICODE)), $decoded);
            }
        }

        return array_values(array_filter(array_map(function ($block) {
            $block = trim((string) $block);

            return $block === '' ? null : $this->parsePartLayers($block);
        }, $blocks)));
    }

    public function completePetSpriteIdMap(array $idMap, array $uploadedIds, array $layers): array
    {
        $uploadedIds = array_values(array_unique(array_filter(array_map('intval', $uploadedIds), fn($id) => $id > 0)));
        if (!$uploadedIds) {
            return $idMap;
        }

        $sourceIds = [];
        foreach ($layers as $entry) {
            if (!is_array($entry) || !isset($entry[0]) || $this->isPetPlaceholderLayer($entry)) {
                continue;
            }

            $sourceId = (int) $entry[0];
            if ($sourceId > 0 && !isset($idMap[$sourceId])) {
                $sourceIds[$sourceId] = true;
            }
        }

        $sourceIds = array_keys($sourceIds);
        sort($sourceIds);
        foreach ($sourceIds as $index => $sourceId) {
            if (!isset($uploadedIds[$index])) {
                break;
            }

            $idMap[$sourceId] = $uploadedIds[$index];
        }

        return $idMap;
    }

    public function rewritePetPartLayers(array $layers, array $idMap): array
    {
        return array_map(function ($entry) use ($idMap) {
            $iconId = (int) $entry[0];
            if ($this->isPetPlaceholderLayer($entry)) {
                return [
                    $iconId,
                    (int) $entry[1],
                    (int) $entry[2],
                ];
            }

            return [
                $idMap[$iconId] ?? $iconId,
                (int) $entry[1],
                (int) $entry[2],
            ];
        }, $layers);
    }

    public function temporaryPartSourceIconIds(Request $request): array
    {
        $raw = implode("\n", [
            (string) $request->input('head_data', ''),
            (string) $request->input('body_data', ''),
            (string) $request->input('leg_data', ''),
            (string) $request->input('extra_head_data', ''),
        ]);

        $ids = [];
        if (preg_match_all('/\[\s*(\d+)\s*,\s*-?\d+\s*,\s*-?\d+\s*\]/', $raw, $matches)) {
            foreach ($matches[1] as $value) {
                $id = (int) $value;
                if ($id > 0 && $id < 1000) {
                    $ids[$id] = true;
                }
            }
        }

        return $ids;
    }

    public function decodePartData(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $normalized = trim((string) $raw);
        $normalized = str_replace('\\"', '"', $normalized);
        $decoded = json_decode($this->fixJson($normalized), true);
        if (is_string($decoded)) {
            $decoded = json_decode($this->fixJson($decoded), true);
        }

        if (!is_array($decoded)) {
            return [];
        }

        $layers = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $layers[] = [
                'icon_id' => isset($entry[0]) ? (int) $entry[0] : 0,
                'dx' => isset($entry[1]) ? (int) $entry[1] : 0,
                'dy' => isset($entry[2]) ? (int) $entry[2] : 0,
            ];
        }

        return $layers;
    }

    private function isPetPlaceholderLayer(array $entry): bool
    {
        $iconId = (int) ($entry[0] ?? 0);
        $dx = (int) ($entry[1] ?? 0);
        $dy = (int) ($entry[2] ?? 0);

        return $iconId === 2955 && $dx === 0 && $dy === 0;
    }

    private function saveGamePngPyramidResource($source, string $baseDir, string $filename, ?int $maxX4Size = null): array
    {
        imagesavealpha($source, true);
        $width = imagesx($source);
        $height = imagesy($source);
        $baseScale = 1.0;
        if ($maxX4Size && max($width, $height) > $maxX4Size) {
            $baseScale = $maxX4Size / max($width, $height);
        }
        $saved = [];

        foreach ([4 => 1, 3 => 0.75, 2 => 0.5, 1 => 0.25] as $zoom => $scale) {
            $targetWidth = max(1, (int) round($width * $baseScale * $scale));
            $targetHeight = max(1, (int) round($height * $baseScale * $scale));
            $path = $this->gameSrcPath($baseDir . "/x{$zoom}/{$filename}");
            File::ensureDirectoryExists(dirname($path), 0775, true);

            if ($targetWidth === $width && $targetHeight === $height) {
                imagepng($source, $path);
            } else {
                $target = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
                imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
                imagepng($target, $path);
                imagedestroy($target);
            }

            $saved[] = $path;
        }

        imagedestroy($source);
        return $saved;
    }

    private function gameAssetIdAvailable(int $id, string $kind): bool
    {
        if ($id <= 0 || $id > 32767) {
            return false;
        }

        if ($kind === 'icon') {
            return !isset($this->usedGameIconIds()[$id]);
        }

        if (DB::connection('game')->table('item_template')->where('part', $id)->exists()) {
            return false;
        }
        if (is_file($this->gameSrcPath("data/effdata/DataEffect_{$id}"))) {
            return false;
        }
        foreach ([1, 2, 3, 4] as $zoom) {
            if (
                is_file($this->gameSrcPath("data/effect/x{$zoom}/ImgEffect_{$id}.png")) ||
                is_file($this->gameSrcPath("data/effect/x{$zoom}/ImageEffect_{$id}.png"))
            ) {
                return false;
            }
        }

        return true;
    }

    private function nextFreeGameAssetStart(string $kind): int
    {
        if ($kind === 'icon') {
            return max(1, min(32767, $this->usedGameIconMax() + 1));
        }

        $dbMax = (int) (DB::connection('game')->table('item_template')->where('part', '>=', 0)->max('part') ?? 0);
        $effectMax = $this->maxNumericAssetFileId('data/effect/x4', '/(?:ImgEffect_|ImageEffect_)?(\d+)$/i');
        $dataMax = $this->maxNumericAssetFileId('data/effdata', '/DataEffect_(\d+)$/i');

        return max(1, min(32767, max($dbMax, $effectMax, $dataMax) + 1));
    }

    private function maxNumericAssetFileId(string $relativeDir, string $pattern): int
    {
        $cacheKey = $relativeDir . '|' . $pattern;
        if (array_key_exists($cacheKey, $this->assetFileMaxCache)) {
            return $this->assetFileMaxCache[$cacheKey];
        }

        $max = 0;
        $dir = $this->gameSrcPath($relativeDir);
        if (!is_dir($dir)) {
            return $this->assetFileMaxCache[$cacheKey] = 0;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match($pattern, $name, $matches)) {
                $id = (int) $matches[1];
                if ($id > 0 && $id <= 32767) {
                    $max = max($max, $id);
                }
            }
        }

        return $this->assetFileMaxCache[$cacheKey] = $max;
    }

    private function usedGameIconIds(): array
    {
        if ($this->usedGameIconIdsCache !== null) {
            return $this->usedGameIconIdsCache;
        }

        $used = [];
        $remember = function ($value) use (&$used): void {
            $id = (int) $value;
            if ($id > 0 && $id <= 32767) {
                $used[$id] = true;
            }
        };

        $game = DB::connection('game');
        if (Schema::connection('game')->hasTable('item_template')) {
            foreach ($game->table('item_template')->where('icon_id', '>', 0)->pluck('icon_id') as $iconId) {
                $remember($iconId);
            }
        }

        if (Schema::connection('game')->hasTable('head_avatar')) {
            foreach ($game->table('head_avatar')->where('avatar_id', '>', 0)->pluck('avatar_id') as $iconId) {
                $remember($iconId);
            }
        }

        if (Schema::connection('game')->hasTable('flag_bag')) {
            foreach ($game->table('flag_bag')->get(['icon_id', 'icon_data']) as $flagRow) {
                $remember($flagRow->icon_id ?? 0);
                foreach ($this->parseFlagBagIconData((string) ($flagRow->icon_data ?? '')) as $iconId) {
                    $remember($iconId);
                }
            }
        }

        if (Schema::connection('game')->hasTable('part')) {
            foreach ($game->table('part')->get(['DATA']) as $part) {
                foreach ($this->decodePartData($part->DATA ?? '') as $layer) {
                    $remember($layer['icon_id'] ?? 0);
                }
            }
        }

        foreach ([1, 2, 3, 4] as $zoom) {
            $dir = $this->gameSrcPath("data/icon/x{$zoom}");
            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.png') ?: [] as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (is_numeric($name)) {
                    $remember($name);
                }
            }
        }

        $this->usedGameIconIdsCache = $used;
        $this->usedGameIconMaxCache = $used ? max(array_keys($used)) : 0;

        return $used;
    }

    private function usedGameIconMax(): int
    {
        if ($this->usedGameIconMaxCache !== null) {
            return $this->usedGameIconMaxCache;
        }

        $this->usedGameIconIds();
        return (int) $this->usedGameIconMaxCache;
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
