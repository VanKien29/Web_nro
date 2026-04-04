@extends('admin.layout')
@section('title', 'Items')

@section('content')
<div class="flex justify-between items-center mb-4">
    <form method="GET" class="flex gap-2">
        <input class="form-input" style="width:200px;" name="search" placeholder="Tìm ID hoặc tên..."
            value="{{ $search }}">
        <select class="form-input" style="width:150px;" name="type">
            <option value="">Tất cả type</option>
            @foreach($types as $t)
            <option value="{{ $t }}" {{ $type==$t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Icon</th>
                    <th>Tên</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->icon_id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->type }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#64748b;">Không có item nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        {{ $items->links('admin.partials.pagination') }}
    </div>
</div>
@endsection