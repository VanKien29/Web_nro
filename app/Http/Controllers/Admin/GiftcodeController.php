<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GiftcodeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $giftcodes = DB::connection('game')->table('giftcode')
            ->select('id', 'code', 'count_left', 'expired', 'mtv', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.giftcodes.index', [
            'giftcodes' => $giftcodes,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.giftcodes.create');
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

        return view('admin.giftcodes.edit', [
            'giftcode' => $giftcode,
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
}