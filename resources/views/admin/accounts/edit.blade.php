@extends('admin.layout')
@section('title', 'Sửa tài khoản: ' . $account->username)

@section('content')
<div class="card" style="max-width:600px;">
    <form method="POST" action="{{ route('admin.accounts.update', $account) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label class="form-label">Username</label>
            <input class="form-input" name="username" value="{{ old('username', $account->username) }}" required>
            @error('username') <div style="color:#fca5a5; font-size:13px;">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password <small style="color:#64748b;">(để trống nếu không đổi)</small></label>
            <input class="form-input" name="password" type="text">
            @error('password') <div style="color:#fca5a5; font-size:13px;">{{ $message }}</div> @enderror
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
            <div class="form-group">
                <label class="form-label">Cash</label>
                <input class="form-input" name="cash" type="number" value="{{ old('cash', $account->cash) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Danap</label>
                <input class="form-input" name="danap" type="number" value="{{ old('danap', $account->danap) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Coin</label>
                <input class="form-input" name="coin" type="number" value="{{ old('coin', $account->coin) }}">
            </div>
        </div>
        <div class="form-group flex gap-4">
            <label class="form-check"><input type="checkbox" name="is_admin" value="1" {{ old('is_admin',
                    $account->is_admin) ? 'checked' : '' }}> Admin</label>
            <label class="form-check"><input type="checkbox" name="active" value="1" {{ old('active', $account->active)
                ? 'checked' : '' }}> Active</label>
            <label class="form-check"><input type="checkbox" name="ban" value="1" {{ old('ban', $account->ban) ?
                'checked' : '' }}> Ban</label>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
            <a href="{{ route('admin.accounts.index') }}" class="btn" style="color:#94a3b8;">Quay lại</a>
        </div>
    </form>

    <hr style="border-color:#334155; margin:24px 0;">
    <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}"
        onsubmit="return confirm('Bạn chắc chắn muốn xoá tài khoản này?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-sm" type="submit">Xoá tài khoản</button>
    </form>
</div>
@endsection