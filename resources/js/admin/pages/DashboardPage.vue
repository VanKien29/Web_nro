<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">Dashboard</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span class="sep">/</span>
                    <span class="current">Dashboard</span>
                </nav>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <span class="mi">people</span>
                </div>
                <div>
                    <div class="stat-title">Tổng tài khoản</div>
                    <div class="stat-value">{{ fmt(stats.accounts) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <span class="mi">receipt_long</span>
                </div>
                <div>
                    <div class="stat-title">Tổng giao dịch</div>
                    <div class="stat-value">{{ fmt(stats.topups) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <span class="mi">today</span>
                </div>
                <div>
                    <div class="stat-title">Doanh thu hôm nay</div>
                    <div class="stat-value" style="color: var(--ds-success)">
                        {{ fmt(stats.today_revenue) }}đ
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <span class="mi">trending_up</span>
                </div>
                <div>
                    <div class="stat-title">Doanh thu tháng</div>
                    <div class="stat-value" style="color: var(--ds-warning)">
                        {{ fmt(stats.month_revenue) }}đ
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>
                        <span
                            class="mi"
                            style="
                                font-size: 18px;
                                vertical-align: middle;
                                margin-right: 6px;
                                color: var(--ds-info);
                            "
                            >swap_horiz</span
                        >
                        Giao dịch gần đây
                    </h3>
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
                            <tr v-for="tx in latestTopups" :key="tx.trans_id">
                                <td style="font-weight: 500">
                                    {{ tx.username }}
                                </td>
                                <td>
                                    <span class="badge badge-success"
                                        >{{ fmt(tx.amount) }}đ</span
                                    >
                                </td>
                                <td>{{ tx.source || tx.currency }}</td>
                                <td style="color: var(--ds-text-muted)">
                                    {{ tx.created_at }}
                                </td>
                            </tr>
                            <tr v-if="!latestTopups.length">
                                <td colspan="4" class="empty-cell">
                                    Chưa có giao dịch
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>
                        <span
                            class="mi"
                            style="
                                font-size: 18px;
                                vertical-align: middle;
                                margin-right: 6px;
                                color: var(--ds-warning);
                            "
                            >emoji_events</span
                        >
                        Top nạp nhiều nhất
                    </h3>
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
                            <tr v-for="(u, i) in topUsers" :key="u.username">
                                <td>
                                    <span
                                        v-if="i < 3"
                                        class="badge"
                                        :class="
                                            [
                                                'badge-warning',
                                                'badge-info',
                                                'badge-success',
                                            ][i]
                                        "
                                        >{{ i + 1 }}</span
                                    >
                                    <span v-else>{{ i + 1 }}</span>
                                </td>
                                <td style="font-weight: 500">
                                    {{ u.username }}
                                </td>
                                <td>
                                    <span class="badge badge-warning"
                                        >{{ fmt(u.total) }}đ</span
                                    >
                                </td>
                                <td>{{ u.count }}</td>
                            </tr>
                            <tr v-if="!topUsers.length">
                                <td colspan="4" class="empty-cell">
                                    Chưa có dữ liệu
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            stats: {
                accounts: 0,
                topups: 0,
                today_revenue: 0,
                month_revenue: 0,
            },
            latestTopups: [],
            topUsers: [],
        };
    },
    created() {
        this.load();
    },
    methods: {
        fmt(n) {
            return Number(n || 0).toLocaleString("vi-VN");
        },
        async load() {
            const h = { "X-Requested-With": "XMLHttpRequest" };
            try {
                const [statsRes, historyRes, topRes, monthRes] =
                    await Promise.all([
                        fetch("/admin/api/dashboard/stats", { headers: h }),
                        fetch("/admin/api/dashboard/history", { headers: h }),
                        fetch("/admin/api/dashboard/topUsers", { headers: h }),
                        fetch("/admin/api/dashboard/monthRevenue", {
                            headers: h,
                        }),
                    ]);
                const statsData = await statsRes.json();
                const historyData = await historyRes.json();
                const topData = await topRes.json();
                const monthData = await monthRes.json();

                this.stats.accounts = statsData.accounts || 0;
                this.stats.topups = statsData.topups || 0;
                this.stats.month_revenue = monthData.total || 0;

                // today revenue from history
                const today = new Date().toISOString().slice(0, 10);
                const todayTxs = (historyData.data || []).filter(
                    (t) => t.created_at && t.created_at.startsWith(today),
                );
                this.stats.today_revenue = todayTxs.reduce(
                    (s, t) => s + Number(t.amount || 0),
                    0,
                );

                this.latestTopups = (historyData.data || []).slice(0, 10);
                this.topUsers = topData.data || [];
            } catch {
                // ignore
            }
        },
    },
};
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}
.page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 4px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
.breadcrumb a {
    color: var(--ds-text-muted);
}
.breadcrumb a:hover {
    color: var(--ds-primary);
}
.breadcrumb .sep {
    color: var(--ds-gray-300);
}
.breadcrumb .current {
    color: var(--ds-text);
}
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 24px;
}
@media (max-width: 900px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>
