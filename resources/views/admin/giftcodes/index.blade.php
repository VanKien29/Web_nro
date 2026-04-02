@extends('admin.layout')
@section('title', 'Quản lý Giftcode')

@section('content')
<div class="flex justify-between items-center mb-4">
    <form method="GET" class="flex gap-2">
        <input class="form-input" style="width:250px;" name="search" placeholder="Tìm code..." value="{{ $search }}">
        <button class="btn btn-primary btn-sm" type="submit">Tìm</button>
    </form>
    <a href="{{ route('admin.giftcodes.create') }}" class="btn btn-primary">+ Tạo giftcode</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Còn lại</th>
                    <th>MTV</th>
                    <th>Hết hạn</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($giftcodes as $gc)
                <tr>
                    <td>{{ $gc->id }}</td>
                    <td style="font-family:monospace; color:#fbbf24;">{{ $gc->code }}</td>
                    <td>{{ $gc->count_left }}</td>
                    <td>{{ $gc->mtv ? 'Có' : 'Không' }}</td>
                    <td>{{ $gc->expired ?? '—' }}</td>
                    <td>{!! $gc->active ? '<span style="color:#34d399;">✓</span>' : '<span
                            style="color:#f87171;">✗</span>' !!}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.giftcodes.edit', $gc->id) }}" class="btn btn-primary btn-sm">Sửa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#64748b;">Không có giftcode nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        {{ $giftcodes->links('admin.partials.pagination') }}
    </div>
</div>
@endsection