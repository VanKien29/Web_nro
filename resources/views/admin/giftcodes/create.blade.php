@extends('admin.layout')
@section('title', 'Tạo Giftcode')

@section('content')
<div class="card" style="max-width:600px;">
    <form method="POST" action="{{ route('admin.giftcodes.store') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Code</label>
            <input class="form-input" name="code" value="{{ old('code') }}" required>
            @error('code') <div style="color:#fca5a5; font-size:13px;">{{ $message }}</div> @enderror
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div class="form-group">
                <label class="form-label">Số lượng (count_left)</label>
                <input class="form-input" name="count_left" type="number" value="{{ old('count_left', 100) }}">
            </div>
            <div class="form-group">
                <label class="form-label">MTV (1 lần/tài khoản)</label>
                <input class="form-input" name="mtv" type="number" value="{{ old('mtv', 0) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Detail (JSON)</label>
            <textarea class="form-input" name="detail" rows="4">{{ old('detail', '[]') }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Ngày hết hạn</label>
            <input class="form-input" name="expired" type="date" value="{{ old('expired') }}">
        </div>
        <div class="form-group">
            <label class="form-check"><input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked'
                    : '' }}> Active</label>
        </div>
        <button class="btn btn-primary" type="submit">Tạo giftcode</button>
        <a href="{{ route('admin.giftcodes.index') }}" class="btn" style="color:#94a3b8;">Huỷ</a>
    </form>
</div>
@endsection