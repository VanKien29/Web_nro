@extends('admin.layout')
@section('title', 'Quản lý Giftcode')

@section('content')
<div class="flex justify-between items-center mb-4">
    <form method="GET" class="flex gap-2">
        <input class="form-input" style="width:280px;" name="search" placeholder="Tìm code..." value="{{ $search }}">
        <button class="btn btn-primary btn-sm" type="submit">
            <span class="material-icons-round" style="font-size:16px;">search</span> Tìm
        </button>
    </form>
    <a href="{{ route('admin.giftcodes.create') }}" class="btn btn-primary">
        <span class="material-icons-round" style="font-size:16px;">add</span> Tạo giftcode
    </a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Vật phẩm</th>
                    <th>Còn lại</th>
                    <th>MTV</th>
                    <th>Hết hạn</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($giftcodes as $gc)
                @php
                $detail = json_decode($gc->detail ?? '[]', true) ?: [];
                @endphp
                <tr>
                    <td>{{ $gc->id }}</td>
                    <td><code
                            style="color:#5d87ff; background:rgba(93,135,255,.1); padding:2px 8px; border-radius:4px; font-size:13px;">{{ $gc->code }}</code>
                    </td>
                    <td>
                        <div style="display:flex; gap:4px; flex-wrap:wrap; align-items:center;">
                            @foreach(array_slice($detail, 0, 5) as $item)
                            @php $iconId = $itemIcons[$item['temp_id']] ?? $item['temp_id']; @endphp
                            <img src="/assets/frontend/home/v1/images/x4/{{ $iconId }}.png"
                                alt="item {{ $item['temp_id'] }}"
                                style="width:28px; height:28px; border-radius:4px; background:rgba(255,255,255,.05);"
                                title="ID: {{ $item['temp_id'] }} x{{ $item['quantity'] ?? 1 }}"
                                onerror="this.style.display='none'">
                            @endforeach
                            @if(count($detail) > 5)
                            <span style="color:#5a6a85; font-size:12px;">+{{ count($detail) - 5 }}</span>
                            @endif
                            @if(empty($detail))
                            <span style="color:#5a6a85; font-size:12px;">—</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $gc->count_left }}</td>
                    <td>{{ $gc->mtv ? 'Có' : 'Không' }}</td>
                    <td style="color:#5a6a85;">{{ $gc->expired ?? '—' }}</td>
                    <td>
                        @if($gc->active)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.giftcodes.edit', $gc->id) }}" class="btn btn-primary btn-sm">
                            <span class="material-icons-round" style="font-size:14px;">edit</span> Sửa
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#5a6a85; padding:32px;">Không có giftcode nào.</td>
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