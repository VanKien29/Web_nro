@extends('admin.layout')
@section('title', 'Tạo tài khoản game')

@section('content')
<div class="card" style="max-width:600px;">
    <form method="POST" action="{{ route('admin.accounts.store') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Username</label>
            <input class="form-input" name="username" value="{{ old('username') }}" required>
            @error('username') <div style="color:#fca5a5; font-size:13px;">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input class="form-input" name="password" type="text" required>
            @error('password') <div style="color:#fca5a5; font-size:13px;">{{ $message }}</div> @enderror
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
            <div class="form-group">
                <label class="form-label">Cash</label>
                <input class="form-input" name="cash" type="number" value="{{ old('cash', 0) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Danap</label>
                <input class="form-input" name="danap" type="number" value="{{ old('danap', 0) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Coin</label>
                <input class="form-input" name="coin" type="number" value="{{ old('coin', 0) }}">
            </div>
        </div>
        <div class="form-group flex gap-4">
            <label class="form-check"><input type="checkbox" name="is_admin" value="1" {{ old('is_admin') ? 'checked'
                    : '' }}> Admin</label>
            <label class="form-check"><input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked'
                    : '' }}> Active</label>
            <label class="form-check"><input type="checkbox" name="ban" value="1" {{ old('ban') ? 'checked' : '' }}>
                Ban</label>
        </div>
        <button class="btn btn-primary" type="submit">Tạo tài khoản</button>
        <a href="{{ route('admin.accounts.index') }}" class="btn" style="color:#94a3b8;">Huỷ</a>
    </form>
</div>
@endsection