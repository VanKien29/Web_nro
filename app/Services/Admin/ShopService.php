<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopService extends AdminServiceSupport
{
    public function list(Request $request): array
    {
        $search = $request->query('search', '');

        $query = DB::connection('game')->table('shop')
            ->leftJoin('npc_template', 'shop.npc_id', '=', 'npc_template.id')
            ->select('shop.id', 'shop.npc_id', 'shop.tag_name', 'shop.type_shop', 'npc_template.NAME as npc_name', 'npc_template.avatar as npc_avatar');

        if ($search) {
            $query->where('shop.tag_name', 'LIKE', "%{$search}%");
        }

        $shops = $query->orderBy('shop.npc_id')->orderBy('shop.id')->get();
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
                try {
                    $items = json_decode($this->fixJson($tab->items ?? '[]'), true) ?: [];
                } catch (\Throwable) {
                    $items = [];
                }
                $tab->item_count = count($items);
                unset($tab->items);

                return $tab;
            })->values();
        }

        return ['ok' => true, 'data' => $shops];
    }

    public function getTab(int $tabId): array
    {
        $tab = DB::connection('game')->table('tab_shop')->where('id', $tabId)->first();
        if (!$tab) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tab không tồn tại'];
        }

        $shop = DB::connection('game')->table('shop')->where('id', $tab->shop_id)->first();

        return ['ok' => true, 'data' => $tab, 'shop' => $shop];
    }

    public function updateTab(Request $request, int $tabId): array
    {
        $tab = DB::connection('game')->table('tab_shop')->where('id', $tabId)->first();
        if (!$tab) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tab không tồn tại'];
        }

        $data = $request->only(['tab_name', 'tab_index', 'items']);
        if (empty($data)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Không có dữ liệu để cập nhật'];
        }

        if (isset($data['tab_index'])) {
            $data['tab_index'] = (int) $data['tab_index'];
        }

        $updated = DB::connection('game')->table('tab_shop')->where('id', $tabId)->update($data);
        if ($updated === false) {
            return ['ok' => false, 'status' => 500, 'message' => 'Cập nhật tab thất bại'];
        }

        $after = DB::connection('game')->table('tab_shop')->where('id', $tabId)->first();
        $this->logAdminAction(
            'shop.tab.update',
            'tab_shop',
            $tabId,
            'Cập nhật tab shop ' . ($after->tab_name ?? $tabId),
            $this->sanitizeLogState((array) $tab),
            $this->sanitizeLogState((array) $after),
        );

        return ['ok' => true, 'message' => 'Cập nhật tab thành công'];
    }
}
