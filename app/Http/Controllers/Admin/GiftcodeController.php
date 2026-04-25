<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GiftcodeController extends Controller
{
    public function searchItems(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = $this->webItemIndexReady()
            ? DB::table('game_item_indexes')->select('id', 'name', 'icon_id')
            : DB::connection('game')->table('item_template')->select('id', 'NAME as name', 'icon_id');

        if ($q !== '') {
            if (is_numeric($q)) {
                $query->where('id', (int) $q);
            } else {
                if ($this->webItemIndexReady()) {
                    $query->where(function ($builder) use ($q) {
                        $builder->where('name', 'like', "%{$q}%")
                            ->orWhere('normalized_name', 'like', '%' . mb_strtolower($q) . '%');
                    });
                } else {
                    $query->where('NAME', 'like', "%{$q}%");
                }
            }
        }

        return response()->json($query->limit(30)->get());
    }

    public function allOptions(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 30), 1), 200);
        $page = max((int) $request->query('page', 1), 1);
        $paginate = $request->boolean('paginate') || $request->has('page') || $search !== '';

        $query = $this->webOptionIndexReady()
            ? DB::table('game_item_option_indexes')->select('id', 'name')
            : DB::connection('game')->table('item_option_template')->select('id', 'NAME as name');

        if ($search !== '') {
            if (is_numeric($search)) {
                $query->where('id', (int) $search);
            } else {
                if ($this->webOptionIndexReady()) {
                    $query->where(function ($builder) use ($search) {
                        $builder->where('name', 'like', "%{$search}%")
                            ->orWhere('normalized_name', 'like', '%' . mb_strtolower($search) . '%');
                    });
                } else {
                    $query->where('NAME', 'like', "%{$search}%");
                }
            }
        }

        if ($paginate) {
            $total = (clone $query)->count();
            $options = $query->orderBy('id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return response()->json([
                'ok' => true,
                'data' => $options,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ]);
        }

        $options = Cache::remember('admin:item_option_template:all:v1', now()->addMinutes(30), function () use ($query) {
            return $query->orderBy('id')->get();
        });
        return response()->json($options);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $giftcodes = DB::connection('game')->table('giftcode')
            ->select('id', 'code', 'count_left', 'detail', 'expired', 'mtv', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Collect all item IDs from detail JSON to resolve icon_ids
        $allItemIds = [];
        foreach ($giftcodes as $gc) {
            $detail = json_decode($this->fixJson($gc->detail ?? '[]'), true) ?: [];
            foreach ($detail as $item) {
                if (isset($item['temp_id'])) {
                    $allItemIds[] = (int) $item['temp_id'];
                }
            }
        }
        $itemIcons = [];
        if (!empty($allItemIds)) {
            $items = DB::connection('game')->table('item_template')
                ->select('id', 'icon_id')
                ->whereIn('id', array_unique($allItemIds))
                ->get();
            foreach ($items as $item) {
                $itemIcons[$item->id] = $item->icon_id;
            }
        }

        return view('admin.giftcodes.index', [
            'giftcodes' => $giftcodes,
            'search' => $search,
            'itemIcons' => $itemIcons,
        ]);
    }

    public function create(): View
    {
        $options = DB::connection('game')->table('item_option_template')
            ->select('id', 'NAME as name')
            ->orderBy('id')
            ->get();

        return view('admin.giftcodes.create', compact('options'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'count_left' => ['nullable', 'integer', 'min:0'],
            'mtv' => ['nullable', 'integer', 'min:0'],
            'detail' => ['nullable', 'string'],
            'expired' => ['nullable', 'date'],
        ]);

        $code = trim($validated['code']);

        $exists = DB::connection('game')->table('giftcode')
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['code' => 'Giftcode đã tồn tại.'])
                ->withInput();
        }

        DB::connection('game')->table('giftcode')->insert([
            'code' => $code,
            'count_left' => (int) ($validated['count_left'] ?? 0),
            'mtv' => (int) ($validated['mtv'] ?? 0),
            'active' => $request->boolean('active', true) ? 1 : 0,
            'detail' => $validated['detail'] ?? '[]',
            'expired' => $validated['expired'] ?? null,
        ]);

        return redirect()->route('admin.giftcodes.index')->with('status', 'Tạo giftcode thành công.');
    }

    public function edit(int $id): View
    {
        $giftcode = DB::connection('game')->table('giftcode')->where('id', $id)->firstOrFail();

        $options = DB::connection('game')->table('item_option_template')
            ->select('id', 'NAME as name')
            ->orderBy('id')
            ->get();

        // Resolve item names/icons for detail items
        $detail = json_decode($this->fixJson($giftcode->detail ?? '[]'), true) ?: [];
        $itemIds = array_column($detail, 'temp_id');
        $itemMap = [];
        if (!empty($itemIds)) {
            $items = DB::connection('game')->table('item_template')
                ->select('id', 'NAME as name', 'icon_id')
                ->whereIn('id', $itemIds)
                ->get();
            foreach ($items as $item) {
                $itemMap[$item->id] = $item;
            }
        }

        return view('admin.giftcodes.edit', [
            'giftcode' => $giftcode,
            'options' => $options,
            'itemMap' => $itemMap,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'count_left' => ['nullable', 'integer', 'min:0'],
            'mtv' => ['nullable', 'integer', 'min:0'],
            'detail' => ['nullable', 'string'],
            'expired' => ['nullable', 'date'],
        ]);

        DB::connection('game')->table('giftcode')
            ->where('id', $id)
            ->update([
                'count_left' => (int) ($validated['count_left'] ?? 0),
                'mtv' => (int) ($validated['mtv'] ?? 0),
                'active' => $request->boolean('active') ? 1 : 0,
                'detail' => $validated['detail'] ?? '[]',
                'expired' => $validated['expired'] ?? null,
            ]);

        return redirect()->route('admin.giftcodes.edit', $id)->with('status', 'Cập nhật giftcode thành công.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::connection('game')->table('giftcode')->where('id', $id)->delete();

        return redirect()->route('admin.giftcodes.index')->with('status', 'Đã xoá giftcode.');
    }

    private function fixJson(?string $str): string
    {
        if (!$str) return '[]';
        $s = trim($str);
        $s = preg_replace('/,\s*([\]\}])/', '$1', $s);
        $s = preg_replace('/([\[\{])\s*,/', '$1', $s);
        $s = preg_replace('/,\s*,/', ',', $s);
        return $s;
    }

    private function webItemIndexReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $ready = Schema::hasTable('game_item_indexes')
                && DB::table('game_item_indexes')->exists();
        } catch (\Throwable $e) {
            $ready = false;
        }

        return $ready;
    }

    private function webOptionIndexReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $ready = Schema::hasTable('game_item_option_indexes')
                && DB::table('game_item_option_indexes')->exists();
        } catch (\Throwable $e) {
            $ready = false;
        }

        return $ready;
    }
}
