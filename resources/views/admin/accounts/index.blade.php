@extends('admin.layout')
@section('title', 'Quản lý tài khoản')

@section('content')
<div class="flex justify-between items-center mb-4">
    <form method="GET" class="flex gap-2">
        <input class="form-input" style="width:280px;" name="search" placeholder="Tìm username..."
            value="{{ $search }}">
        <button class="btn btn-primary btn-sm" type="submit">
            <span class="material-icons-round" style="font-size:16px;">search</span> Tìm
        </button>
    </form>
    <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">
        <span class="material-icons-round" style="font-size:16px;">person_add</span> Tạo tài khoản
    </a>
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
                    <td style="font-weight:500;">{{ $acc->username }}</td>
                    <td>
                        @if($acc->ban)
                        <span class="badge badge-danger">Có</span>
                        @else
                        <span style="color:#5a6a85;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($acc->is_admin)
                        <span class="badge badge-warning">Admin</span>
                        @else
                        <span style="color:#5a6a85;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($acc->active)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span style="color:#5a6a85;">—</span>
                        @endif
                    </td>
                    <td>{{ number_format($acc->cash) }}</td>
                    <td>{{ number_format($acc->danap) }}</td>
                    <td>{{ number_format($acc->coin) }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.accounts.edit', $acc) }}" class="btn btn-primary btn-sm">
                            <span class="material-icons-round" style="font-size:14px;">edit</span> Sửa
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:#5a6a85; padding:32px;">Không có tài khoản nào.</td>
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