@extends('admin.layout')
@section('title', 'Quản lý tài khoản')

@section('content')
<div class="flex justify-between items-center mb-4">
    <form method="GET" class="flex gap-2">
        <input class="form-input" style="width:250px;" name="search" placeholder="Tìm username..."
            value="{{ $search }}">
        <button class="btn btn-primary btn-sm" type="submit">Tìm</button>
    </form>
    <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">+ Tạo tài khoản</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Ban</th>
                    <th>Admin</th>
                    <th>Active</th>
                    <th>Cash</th>
                    <th>Danap</th>
                    <th>Coin</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                <tr>
                    <td>{{ $acc->id }}</td>
                    <td>{{ $acc->username }}</td>
                    <td>{!! $acc->ban ? '<span style="color:#f87171;">Có</span>' : '—' !!}</td>
                    <td>{!! $acc->is_admin ? '<span style="color:#fbbf24;">✓</span>' : '—' !!}</td>
                    <td>{!! $acc->active ? '<span style="color:#34d399;">✓</span>' : '—' !!}</td>
                    <td>{{ number_format($acc->cash) }}</td>
                    <td>{{ number_format($acc->danap) }}</td>
                    <td>{{ number_format($acc->coin) }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.accounts.edit', $acc) }}" class="btn btn-primary btn-sm">Sửa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:#64748b;">Không có tài khoản nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        {{ $accounts->links('admin.partials.pagination') }}
    </div>
</div>
@endsection