@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="card">
        <div class="card-title">Tổng tài khoản</div>
        <div class="card-value">{{ number_format($stats['accounts']) }}</div>
    </div>
    <div class="card">
        <div class="card-title">Tổng giao dịch</div>
        <div class="card-value">{{ number_format($stats['topups']) }}</div>
    </div>
    <div class="card">
        <div class="card-title">Doanh thu hôm nay</div>
        <div class="card-value" style="color:#34d399;">{{ number_format($stats['today_revenue']) }}đ</div>
    </div>
    <div class="card">
        <div class="card-title">Doanh thu tháng này</div>
        <div class="card-value" style="color:#fbbf24;">{{ number_format($stats['month_revenue']) }}đ</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
    <!-- Latest Topups -->
    <div class="card">
        <h3 style="font-size:16px; margin-bottom:12px;">Giao dịch gần đây</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Số tiền</th>
                        <th>Nguồn</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestTopups as $tx)
                    <tr>
                        <td>{{ $tx->username }}</td>
                        <td>{{ number_format($tx->amount) }}đ</td>
                        <td>{{ $tx->source ?? $tx->currency }}</td>
                        <td>{{ $tx->created_at }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#64748b;">Chưa có giao dịch</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Users -->
    <div class="card">
        <h3 style="font-size:16px; margin-bottom:12px;">Top nạp nhiều nhất</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Tổng nạp</th>
                        <th>Số lần</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topUsers as $i => $user)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ number_format($user->total) }}đ</td>
                        <td>{{ $user->topup_count }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#64748b;">Chưa có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection