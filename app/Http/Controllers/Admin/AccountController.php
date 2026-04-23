<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $accounts = Account::query()
            ->select('id', 'username', 'ban', 'is_admin', 'active', 'cash', 'danap', 'coin')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'cash' => ['nullable', 'integer', 'min:0'],
            'danap' => ['nullable', 'integer', 'min:0'],
            'coin' => ['nullable', 'integer', 'min:0'],
        ]);

        if (Account::query()->where('username', $validated['username'])->exists()) {
            return back()
                ->withErrors(['username' => 'Tên tài khoản đã tồn tại.'])
                ->withInput();
        }

        Account::query()->create([
            'username' => trim($validated['username']),
            'password' => $validated['password'],
            'cash' => (int) ($validated['cash'] ?? 0),
            'danap' => (int) ($validated['danap'] ?? 0),
            'coin' => (int) ($validated['coin'] ?? 0),
            'ban' => $request->boolean('ban'),
            'is_admin' => $request->boolean('is_admin'),
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.accounts.index')->with('status', 'Tạo tài khoản game thành công.');
    }

    public function edit(Account $account): View
    {
        return view('admin.accounts.edit', [
            'account' => $account,
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'cash' => ['nullable', 'integer', 'min:0'],
            'danap' => ['nullable', 'integer', 'min:0'],
            'coin' => ['nullable', 'integer', 'min:0'],
        ]);

        $exists = Account::query()
            ->where('username', $validated['username'])
            ->where('id', '!=', $account->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['username' => 'Tên tài khoản đã tồn tại.'])
                ->withInput();
        }

        $data = [
            'username' => trim($validated['username']),
            'cash' => (int) ($validated['cash'] ?? 0),
            'danap' => (int) ($validated['danap'] ?? 0),
            'coin' => (int) ($validated['coin'] ?? 0),
            'ban' => $request->boolean('ban'),
            'is_admin' => $request->boolean('is_admin'),
            'active' => $request->boolean('active'),
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $account->update($data);

        return redirect()->route('admin.accounts.edit', $account)->with('status', 'Cập nhật tài khoản game thành công.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        Account::query()->whereKey($account->id)->delete();
        return redirect()->route('admin.accounts.index')->with('status', 'Đã xoá tài khoản game.');
    }
}
