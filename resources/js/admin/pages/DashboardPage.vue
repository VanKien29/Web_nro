<template>
    <div class="dashboard-page">
        <header class="page-top">
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
            <div class="page-meta">
                <span class="meta-pill">
                    <span class="mi">monitoring</span>
                    Bảng điều hành quản trị
                </span>
                <span class="meta-pill muted">
                    <span class="mi">schedule</span>
                    Dữ liệu realtime theo database
                </span>
            </div>
        </header>

        <div v-if="error" class="alert alert-error">
            <span class="mi">error</span>
            {{ error }}
        </div>

        <section class="hero-panel">
            <div class="hero-copy">
                <div class="eyebrow">TỔNG QUAN VẬN HÀNH</div>
                <h3>Nhìn nhanh tình trạng server, nạp, giftcode và shop</h3>
                <p>
                    Một nơi để theo dõi dòng tiền, tăng trưởng tài khoản và các
                    bảng dữ liệu quan trọng của game mà không phải chuyển trang
                    liên tục.
                </p>
            </div>

            <div class="hero-metrics">
                <div class="hero-metric">
                    <span class="hero-label">Doanh thu tháng</span>
                    <strong>{{ fmtCurrency(stats.month_revenue) }}</strong>
                    <span class="hero-note"
                        >{{ fmt(stats.month_topups) }} giao dịch</span
                    >
                </div>
                <div class="hero-metric">
                    <span class="hero-label">Doanh thu hôm nay</span>
                    <strong>{{ fmtCurrency(stats.today_revenue) }}</strong>
                    <span class="hero-note"
                        >{{ fmt(stats.today_topups) }} giao dịch</span
                    >
                </div>
                <div class="hero-metric">
                    <span class="hero-label">Giftcode đang bật</span>
                    <strong>{{ fmt(stats.giftcodes_active) }}</strong>
                    <span class="hero-note"
                        >trên {{ fmt(stats.giftcodes) }} giftcode</span
                    >
                </div>
            </div>
        </section>

        <section class="metric-grid">
            <article
                v-for="card in statCards"
                :key="card.key"
                class="metric-card"
            >
                <div class="metric-icon" :class="card.color">
                    <span class="mi">{{ card.icon }}</span>
                </div>
                <div class="metric-content">
                    <div class="metric-title">{{ card.title }}</div>
                    <div class="metric-value">{{ card.value }}</div>
                    <div class="metric-note">{{ card.subtitle }}</div>
                </div>
            </article>
        </section>

        <section class="dashboard-grid">
            <article class="panel revenue-panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-kicker">DOANH THU</div>
                        <h3>Biểu đồ doanh thu</h3>
                        <p>
                            Theo dõi xu hướng nạp gần đây theo mốc thời gian bạn
                            quan tâm.
                        </p>
                    </div>
                    <div class="range-switch">
                        <button
                            type="button"
                            class="range-btn"
                            :class="{ active: chartRange === '7d' }"
                            @click="chartRange = '7d'"
                        >
                            7 ngày
                        </button>
                        <button
                            type="button"
                            class="range-btn"
                            :class="{ active: chartRange === '30d' }"
                            @click="chartRange = '30d'"
                        >
                            30 ngày
                        </button>
                    </div>
                </div>

                <div class="revenue-summary">
                    <div class="summary-box">
                        <span>Tổng doanh thu</span>
                        <strong>{{ fmtCurrency(chartTotal) }}</strong>
                    </div>
                    <div class="summary-box">
                        <span>Trung bình / ngày</span>
                        <strong>{{ fmtCurrency(chartAverage) }}</strong>
                    </div>
                    <div class="summary-box">
                        <span>Tổng giao dịch</span>
                        <strong>{{ fmt(chartCountTotal) }}</strong>
                    </div>
                    <div class="summary-box">
                        <span>Đỉnh cao nhất</span>
                        <strong>{{ fmtCurrency(chartPeak) }}</strong>
                    </div>
                </div>

                <div class="chart-shell">
                    <div class="chart-yaxis">
                        <span
                            v-for="label in yAxisLabels"
                            :key="label"
                            >{{ fmtCurrency(label) }}</span
                        >
                    </div>

                    <div class="chart-canvas">
                        <div class="chart-grid-lines">
                            <span
                                v-for="index in 4"
                                :key="index"
                                class="grid-line"
                            ></span>
                        </div>

                        <svg
                            class="chart-svg"
                            viewBox="0 0 860 320"
                            preserveAspectRatio="none"
                        >
                            <defs>
                                <linearGradient
                                    id="revenueArea"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="rgba(75, 158, 139, 0.34)"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="rgba(75, 158, 139, 0.02)"
                                    />
                                </linearGradient>
                            </defs>
                            <path
                                :d="chartAreaPath"
                                fill="url(#revenueArea)"
                                :class="{ 'chart-area-empty': !hasChartData }"
                            />
                            <path
                                :d="chartPath"
                                class="chart-line"
                                :class="{ 'chart-line-empty': !hasChartData }"
                                fill="none"
                            />
                            <circle
                                v-for="point in chartPoints"
                                :key="point.key"
                                :cx="point.x"
                                :cy="point.y"
                                r="4"
                                class="chart-dot"
                                :class="{ 'chart-dot-empty': !hasChartData }"
                            />
                        </svg>

                        <div class="chart-xaxis">
                            <span
                                v-for="label in xAxisLabels"
                                :key="label.key"
                                :style="{ left: label.left }"
                            >
                                {{ label.text }}
                            </span>
                        </div>

                        <div v-if="!hasChartData" class="chart-overlay-note">
                            Chưa có giao dịch trong giai đoạn này
                        </div>
                    </div>
                </div>
            </article>

            <article class="panel source-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">NGUỒN NẠP</div>
                        <h3>Cơ cấu doanh thu</h3>
                    </div>
                </div>

                <div class="source-chart-layout">
                    <div class="source-donut-wrap">
                        <svg
                            class="source-donut"
                            viewBox="0 0 220 220"
                            aria-hidden="true"
                        >
                            <circle
                                class="source-donut-base"
                                cx="110"
                                cy="110"
                                :r="sourceChartRadius"
                            />
                            <circle
                                v-for="segment in sourceChartSegments"
                                :key="segment.key"
                                class="source-donut-segment"
                                cx="110"
                                cy="110"
                                :r="sourceChartRadius"
                                :stroke="segment.color"
                                :stroke-dasharray="segment.dasharray"
                                :stroke-dashoffset="segment.dashoffset"
                            />
                        </svg>
                        <div class="source-donut-center">
                            <span class="source-donut-label">Tổng 30 ngày</span>
                            <strong>{{ fmtCurrency(sourceRevenueTotal) }}</strong>
                            <span class="source-donut-note">{{
                                fmt(sourceRevenueCount)
                            }}
                                giao dịch</span>
                        </div>
                    </div>

                    <div class="source-legend">
                        <div
                            v-for="(row, index) in sourceChartData"
                            :key="row.source"
                            class="source-legend-row"
                        >
                            <div class="source-legend-left">
                                <span
                                    class="source-legend-dot"
                                    :style="{ background: row.color }"
                                ></span>
                                <div>
                                    <div class="source-legend-title">
                                        {{ row.source }}
                                    </div>
                                    <div class="source-legend-subtitle">
                                        {{ row.count }} giao dịch
                                    </div>
                                </div>
                            </div>
                            <div class="source-legend-right">
                                <strong>{{ row.percent }}%</strong>
                                <span>{{ fmtCurrency(row.total) }}</span>
                            </div>
                        </div>

                        <div
                            v-if="!sourceRevenue.length"
                            class="source-legend-empty"
                        >
                            Chưa có giao dịch nguồn nạp, biểu đồ đang hiển thị
                            trạng thái nền.
                        </div>
                    </div>
                </div>
            </article>

            <article class="panel system-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">HỆ THỐNG</div>
                        <h3>Tổng quan dữ liệu</h3>
                    </div>
                </div>

                <div class="system-list">
                    <div
                        v-for="row in tables.system_summary"
                        :key="row.label"
                        class="system-row"
                    >
                        <div>
                            <div class="system-label">{{ row.label }}</div>
                            <div class="system-hint">{{ row.hint }}</div>
                        </div>
                        <div class="system-value">
                            {{
                                typeof row.count === "number"
                                    ? fmt(row.count)
                                    : row.count
                            }}
                        </div>
                    </div>
                </div>
            </article>

            <article class="panel table-panel recent-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">GIAO DỊCH</div>
                        <h3>Giao dịch gần đây</h3>
                    </div>
                    <span class="panel-badge">{{
                        tables.recent_transactions.length
                    }}</span>
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
                            <tr
                                v-for="tx in tables.recent_transactions"
                                :key="tx.trans_id"
                            >
                                <td class="cell-strong">{{ tx.username }}</td>
                                <td>{{ fmtCurrency(tx.amount) }}</td>
                                <td>{{ tx.source || tx.currency || "-" }}</td>
                                <td class="cell-muted">
                                    {{ tx.created_at || "-" }}
                                </td>
                            </tr>
                            <tr v-if="!tables.recent_transactions.length">
                                <td colspan="4" class="empty-cell">
                                    Chưa có giao dịch
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel table-panel topup-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">XẾP HẠNG</div>
                        <h3>Top nạp</h3>
                    </div>
                    <span class="panel-badge">{{ tables.top_users.length }}</span>
                </div>

                <div class="leader-list">
                    <div
                        v-for="(user, index) in tables.top_users"
                        :key="user.username"
                        class="leader-row"
                    >
                        <div class="leader-left">
                            <span class="leader-index">{{ index + 1 }}</span>
                            <div>
                                <div class="leader-name">
                                    {{ user.username }}
                                </div>
                                <div class="leader-meta">
                                    {{ fmt(user.count) }} giao dịch
                                </div>
                            </div>
                        </div>
                        <div class="leader-total">
                            {{ fmtCurrency(user.total) }}
                        </div>
                    </div>

                    <div v-if="!tables.top_users.length" class="panel-empty">
                        Chưa có dữ liệu top nạp
                    </div>
                </div>
            </article>

            <article class="panel table-panel account-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">TÀI KHOẢN</div>
                        <h3>Tài khoản mới / gần đây</h3>
                    </div>
                    <span class="panel-badge">{{
                        tables.recent_accounts.length
                    }}</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nhân vật</th>
                                <th>Trạng thái</th>
                                <th>Tạo lúc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in tables.recent_accounts"
                                :key="row.id"
                            >
                                <td class="cell-strong">{{ row.username }}</td>
                                <td>{{ row.player_name || "-" }}</td>
                                <td>
                                    <span
                                        class="state-dot"
                                        :class="{
                                            danger: row.ban,
                                            success: !row.ban && row.active,
                                            muted: !row.ban && !row.active,
                                        }"
                                    ></span>
                                    {{
                                        row.ban
                                            ? "Bị khóa"
                                            : row.active
                                              ? "Đang bật"
                                              : "Chưa bật"
                                    }}
                                </td>
                                <td class="cell-muted">
                                    {{ row.create_time || "-" }}
                                </td>
                            </tr>
                            <tr v-if="!tables.recent_accounts.length">
                                <td colspan="4" class="empty-cell">
                                    Chưa có dữ liệu
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel table-panel milestone-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">MỐC THƯỞNG</div>
                        <h3>Phân bố theo bảng</h3>
                    </div>
                    <span class="panel-badge">{{ fmt(stats.milestones) }}</span>
                </div>

                <div class="milestone-list">
                    <div
                        v-for="row in tables.milestone_breakdown"
                        :key="row.table"
                        class="milestone-row"
                    >
                        <div class="milestone-name">{{ row.label }}</div>
                        <div class="milestone-count">
                            {{ fmt(row.count) }}
                        </div>
                    </div>

                    <div
                        v-if="!tables.milestone_breakdown.length"
                        class="panel-empty"
                    >
                        Chưa có dữ liệu mốc thưởng
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loading: false,
            error: "",
            chartRange: "30d",
            stats: {
                accounts: 0,
                players: 0,
                topups: 0,
                items: 0,
                giftcodes: 0,
                giftcodes_active: 0,
                shops: 0,
                shop_tabs: 0,
                milestones: 0,
                today_revenue: 0,
                today_topups: 0,
                month_revenue: 0,
                month_topups: 0,
            },
            charts: {
                revenue_7d: [],
                revenue_30d: [],
                source_revenue_30d: [],
            },
            tables: {
                recent_transactions: [],
                top_users: [],
                recent_accounts: [],
                system_summary: [],
                milestone_breakdown: [],
            },
        };
    },
    computed: {
        statCards() {
            return [
                {
                    key: "accounts",
                    title: "Tài khoản",
                    value: this.fmt(this.stats.accounts),
                    subtitle: `${this.fmt(this.stats.players)} nhân vật`,
                    icon: "people",
                    color: "primary",
                },
                {
                    key: "topups",
                    title: "Giao dịch nạp",
                    value: this.fmt(this.stats.topups),
                    subtitle: `${this.fmt(this.stats.today_topups)} hôm nay`,
                    icon: "receipt_long",
                    color: "info",
                },
                {
                    key: "items",
                    title: "Vật phẩm",
                    value: this.fmt(this.stats.items),
                    subtitle: `${this.fmt(this.stats.shops)} shop / ${this.fmt(this.stats.shop_tabs)} tab`,
                    icon: "inventory_2",
                    color: "primary",
                },
                {
                    key: "giftcodes",
                    title: "Giftcode",
                    value: this.fmt(this.stats.giftcodes),
                    subtitle: `${this.fmt(this.stats.giftcodes_active)} đang bật`,
                    icon: "card_giftcard",
                    color: "warning",
                },
                {
                    key: "milestones",
                    title: "Mốc thưởng",
                    value: this.fmt(this.stats.milestones),
                    subtitle: "4 bảng quản trị",
                    icon: "emoji_events",
                    color: "warning",
                },
                {
                    key: "today",
                    title: "Doanh thu hôm nay",
                    value: this.fmtCurrency(this.stats.today_revenue),
                    subtitle: `${this.fmtCurrency(this.stats.month_revenue)} tháng này`,
                    icon: "payments",
                    color: "success",
                },
            ];
        },
        sourceRevenue() {
            return this.charts.source_revenue_30d || [];
        },
        sourceRevenueTotal() {
            return this.sourceRevenue.reduce(
                (sum, item) => sum + Number(item.total || 0),
                0,
            );
        },
        sourceRevenueCount() {
            return this.sourceRevenue.reduce(
                (sum, item) => sum + Number(item.count || 0),
                0,
            );
        },
        sourceChartData() {
            const palette = [
                "#4b9e8b",
                "#4aa8b4",
                "#d5a042",
                "#58ac74",
                "#d05c5c",
                "#8b78d6",
            ];
            const total = this.sourceRevenueTotal;
            if (!this.sourceRevenue.length) {
                return [
                    {
                        key: "empty",
                        source: "Chưa phát sinh",
                        total: 0,
                        count: 0,
                        percent: 100,
                        color: "rgba(94, 116, 136, 0.65)",
                    },
                ];
            }

            return this.sourceRevenue.map((item, index) => {
                const amount = Number(item.total || 0);
                return {
                    key: `${item.source}-${index}`,
                    source: item.source || "Khác",
                    total: amount,
                    count: Number(item.count || 0),
                    percent:
                        total > 0
                            ? Number(((amount / total) * 100).toFixed(1))
                            : 0,
                    color: palette[index % palette.length],
                };
            });
        },
        sourceChartRadius() {
            return 72;
        },
        sourceChartCircumference() {
            return 2 * Math.PI * this.sourceChartRadius;
        },
        sourceChartSegments() {
            const circumference = this.sourceChartCircumference;
            let progress = 0;

            return this.sourceChartData.map((item) => {
                const fraction = Math.max(
                    0,
                    Math.min(1, Number(item.percent || 0) / 100),
                );
                const length = circumference * fraction;
                const segment = {
                    key: item.key,
                    color: item.color,
                    dasharray: `${length} ${Math.max(circumference - length, 0)}`,
                    dashoffset: -progress,
                };
                progress += length;
                return segment;
            });
        },
        activeSeries() {
            return this.chartRange === "7d"
                ? this.charts.revenue_7d || []
                : this.charts.revenue_30d || [];
        },
        hasChartData() {
            return this.activeSeries.some((item) => Number(item.total || 0) > 0);
        },
        chartTotal() {
            return this.activeSeries.reduce(
                (sum, item) => sum + Number(item.total || 0),
                0,
            );
        },
        chartCountTotal() {
            return this.activeSeries.reduce(
                (sum, item) => sum + Number(item.count || 0),
                0,
            );
        },
        chartAverage() {
            if (!this.activeSeries.length) return 0;
            return Math.round(this.chartTotal / this.activeSeries.length);
        },
        chartPeak() {
            return Math.max(
                0,
                ...this.activeSeries.map((item) => Number(item.total || 0)),
            );
        },
        chartMax() {
            return Math.max(1, this.chartPeak);
        },
        yAxisLabels() {
            return [
                this.chartMax,
                Math.round(this.chartMax * 0.66),
                Math.round(this.chartMax * 0.33),
                0,
            ];
        },
        chartPoints() {
            const list = this.activeSeries;
            if (!list.length) return [];

            const width = 860;
            const height = 320;
            const left = 10;
            const right = 10;
            const top = 16;
            const bottom = 26;
            const usableWidth = width - left - right;
            const usableHeight = height - top - bottom;

            return list.map((item, index) => {
                const x =
                    left +
                    (usableWidth * index) / Math.max(1, list.length - 1);
                const y =
                    top +
                    usableHeight -
                    (Number(item.total || 0) / this.chartMax) * usableHeight;

                return {
                    key: item.date || `${this.chartRange}-${index}`,
                    x,
                    y,
                    label: item.label,
                    total: Number(item.total || 0),
                };
            });
        },
        chartPath() {
            if (!this.chartPoints.length) return "";
            return this.chartPoints
                .map((point, index) => {
                    const prefix = index === 0 ? "M" : "L";
                    return `${prefix}${point.x},${point.y}`;
                })
                .join(" ");
        },
        chartAreaPath() {
            if (!this.chartPoints.length) return "";
            const first = this.chartPoints[0];
            const last = this.chartPoints[this.chartPoints.length - 1];
            return [
                `M${first.x},294`,
                `L${first.x},${first.y}`,
                ...this.chartPoints.slice(1).map((point) => {
                    return `L${point.x},${point.y}`;
                }),
                `L${last.x},294`,
                "Z",
            ].join(" ");
        },
        xAxisLabels() {
            const list = this.activeSeries;
            if (!list.length) return [];

            const indexes =
                this.chartRange === "7d"
                    ? list.map((_, index) => index)
                    : [0, 7, 14, 21, list.length - 1];

            const uniqueIndexes = [...new Set(indexes)].filter(
                (index) => index >= 0 && index < list.length,
            );

            return uniqueIndexes.map((index) => ({
                key: `${this.chartRange}-${index}`,
                text: list[index]?.label || "-",
                left: `${(index / Math.max(1, list.length - 1)) * 100}%`,
            }));
        },
    },
    created() {
        this.load();
    },
    methods: {
        fmt(value) {
            return Number(value || 0).toLocaleString("vi-VN");
        },
        fmtCurrency(value) {
            return `${this.fmt(value)}đ`;
        },
        async load() {
            this.loading = true;
            this.error = "";
            try {
                const res = await fetch("/admin/api/dashboard/overview", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không thể tải dashboard");
                }
                this.stats = { ...this.stats, ...(data.stats || {}) };
                this.charts = { ...this.charts, ...(data.charts || {}) };
                this.tables = { ...this.tables, ...(data.tables || {}) };
            } catch (error) {
                this.error =
                    error?.message ||
                    "Không thể tải dữ liệu dashboard hiện tại.";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style>
.dashboard-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    --dash-panel-bg: #151f28;
    --dash-panel-soft: #111921;
    --dash-panel-soft-2: rgba(9, 13, 17, 0.26);
    --dash-hero-bg:
        linear-gradient(
            140deg,
            rgba(var(--ds-primary-rgb), 0.18),
            rgba(var(--ds-info-rgb), 0.06)
        ),
        #16212a;
    --dash-border: rgba(var(--ds-primary-rgb), 0.08);
    --dash-border-strong: rgba(var(--ds-primary-rgb), 0.16);
    --dash-border-hover: rgba(var(--ds-primary-rgb), 0.26);
    --dash-grid-line: rgba(255, 255, 255, 0.06);
    --dash-track: rgba(255, 255, 255, 0.04);
    --dash-ring-base: rgba(255, 255, 255, 0.06);
    --dash-overlay: rgba(11, 17, 23, 0.72);
    --dash-dot-stroke: rgba(15, 20, 24, 0.9);
}

.admin-app.theme-light .dashboard-page {
    --dash-panel-bg: #f1f4f6;
    --dash-panel-soft: #e8edf1;
    --dash-panel-soft-2: rgba(232, 237, 241, 0.94);
    --dash-hero-bg:
        linear-gradient(
            140deg,
            rgba(var(--ds-primary-rgb), 0.1),
            rgba(var(--ds-info-rgb), 0.04)
        ),
        #eef2f5;
    --dash-border: rgba(86, 105, 123, 0.16);
    --dash-border-strong: rgba(var(--ds-primary-rgb), 0.16);
    --dash-border-hover: rgba(var(--ds-primary-rgb), 0.22);
    --dash-grid-line: rgba(86, 105, 123, 0.14);
    --dash-track: rgba(86, 105, 123, 0.1);
    --dash-ring-base: rgba(86, 105, 123, 0.16);
    --dash-overlay: rgba(241, 244, 246, 0.94);
    --dash-dot-stroke: rgba(241, 244, 246, 0.96);
}

.dashboard-page .page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.dashboard-page .page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 4px;
}

.dashboard-page .breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.dashboard-page .breadcrumb a {
    color: var(--ds-text-muted);
}

.dashboard-page .breadcrumb a:hover {
    color: var(--ds-primary);
}

.dashboard-page .breadcrumb .sep {
    color: var(--ds-gray-300);
}

.dashboard-page .breadcrumb .current {
    color: var(--ds-text);
}

.dashboard-page .page-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.dashboard-page .meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.18);
    background: rgba(var(--ds-primary-rgb), 0.08);
    color: var(--ds-text-emphasis);
    font-size: 13px;
    font-weight: 600;
}

.dashboard-page .meta-pill.muted {
    border-color: var(--ds-border);
    background: var(--ds-gray-100);
    color: var(--ds-text-muted);
}

.dashboard-page .hero-panel {
    display: grid;
    grid-template-columns: minmax(320px, 1.5fr) minmax(320px, 1fr);
    gap: 20px;
    padding: 26px 28px;
    border-radius: 20px;
    background: var(--dash-hero-bg);
    border: 1px solid var(--dash-border-strong);
    box-shadow: var(--ds-shadow-xl);
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.dashboard-page .hero-panel:hover,
.dashboard-page .metric-card:hover,
.dashboard-page .panel:hover {
    transform: translateY(-2px);
    border-color: var(--dash-border-hover);
    box-shadow:
        0 0 0 1px rgba(var(--ds-primary-rgb), 0.05),
        0 18px 38px -18px rgba(0, 0, 0, 0.45);
}

.dashboard-page .eyebrow,
.dashboard-page .panel-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    color: var(--ds-primary-lighter);
    margin-bottom: 10px;
}

.dashboard-page .hero-copy h3 {
    font-size: 24px;
    line-height: 1.25;
    color: var(--ds-text-emphasis);
    margin-bottom: 10px;
}

.dashboard-page .hero-copy p {
    max-width: 720px;
    color: var(--ds-text-muted);
    line-height: 1.6;
}

.dashboard-page .hero-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.dashboard-page .hero-metric {
    min-height: 124px;
    padding: 18px;
    border-radius: 16px;
    background: var(--dash-panel-soft-2);
    border: 1px solid var(--dash-border);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.dashboard-page .hero-label {
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .hero-metric strong {
    display: block;
    margin-top: 8px;
    font-size: 24px;
    color: var(--ds-text-emphasis);
}

.dashboard-page .hero-note {
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.dashboard-page .metric-card {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 118px;
    padding: 18px 20px;
    border-radius: 18px;
    background: var(--dash-panel-bg);
    border: 1px solid var(--dash-border);
    box-shadow: var(--ds-shadow-xl);
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.dashboard-page .metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dashboard-page .metric-icon.primary {
    background: rgba(var(--ds-primary-rgb), 0.14);
    color: var(--ds-primary);
}

.dashboard-page .metric-icon.info {
    background: rgba(var(--ds-info-rgb), 0.14);
    color: var(--ds-info);
}

.dashboard-page .metric-icon.success {
    background: rgba(var(--ds-success-rgb), 0.14);
    color: var(--ds-success);
}

.dashboard-page .metric-icon.warning {
    background: rgba(var(--ds-warning-rgb), 0.14);
    color: var(--ds-warning);
}

.dashboard-page .metric-content {
    min-width: 0;
}

.dashboard-page .metric-title {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-bottom: 4px;
}

.dashboard-page .metric-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.15;
    color: var(--ds-text-emphasis);
}

.dashboard-page .metric-note {
    margin-top: 6px;
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 20px;
}

.dashboard-page .panel {
    padding: 22px;
    border-radius: 20px;
    background: var(--dash-panel-bg);
    border: 1px solid var(--dash-border);
    box-shadow: var(--ds-shadow-xl);
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.dashboard-page .revenue-panel {
    grid-column: span 8;
}

.dashboard-page .source-panel,
.dashboard-page .system-panel {
    grid-column: span 4;
}

.dashboard-page .source-chart-layout {
    display: grid;
    grid-template-columns: minmax(200px, 220px) minmax(0, 1fr);
    gap: 18px;
    align-items: center;
}

.dashboard-page .source-donut-wrap {
    position: relative;
    width: 220px;
    height: 220px;
    margin: 0 auto;
}

.dashboard-page .source-donut {
    width: 220px;
    height: 220px;
    display: block;
    transform: rotate(-90deg);
}

.dashboard-page .source-donut-base,
.dashboard-page .source-donut-segment {
    fill: none;
    stroke-width: 18;
}

.dashboard-page .source-donut-base {
    stroke: var(--dash-ring-base);
}

.dashboard-page .source-donut-segment {
    stroke-linecap: butt;
    transition:
        stroke-dasharray 0.25s ease,
        stroke-dashoffset 0.25s ease;
}

.dashboard-page .source-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    pointer-events: none;
}

.dashboard-page .source-donut-label {
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .source-donut-center strong {
    margin-top: 6px;
    font-size: 26px;
    line-height: 1.1;
    color: var(--ds-text-emphasis);
}

.dashboard-page .source-donut-note {
    margin-top: 8px;
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .source-legend {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dashboard-page .source-legend-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 14px;
    background: var(--dash-panel-soft);
    border: 1px solid var(--dash-border);
}

.dashboard-page .source-legend-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.dashboard-page .source-legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dashboard-page .source-legend-title {
    color: var(--ds-text-emphasis);
    font-weight: 600;
}

.dashboard-page .source-legend-subtitle {
    margin-top: 4px;
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .source-legend-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.dashboard-page .source-legend-right strong {
    color: var(--ds-text-emphasis);
    font-size: 16px;
}

.dashboard-page .source-legend-right span {
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .source-legend-empty {
    padding: 10px 4px 0;
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .recent-panel {
    grid-column: span 6;
}

.dashboard-page .topup-panel {
    grid-column: span 3;
}

.dashboard-page .account-panel {
    grid-column: span 6;
}

.dashboard-page .milestone-panel {
    grid-column: span 3;
}

.dashboard-page .panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}

.dashboard-page .panel-head.compact {
    margin-bottom: 16px;
}

.dashboard-page .panel-head h3 {
    font-size: 18px;
    color: var(--ds-text-emphasis);
    margin-bottom: 4px;
}

.dashboard-page .panel-head p {
    font-size: 13px;
    color: var(--ds-text-muted);
    line-height: 1.55;
}

.dashboard-page .range-switch {
    display: inline-flex;
    padding: 4px;
    gap: 4px;
    border-radius: 12px;
    background: var(--ds-gray-100);
    border: 1px solid var(--ds-border);
}

.dashboard-page .range-btn {
    min-width: 84px;
    height: 34px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: var(--ds-text-muted);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background 0.2s ease,
        color 0.2s ease,
        transform 0.2s ease;
}

.dashboard-page .range-btn:hover {
    color: var(--ds-text-emphasis);
}

.dashboard-page .range-btn.active {
    background: rgba(var(--ds-primary-rgb), 0.18);
    color: var(--ds-text-emphasis);
}

.dashboard-page .revenue-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.dashboard-page .summary-box {
    padding: 14px 16px;
    border-radius: 14px;
    background: var(--dash-panel-soft);
    border: 1px solid var(--dash-border);
}

.dashboard-page .summary-box span {
    display: block;
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-bottom: 8px;
}

.dashboard-page .summary-box strong {
    font-size: 20px;
    color: var(--ds-text-emphasis);
}

.dashboard-page .chart-shell {
    min-height: 368px;
    position: relative;
    display: flex;
    align-items: stretch;
    gap: 14px;
    padding: 14px 0 2px;
}

.dashboard-page .chart-yaxis {
    width: 96px;
    padding: 6px 0 26px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .chart-canvas {
    position: relative;
    flex: 1;
    min-height: 320px;
    border-radius: 16px;
    border: 1px solid var(--dash-border);
    background:
        linear-gradient(
            180deg,
            rgba(var(--ds-primary-rgb), 0.06),
            rgba(var(--ds-primary-rgb), 0.01)
        ),
        var(--dash-panel-soft);
    overflow: hidden;
}

.dashboard-page .chart-grid-lines {
    position: absolute;
    inset: 0 0 28px 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 18px 0 8px;
    pointer-events: none;
}

.dashboard-page .grid-line {
    border-top: 1px dashed var(--dash-grid-line);
}

.dashboard-page .chart-svg {
    display: block;
    width: 100%;
    height: 320px;
}

.dashboard-page .chart-line {
    stroke: var(--ds-primary);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.dashboard-page .chart-dot {
    fill: var(--ds-primary);
    stroke: var(--dash-dot-stroke);
    stroke-width: 2;
}

.dashboard-page .chart-line-empty {
    stroke: rgba(var(--ds-primary-rgb), 0.55);
}

.dashboard-page .chart-dot-empty {
    fill: rgba(var(--ds-primary-rgb), 0.78);
}

.dashboard-page .chart-area-empty {
    opacity: 0.45;
}

.dashboard-page .chart-xaxis {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 8px;
    height: 20px;
    pointer-events: none;
}

.dashboard-page .chart-xaxis span {
    position: absolute;
    transform: translateX(-50%);
    font-size: 12px;
    color: var(--ds-text-muted);
    white-space: nowrap;
}

.dashboard-page .chart-overlay-note {
    position: absolute;
    top: 14px;
    right: 14px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 600;
    color: var(--ds-text-muted);
    background: var(--dash-overlay);
    border: 1px solid var(--dash-border);
}

.dashboard-page .rank-list,
.dashboard-page .leader-list,
.dashboard-page .milestone-list,
.dashboard-page .system-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dashboard-page .rank-item,
.dashboard-page .system-row,
.dashboard-page .leader-row,
.dashboard-page .milestone-row {
    padding: 14px 16px;
    border-radius: 14px;
    background: var(--dash-panel-soft);
    border: 1px solid var(--dash-border);
}

.dashboard-page .rank-top,
.dashboard-page .leader-row,
.dashboard-page .milestone-row,
.dashboard-page .system-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.dashboard-page .rank-title-wrap,
.dashboard-page .leader-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.dashboard-page .rank-index,
.dashboard-page .leader-index {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-text-emphasis);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.dashboard-page .rank-title,
.dashboard-page .leader-name,
.dashboard-page .system-label,
.dashboard-page .milestone-name {
    color: var(--ds-text-emphasis);
    font-weight: 600;
}

.dashboard-page .rank-subtitle,
.dashboard-page .leader-meta,
.dashboard-page .system-hint {
    margin-top: 4px;
    font-size: 12px;
    color: var(--ds-text-muted);
}

.dashboard-page .rank-value,
.dashboard-page .leader-total,
.dashboard-page .system-value,
.dashboard-page .milestone-count {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}

.dashboard-page .rank-track {
    margin-top: 10px;
    height: 8px;
    border-radius: 999px;
    overflow: hidden;
    background: var(--dash-track);
}

.dashboard-page .rank-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(
        90deg,
        rgba(var(--ds-primary-rgb), 0.95),
        rgba(var(--ds-info-rgb), 0.95)
    );
}

.dashboard-page .panel-badge {
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-text-emphasis);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.dashboard-page .panel-empty,
.dashboard-page .empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 24px;
}

.dashboard-page .table-wrap {
    overflow-x: auto;
}

.dashboard-page table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-page thead {
    background: transparent;
}

.dashboard-page th {
    padding: 0 0 12px;
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ds-text-muted);
    border-bottom: 1px solid var(--dash-border);
}

.dashboard-page td {
    padding: 14px 0;
    border-bottom: 1px dashed var(--dash-grid-line);
    color: var(--ds-text);
    font-size: 14px;
}

.dashboard-page tbody tr:last-child td {
    border-bottom: 0;
}

.dashboard-page tbody td:not(:first-child),
.dashboard-page thead th:not(:first-child) {
    padding-left: 14px;
}

.dashboard-page .cell-strong {
    color: var(--ds-text-emphasis);
    font-weight: 600;
}

.dashboard-page .cell-muted {
    color: var(--ds-text-muted);
}

.dashboard-page .state-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
    vertical-align: middle;
    background: var(--ds-gray-400);
}

.dashboard-page .state-dot.success {
    background: var(--ds-success);
}

.dashboard-page .state-dot.danger {
    background: var(--ds-danger);
}

.dashboard-page .state-dot.muted {
    background: var(--ds-gray-400);
}

@media (max-width: 1400px) {
    .dashboard-page .revenue-panel {
        grid-column: span 12;
    }

    .dashboard-page .source-panel,
    .dashboard-page .system-panel,
    .dashboard-page .recent-panel,
    .dashboard-page .topup-panel,
    .dashboard-page .account-panel,
    .dashboard-page .milestone-panel {
        grid-column: span 6;
    }
}

@media (max-width: 1100px) {
    .dashboard-page .hero-panel {
        grid-template-columns: 1fr;
    }

    .dashboard-page .hero-metrics {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .dashboard-page .revenue-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-page .source-chart-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .dashboard-page .source-panel,
    .dashboard-page .system-panel,
    .dashboard-page .recent-panel,
    .dashboard-page .topup-panel,
    .dashboard-page .account-panel,
    .dashboard-page .milestone-panel {
        grid-column: span 12;
    }

    .dashboard-page .hero-metrics {
        grid-template-columns: 1fr;
    }

    .dashboard-page .chart-shell {
        flex-direction: column;
    }

    .dashboard-page .chart-yaxis {
        width: 100%;
        padding: 0;
        flex-direction: row;
        justify-content: space-between;
    }
}

@media (max-width: 700px) {
    .dashboard-page .page-meta {
        width: 100%;
        justify-content: flex-start;
    }

    .dashboard-page .revenue-summary {
        grid-template-columns: 1fr;
    }

    .dashboard-page .metric-value {
        font-size: 24px;
    }

    .dashboard-page .hero-copy h3 {
        font-size: 20px;
    }

    .dashboard-page .panel {
        padding: 18px;
    }
}
</style>
