@extends('admin.layout')
@section('title', 'Sửa Giftcode: ' . $giftcode->code)

@section('content')
<div class="card" style="max-width:600px;">
    <form method="POST" action="{{ route('admin.giftcodes.update', $giftcode->id) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label class="form-label">Code</label>
            <input class="form-input" value="{{ $giftcode->code }}" disabled style="opacity:.6;">
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div class="form-group">
                <label class="form-label">Số lượng (count_left)</label>
                <input class="form-input" name="count_left" type="number"
                    value="{{ old('count_left', $giftcode->count_left) }}">
            </div>
            <div class="form-group">
                <label class="form-label">MTV</label>
                <input class="form-input" name="mtv" type="number" value="{{ old('mtv', $giftcode->mtv) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Detail (JSON)</label>
            <textarea class="form-input" name="detail"
                rows="4">{{ old('detail', $giftcode->detail ?? '[]') }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Ngày hết hạn</label>
            <input class="form-input" name="expired" type="date" value="{{ old('expired', $giftcode->expired) }}">
        </div>
        <div class="form-group">
            <label class="form-check"><input type="checkbox" name="active" value="1" {{ old('active', $giftcode->active)
                ? 'checked' : '' }}> Active</label>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
            <a href="{{ route('admin.giftcodes.index') }}" class="btn" style="color:#94a3b8;">Quay lại</a>
        </div>
    </form>

    <hr style="border-color:#334155; margin:24px 0;">
    <form method="POST" action="{{ route('admin.giftcodes.destroy', $giftcode->id) }}"
        onsubmit="return confirm('Xoá giftcode này?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-sm" type="submit">Xoá giftcode</button>
    </form>
</div>
@endsection