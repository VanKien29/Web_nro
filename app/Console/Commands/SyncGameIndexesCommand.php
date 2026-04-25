<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncGameIndexesCommand extends Command
{
    protected $signature = 'game:sync-indexes {--chunk=500 : Số bản ghi xử lý mỗi lượt}';

    protected $description = 'Đồng bộ bảng index nhẹ từ DB game sang DB web cho item, option và type';

    public function handle(): int
    {
        if (!Schema::hasTable('game_item_indexes') ||
            !Schema::hasTable('game_item_option_indexes') ||
            !Schema::hasTable('game_item_type_indexes')) {
            $this->error('Thiếu bảng index web. Hãy chạy php artisan migrate trước.');

            return self::FAILURE;
        }

        $chunk = max(100, (int) $this->option('chunk'));
        $game = DB::connection('game');
        $web = DB::connection();
        $now = now();

        $this->info('Đồng bộ item index...');
        $itemIds = [];
        $game->table('item_template')
            ->selectRaw('id, NAME as name, TYPE as type, icon_id, part, head, body, leg, description, is_up_to_up')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($web, $now, &$itemIds) {
                $payload = [];
                foreach ($rows as $row) {
                    $id = (int) $row->id;
                    $itemIds[] = $id;
                    $payload[] = [
                        'id' => $id,
                        'name' => (string) ($row->name ?? ''),
                        'normalized_name' => Str::lower(trim((string) ($row->name ?? ''))),
                        'type' => $row->type !== null ? (int) $row->type : null,
                        'icon_id' => (int) ($row->icon_id ?? 0),
                        'part' => $row->part !== null ? (int) $row->part : null,
                        'head' => $row->head !== null ? (int) $row->head : null,
                        'body' => $row->body !== null ? (int) $row->body : null,
                        'leg' => $row->leg !== null ? (int) $row->leg : null,
                        'description' => $row->description !== null ? (string) $row->description : null,
                        'is_up_to_up' => !empty($row->is_up_to_up),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($payload)) {
                    $web->table('game_item_indexes')->upsert(
                        $payload,
                        ['id'],
                        ['name', 'normalized_name', 'type', 'icon_id', 'part', 'head', 'body', 'leg', 'description', 'is_up_to_up', 'updated_at']
                    );
                }
            });

        if (!empty($itemIds)) {
            $web->table('game_item_indexes')->whereNotIn('id', array_unique($itemIds))->delete();
        } else {
            $web->table('game_item_indexes')->truncate();
        }

        $this->info('Đồng bộ option index...');
        $optionIds = [];
        $game->table('item_option_template')
            ->selectRaw('id, NAME as name')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($web, $now, &$optionIds) {
                $payload = [];
                foreach ($rows as $row) {
                    $id = (int) $row->id;
                    $optionIds[] = $id;
                    $payload[] = [
                        'id' => $id,
                        'name' => (string) ($row->name ?? ''),
                        'normalized_name' => Str::lower(trim((string) ($row->name ?? ''))),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($payload)) {
                    $web->table('game_item_option_indexes')->upsert(
                        $payload,
                        ['id'],
                        ['name', 'normalized_name', 'updated_at']
                    );
                }
            });

        if (!empty($optionIds)) {
            $web->table('game_item_option_indexes')->whereNotIn('id', array_unique($optionIds))->delete();
        } else {
            $web->table('game_item_option_indexes')->truncate();
        }

        $this->info('Đồng bộ type index...');
        $typeRows = $game->table('item_template')
            ->selectRaw('TYPE as id, COUNT(*) as item_count')
            ->whereNotNull('TYPE')
            ->groupBy('TYPE')
            ->orderBy('TYPE')
            ->get();

        $typeIds = $typeRows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $nameById = [];
        $nameByIndex = [];

        if (!empty($typeIds)) {
            $nameById = $game->table('type_item')
                ->whereIn('id', $typeIds)
                ->where('NAME', '<>', '.')
                ->pluck('NAME', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();

            $indexRows = $game->table('type_item')
                ->whereIn('index_body', $typeIds)
                ->where('index_body', '>=', 0)
                ->where('NAME', '<>', '.')
                ->orderBy('id')
                ->get(['index_body', 'NAME']);

            foreach ($indexRows as $row) {
                $index = (int) $row->index_body;
                if (!isset($nameByIndex[$index])) {
                    $nameByIndex[$index] = (string) $row->NAME;
                }
            }
        }

        $typePayload = [];
        foreach ($typeRows as $row) {
            $typeId = (int) $row->id;
            $typePayload[] = [
                'id' => $typeId,
                'name' => $nameById[$typeId] ?? ($nameByIndex[$typeId] ?? ('Type ' . $typeId)),
                'item_count' => (int) ($row->item_count ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($typePayload)) {
            $web->table('game_item_type_indexes')->upsert(
                $typePayload,
                ['id'],
                ['name', 'item_count', 'updated_at']
            );
            $web->table('game_item_type_indexes')->whereNotIn('id', $typeIds)->delete();
        } else {
            $web->table('game_item_type_indexes')->truncate();
        }

        $this->info('Đồng bộ index hoàn tất.');

        return self::SUCCESS;
    }
}
