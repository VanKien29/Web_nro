<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\ItemOptionTemplate;
use App\Models\Game\ItemTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GiftcodeController extends Controller
{
    public function index(): JsonResponse
    {
        $giftcodes = DB::connection('game')
            ->table('giftcode')
            ->where('active', 0)
            ->orderByDesc('id')
            ->select('id', 'code', 'detail', 'count_left', 'expired')
            ->get();

        if ($giftcodes->isEmpty()) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        // Gom temp_id và option_id từ detail
        $itemIds = [];
        $optionIds = [];

        foreach ($giftcodes as $gc) {
            $detail = json_decode($gc->detail, true);
            if (!is_array($detail)) continue;

            foreach ($detail as $item) {
                if (isset($item['temp_id'])) {
                    $itemIds[] = (int) $item['temp_id'];
                }
                if (!empty($item['options'])) {
                    foreach ($item['options'] as $op) {
                        if (isset($op['id'])) {
                            $optionIds[] = (int) $op['id'];
                        }
                    }
                }
            }
        }

        $itemIds = array_values(array_unique($itemIds));
        $optionIds = array_values(array_unique($optionIds));

        // Lấy item_template
        $itemMap = [];
        if ($itemIds) {
            $items = ItemTemplate::whereIn('id', $itemIds)->get();
            foreach ($items as $item) {
                $itemMap[$item->id] = $item;
            }
        }

        // Lấy item_option_template
        $optionMap = [];
        if ($optionIds) {
            $options = ItemOptionTemplate::whereIn('id', $optionIds)->get();
            foreach ($options as $opt) {
                $optionMap[$opt->id] = $opt->name;
            }
        }

        // Build lại dữ liệu
        $result = $giftcodes->map(function ($gc) use ($itemMap, $optionMap) {
            $items = [];
            $detail = json_decode($gc->detail, true);

            if (is_array($detail)) {
                foreach ($detail as $d) {
                    $tempId = $d['temp_id'] ?? null;
                    if (!$tempId) continue;

                    $tpl = $itemMap[$tempId] ?? null;

                    $opts = [];
                    if (!empty($d['options'])) {
                        foreach ($d['options'] as $op) {
                            $rawName = $optionMap[$op['id']] ?? ('Chỉ số #' . $op['id']);
                            $value = (int) $op['param'];
                            $display = str_replace('#', (string) $value, $rawName);

                            $opts[] = [
                                'id' => (int) $op['id'],
                                'text' => $display,
                                'param' => $value,
                                'raw' => $rawName,
                            ];
                        }
                    }

                    $items[] = [
                        'temp_id' => (int) $tempId,
                        'name' => $tpl ? $tpl->name : ('Vật phẩm #' . $tempId),
                        'description' => $tpl->description ?? '',
                        'type' => (int) ($tpl->type ?? -1),
                        'gender' => (int) ($tpl->gender ?? 3),
                        'level' => (int) ($tpl->level ?? 0),
                        'power_require' => (int) ($tpl->power_require ?? 0),
                        'icon_id' => (int) ($tpl->icon_id ?? $tempId),
                        'quantity' => (int) $d['quantity'],
                        'options' => $opts,
                    ];
                }
            }

            return [
                'id' => $gc->id,
                'code' => $gc->code,
                'count_left' => $gc->count_left,
                'expired' => $gc->expired,
                'items' => $items,
            ];
        });

        return response()->json(['ok' => true, 'data' => $result]);
    }
}
