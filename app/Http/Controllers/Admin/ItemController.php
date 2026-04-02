<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));

        $items = DB::connection('game')->table('item_template')
            ->select('id', 'name', 'icon_id', 'type')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->orderBy('type')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        $types = DB::connection('game')->table('item_template')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('admin.items.index', [
            'items' => $items,
            'types' => $types,
            'search' => $search,
            'type' => $type,
        ]);
    }
}