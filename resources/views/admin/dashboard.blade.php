@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><span class="material-icons-round">people</span></div>
        <div>
            <div class="stat-title">Tổng tài khoản</div>
            <div class="stat-value">{{ number_format($stats['accounts']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><span class="material-icons-round">receipt_long</span></div>
        <div>
            <div class="stat-title">Tổng giao dịch</div>
            <div class="stat-value">{{ number_format($stats['topups']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><span class="material-icons-round">today</span></div>
        <div>
            <div class="stat-title">Doanh thu hôm nay</div>
            <div class="stat-value" style="color:#13deb9;">{{ number_format($stats['today_revenue']) }}đ</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><span class="material-icons-round">trending_up</span></div>
        <div>
            <div class="stat-title">Doanh thu tháng này</div>
            <div class="stat-value" style="color:#ffae1f;">{{ number_format($stats['month_revenue']) }}đ</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
    <div class="card">
        <div class="card-header">
            <h3><span class="material-icons-round"
                    style="font-size:18px; vertical-align:middle; margin-right:6px; color:#5d87ff;">swap_horiz</span>Giao
                dịch gần đây</h3>
        </div>
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
                        <td style="font-weight:500;">{{ $tx->username }}</td>
                        <td><span class="badge badge-success">{{ number_format($tx->amount) }}đ</span></td>
                        <td>{{ $tx->source ?? $tx->currency }}</td>
                        <td style="color:#5a6a85;">{{ $tx->created_at }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#5a6a85; padding:24px;">Chưa có giao dịch</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><span class="material-icons-round"
                    style="font-size:18px; vertical-align:middle; margin-right:6px; color:#ffae1f;">emoji_events</span>Top
                nạp nhiều nhất</h3>
        </div>
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
                        <td>
                            @if($i < 3) <span class="badge {{ ['badge-warning','badge-info','badge-success'][$i] }}">{{
                                $i + 1 }}</span>
                                @else
                                {{ $i + 1 }}
                                @endif
                        </td>
                        <td style="font-weight:500;">{{ $user->username }}</td>
                        <td><span class="badge badge-warning">{{ number_format($user->total) }}đ</span></td>
                        <td>{{ $user->topup_count }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#5a6a85; padding:24px;">Chưa có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection