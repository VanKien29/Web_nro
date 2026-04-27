<template>
    <div class="dashboard-page">
        <header class="dash-top">
            <div>
                <h2 class="page-title">Dashboard</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Dashboard</span>
                </nav>
            </div>

            <div class="top-actions">
                <button
                    type="button"
                    class="ghost-chip"
                    :disabled="loading"
                    @click="load"
                >
                    <span class="mi">refresh</span>
                    Làm mới
                </button>
                <div class="ghost-chip muted">
                    <span class="mi">schedule</span>
                    Database realtime
                </div>
            </div>
        </header>

        <div v-if="error" class="alert alert-error">
            <span class="mi">error</span>
            {{ error }}
        </div>
        <!-- 
        <section class="ops-hero">
            <div class="hero-main">
                <div class="hero-kicker">TRUNG TÂM ĐIỀU HÀNH</div>
                <h3>Bảng theo dõi vận hành game và quản trị dữ liệu</h3>
                <p>
                    Tập trung doanh thu, tài khoản, giftcode, shop và các bảng
                    hệ thống quan trọng vào một màn duy nhất để theo dõi nhanh,
                    sửa nhanh và phát hiện bất thường sớm.
                </p>

                <div class="hero-strip">
                    <div class="hero-strip-item">
                        <span>Tài khoản / Nhân vật</span>
                        <strong
                            >{{ fmt(stats.accounts) }} /
                            {{ fmt(stats.players) }}</strong
                        >
                    </div>
                    <div class="hero-strip-item">
                        <span>Nạp tháng này</span>
                        <strong>{{ fmtCurrency(stats.month_revenue) }}</strong>
                    </div>
                    <div class="hero-strip-item">
                        <span>Tỉ lệ giftcode đang bật</span>
                        <strong>{{ giftcodeRate }}%</strong>
                    </div>
                </div>
            </div>

            <div class="hero-side">
                <div class="hero-health">
                    <div class="health-ring">
                        <svg viewBox="0 0 120 120">
                            <circle
                                class="health-ring-base"
                                cx="60"
                                cy="60"
                                r="46"
                            />
                            <circle
                                class="health-ring-value"
                                cx="60"
                                cy="60"
                                r="46"
                                :stroke-dasharray="healthRingDasharray"
                            />
                        </svg>
                        <div class="health-center">
                            <strong>{{ healthScore }}</strong>
                            <span>/ 100</span>
                        </div>
                    </div>
                    <div class="health-copy">
                        <div class="health-title">Chỉ số vận hành</div>
                        <p>
                            Điểm tổng hợp theo doanh thu tháng, giftcode đang
                            bật và tần suất giao dịch hiện tại.
                        </p>
                    </div>
                </div>

                <div class="signal-list">
                    <div class="signal-row">
                        <span class="signal-label">Doanh thu hôm nay</span>
                        <strong>{{ fmtCurrency(stats.today_revenue) }}</strong>
                        <small>{{ fmt(stats.today_topups) }} giao dịch</small>
                    </div>
                    <div class="signal-row">
                        <span class="signal-label">Giftcode</span>
                        <strong>{{ fmt(stats.giftcodes_active) }} bật</strong>
                        <small>trên {{ fmt(stats.giftcodes) }} code</small>
                    </div>
                    <div class="signal-row">
                        <span class="signal-label">Shop / Tab / Item</span>
                        <strong
                            >{{ fmt(stats.shops) }} /
                            {{ fmt(stats.shop_tabs) }} /
                            {{ fmt(stats.items) }}</strong
                        >
                        <small>nguồn dữ liệu vận hành</small>
                    </div>
                </div>
            </div>
        </section> -->

        <section class="kpi-grid">
            <article
                v-for="card in kpiCards"
                :key="card.key"
                class="kpi-card"
                :class="card.tone"
            >
                <div class="kpi-head">
                    <span class="kpi-icon">
                        <span class="mi">{{ card.icon }}</span>
                    </span>
                    <span class="kpi-label">{{ card.title }}</span>
                </div>
                <strong class="kpi-value">{{ card.value }}</strong>
                <small class="kpi-note">{{ card.note }}</small>
            </article>
        </section>

        <section class="dashboard-grid">
            <article class="panel panel-xl revenue-panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-kicker">DOANH THU</div>
                        <h3>Xu hướng dòng tiền</h3>
                        <p>Theo dõi biến động nạp theo ngày và mốc gần nhất.</p>
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

                <div class="mini-kpi-row">
                    <div class="mini-kpi">
                        <span>Tổng doanh thu</span>
                        <strong>{{ fmtCurrency(chartTotal) }}</strong>
                    </div>
                    <div class="mini-kpi">
                        <span>Trung bình / ngày</span>
                        <strong>{{ fmtCurrency(chartAverage) }}</strong>
                    </div>
                    <div class="mini-kpi">
                        <span>Tổng giao dịch</span>
                        <strong>{{ fmt(chartCountTotal) }}</strong>
                    </div>
                    <div class="mini-kpi">
                        <span>Đỉnh cao nhất</span>
                        <strong>{{ fmtCurrency(chartPeak) }}</strong>
                    </div>
                </div>

                <div class="chart-shell">
                    <div class="chart-yaxis">
                        <span v-for="label in yAxisLabels" :key="label">
                            {{ fmtCurrency(label) }}
                        </span>
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
                                        stop-color="rgba(77, 191, 170, 0.34)"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="rgba(77, 191, 170, 0.02)"
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

            <article class="panel panel-lg source-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">NGUỒN NẠP</div>
                        <h3>Cơ cấu doanh thu 30 ngày</h3>
                    </div>
                </div>

                <div class="source-layout">
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
                        <div class="source-center">
                            <span>Tổng 30 ngày</span>
                            <strong>{{
                                fmtCurrency(sourceRevenueTotal)
                            }}</strong>
                            <small
                                >{{ fmt(sourceRevenueCount) }} giao dịch</small
                            >
                        </div>
                    </div>

                    <div class="source-list">
                        <div
                            v-for="row in sourceChartData"
                            :key="row.key"
                            class="source-row"
                        >
                            <div class="source-left">
                                <span
                                    class="source-dot"
                                    :style="{ background: row.color }"
                                ></span>
                                <div>
                                    <strong>{{ row.source }}</strong>
                                    <small
                                        >{{ fmt(row.count) }} giao dịch</small
                                    >
                                </div>
                            </div>
                            <div class="source-right">
                                <strong>{{ row.percent }}%</strong>
                                <small>{{ fmtCurrency(row.total) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- <article class="panel panel-md command-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">LỆNH NHANH</div>
                        <h3>Tín hiệu quản trị</h3>
                    </div>
                </div>
                <div class="signal-stack">
                    <div class="signal-box">
                        <span>Mốc thưởng</span>
                        <strong>{{ fmt(stats.milestones) }}</strong>
                        <small>4 bảng thưởng đang quản lý</small>
                    </div>
                    <div class="signal-box">
                        <span>Topup trung bình</span>
                        <strong>{{ fmtCurrency(avgTopupValue) }}</strong>
                        <small>giá trị / giao dịch tháng này</small>
                    </div>
                    <div class="signal-box">
                        <span>Mật độ shop</span>
                        <strong>{{ shopDensity }}</strong>
                        <small>tab / shop đang có</small>
                    </div>
                </div>
            </article> -->
            <article class="panel panel-md milestone-panel">
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
            <article class="panel panel-lg table-panel transaction-panel">
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

            <article class="panel panel-md leaderboard-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">XẾP HẠNG</div>
                        <h3>Top nạp</h3>
                    </div>
                    <span class="panel-badge">{{
                        tables.top_users.length
                    }}</span>
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

            <article class="panel panel-md account-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">TÀI KHOẢN</div>
                        <h3>Tạo mới gần đây</h3>
                    </div>
                    <span class="panel-badge">{{
                        tables.recent_accounts.length
                    }}</span>
                </div>

                <div class="account-list">
                    <div
                        v-for="row in tables.recent_accounts"
                        :key="row.id"
                        class="account-row"
                    >
                        <div>
                            <strong>{{ row.username }}</strong>
                            <small>{{
                                row.player_name || "Chưa có nhân vật"
                            }}</small>
                        </div>
                        <div class="account-right">
                            <span
                                class="state-dot"
                                :class="{
                                    danger: row.ban,
                                    success: !row.ban && row.active,
                                    muted: !row.ban && !row.active,
                                }"
                            ></span>
                            <small>{{ row.create_time || "-" }}</small>
                        </div>
                    </div>
                    <div
                        v-if="!tables.recent_accounts.length"
                        class="panel-empty"
                    >
                        Chưa có dữ liệu
                    </div>
                </div>
            </article>

            <article class="panel panel-md system-panel">
                <div class="panel-head compact">
                    <div>
                        <div class="panel-kicker">HỆ THỐNG</div>
                        <h3>Tổng quan bảng dữ liệu</h3>
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
        kpiCards() {
            return [
                {
                    key: "accounts",
                    title: "Tài khoản",
                    value: this.fmt(this.stats.accounts),
                    note: `${this.fmt(this.stats.players)} nhân vật`,
                    icon: "people",
                    tone: "tone-primary",
                },
                {
                    key: "topups",
                    title: "Giao dịch nạp",
                    value: this.fmt(this.stats.topups),
                    note: `${this.fmt(this.stats.today_topups)} hôm nay`,
                    icon: "receipt_long",
                    tone: "tone-info",
                },
                {
                    key: "giftcodes",
                    title: "Giftcode",
                    value: this.fmt(this.stats.giftcodes),
                    note: `${this.fmt(this.stats.giftcodes_active)} đang bật`,
                    icon: "redeem",
                    tone: "tone-warning",
                },
                {
                    key: "items",
                    title: "Vật phẩm",
                    value: this.fmt(this.stats.items),
                    note: `${this.fmt(this.stats.shops)} shop / ${this.fmt(this.stats.shop_tabs)} tab`,
                    icon: "inventory_2",
                    tone: "tone-primary",
                },
                {
                    key: "month",
                    title: "Doanh thu tháng",
                    value: this.fmtCurrency(this.stats.month_revenue),
                    note: `${this.fmt(this.stats.month_topups)} giao dịch`,
                    icon: "payments",
                    tone: "tone-success",
                },
                {
                    key: "today",
                    title: "Doanh thu hôm nay",
                    value: this.fmtCurrency(this.stats.today_revenue),
                    note: `TB ${this.fmtCurrency(this.avgTopupValue)}/giao dịch`,
                    icon: "trending_up",
                    tone: "tone-success",
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
                "#4dbfaa",
                "#47a8c6",
                "#d6a24c",
                "#6ac26b",
                "#d66868",
                "#8a77db",
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
                        color: "rgba(111, 129, 148, 0.45)",
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
            return this.activeSeries.some(
                (item) => Number(item.total || 0) > 0,
            );
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
                    left + (usableWidth * index) / Math.max(1, list.length - 1);
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
        giftcodeRate() {
            if (!Number(this.stats.giftcodes)) return 0;
            return Number(
                (
                    (Number(this.stats.giftcodes_active) /
                        Number(this.stats.giftcodes)) *
                    100
                ).toFixed(1),
            );
        },
        avgTopupValue() {
            if (!Number(this.stats.month_topups)) return 0;
            return Math.round(
                Number(this.stats.month_revenue || 0) /
                    Number(this.stats.month_topups || 1),
            );
        },
        shopDensity() {
            if (!Number(this.stats.shops)) return "0.0";
            return (
                Number(this.stats.shop_tabs || 0) /
                Number(this.stats.shops || 1)
            ).toFixed(1);
        },
        healthScore() {
            const revenueScore = Math.min(
                40,
                Math.round(Number(this.stats.month_revenue || 0) / 1000000),
            );
            const activityScore = Math.min(
                30,
                Math.round(Number(this.stats.month_topups || 0) / 2),
            );
            const giftcodeScore = Math.min(
                20,
                Math.round(Number(this.giftcodeRate || 0) / 5),
            );
            const systemScore = Number(this.stats.shops || 0) > 0 ? 10 : 0;
            return Math.max(
                0,
                Math.min(
                    100,
                    revenueScore + activityScore + giftcodeScore + systemScore,
                ),
            );
        },
        healthRingDasharray() {
            const circumference = 2 * Math.PI * 46;
            const filled = (this.healthScore / 100) * circumference;
            return `${filled} ${Math.max(circumference - filled, 0)}`;
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
    --dash-bg: #151f28;
    --dash-bg-soft: #101820;
    --dash-bg-strong: #0c1319;
    --dash-hero:
        radial-gradient(
            circle at top left,
            rgba(78, 184, 160, 0.16),
            transparent 28%
        ),
        radial-gradient(
            circle at right,
            rgba(74, 142, 196, 0.12),
            transparent 24%
        ),
        linear-gradient(180deg, #16222b 0%, #111b22 100%);
    --dash-border: rgba(110, 150, 182, 0.12);
    --dash-border-strong: rgba(86, 199, 173, 0.18);
    --dash-grid: rgba(255, 255, 255, 0.06);
    --dash-shadow: 0 22px 46px -24px rgba(0, 0, 0, 0.62);
    --dash-track: rgba(255, 255, 255, 0.05);
    --dash-muted-bg: rgba(255, 255, 255, 0.03);
}

.admin-app.theme-light .dashboard-page {
    --dash-bg: #eef2f5;
    --dash-bg-soft: #f8fafb;
    --dash-bg-strong: #e5ebef;
    --dash-hero:
        radial-gradient(
            circle at top left,
            rgba(78, 184, 160, 0.1),
            transparent 28%
        ),
        radial-gradient(
            circle at right,
            rgba(74, 142, 196, 0.08),
            transparent 24%
        ),
        linear-gradient(180deg, #edf2f5 0%, #e7edf1 100%);
    --dash-border: rgba(95, 117, 136, 0.14);
    --dash-border-strong: rgba(86, 199, 173, 0.14);
    --dash-grid: rgba(95, 117, 136, 0.12);
    --dash-shadow: 0 18px 36px -28px rgba(23, 37, 49, 0.24);
    --dash-track: rgba(95, 117, 136, 0.08);
    --dash-muted-bg: rgba(95, 117, 136, 0.04);
}

.dashboard-page .page-title {
    margin: 0 0 4px;
    font-size: 22px;
    color: var(--ds-text-emphasis);
}

.dashboard-page .breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--ds-text-muted);
}

.dashboard-page .breadcrumb a {
    color: var(--ds-text-muted);
}

.dashboard-page .breadcrumb .current {
    color: var(--ds-text);
}

.dash-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.ghost-chip {
    min-height: 38px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid var(--dash-border);
    background: var(--dash-bg-soft);
    color: var(--ds-text-emphasis);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
}

.ghost-chip.muted {
    color: var(--ds-text-muted);
}

.alert {
    border-radius: 12px;
    padding: 12px 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.alert-error {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
    border: 1px solid rgba(var(--ds-danger-rgb), 0.22);
}

.ops-hero,
.panel,
.kpi-card {
    border: 1px solid var(--dash-border);
    box-shadow: var(--dash-shadow);
}

.ops-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(360px, 0.85fr);
    gap: 18px;
    background: var(--dash-hero);
    border-radius: 24px;
    padding: 24px;
}

.hero-kicker,
.panel-kicker {
    font-size: 11px;
    letter-spacing: 0.12em;
    color: var(--ds-primary-lighter);
    font-weight: 800;
    margin-bottom: 10px;
}

.hero-main h3 {
    margin: 0 0 10px;
    font-size: 28px;
    line-height: 1.2;
    color: var(--ds-text-emphasis);
    max-width: 760px;
}

.hero-main p,
.panel-head p,
.health-copy p {
    margin: 0;
    color: var(--ds-text-muted);
    line-height: 1.6;
}

.hero-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
}

.hero-strip-item,
.signal-row,
.mini-kpi,
.signal-box,
.leader-row,
.account-row,
.system-row,
.milestone-row,
.source-row {
    border: 1px solid var(--dash-border);
    background: rgba(255, 255, 255, 0.03);
    border-radius: 16px;
}

.hero-strip-item {
    padding: 14px 16px;
}

.hero-strip-item span,
.signal-label,
.mini-kpi span,
.signal-box span {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
}

.hero-strip-item strong,
.signal-row strong,
.mini-kpi strong,
.signal-box strong {
    display: block;
    margin-top: 6px;
    color: var(--ds-text-emphasis);
    font-size: 20px;
}

.hero-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.hero-health {
    display: grid;
    grid-template-columns: 132px minmax(0, 1fr);
    gap: 14px;
    align-items: center;
    padding: 18px;
    border-radius: 20px;
    background: rgba(8, 14, 18, 0.16);
    border: 1px solid var(--dash-border-strong);
}

.admin-app.theme-light .hero-health {
    background: rgba(255, 255, 255, 0.44);
}

.health-ring {
    position: relative;
    width: 120px;
    height: 120px;
}

.health-ring svg {
    width: 120px;
    height: 120px;
    transform: rotate(-90deg);
}

.health-ring-base,
.health-ring-value {
    fill: none;
    stroke-width: 10;
}

.health-ring-base {
    stroke: var(--dash-track);
}

.health-ring-value {
    stroke: var(--ds-primary);
    stroke-linecap: round;
}

.health-center {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: var(--ds-text-emphasis);
}

.health-center strong {
    font-size: 30px;
}

.health-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 8px;
}

.signal-list,
.signal-stack,
.system-list,
.leader-list,
.milestone-list,
.account-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.signal-row {
    padding: 14px 16px;
}

.signal-row small,
.signal-box small,
.leader-meta,
.system-hint,
.account-row small,
.source-row small {
    display: block;
    margin-top: 4px;
    color: var(--ds-text-muted);
    font-size: 12px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 14px;
}

.kpi-card {
    background: var(--dash-bg);
    border-radius: 18px;
    padding: 18px;
}

.kpi-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.kpi-label {
    font-size: 12px;
    color: var(--ds-text-muted);
    font-weight: 700;
}

.kpi-value {
    display: block;
    color: var(--ds-text-emphasis);
    font-size: 28px;
    line-height: 1.15;
}

.kpi-note {
    display: block;
    margin-top: 8px;
    color: var(--ds-text-muted);
    font-size: 12px;
}

.tone-primary .kpi-icon {
    background: rgba(var(--ds-primary-rgb), 0.14);
    color: var(--ds-primary);
}

.tone-info .kpi-icon {
    background: rgba(var(--ds-info-rgb), 0.14);
    color: var(--ds-info);
}

.tone-warning .kpi-icon {
    background: rgba(var(--ds-warning-rgb), 0.14);
    color: var(--ds-warning);
}

.tone-success .kpi-icon {
    background: rgba(var(--ds-success-rgb), 0.14);
    color: var(--ds-success);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 16px;
}

.panel {
    background: var(--dash-bg);
    border-radius: 22px;
    padding: 22px;
}

.panel-xl {
    grid-column: span 8;
}

.panel-lg {
    grid-column: span 4;
}

.panel-md {
    grid-column: span 4;
}

.transaction-panel {
    grid-column: span 8;
}

.panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}

.panel-head h3 {
    margin: 0 0 6px;
    color: var(--ds-text-emphasis);
    font-size: 20px;
}

.panel-badge {
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

.range-switch {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px;
    border-radius: 14px;
    background: var(--dash-bg-strong);
    border: 1px solid var(--dash-border);
}

.range-btn {
    min-height: 34px;
    padding: 0 14px;
    border-radius: 10px;
    background: transparent;
    color: var(--ds-text-muted);
    font-weight: 700;
}

.range-btn.active {
    background: rgba(var(--ds-primary-rgb), 0.18);
    color: var(--ds-text-emphasis);
}

.mini-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.mini-kpi {
    padding: 14px 16px;
}

.chart-shell {
    display: flex;
    gap: 16px;
    align-items: stretch;
}

.chart-yaxis {
    width: 96px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 8px 0 28px;
    color: var(--ds-text-muted);
    font-size: 12px;
}

.chart-canvas {
    position: relative;
    flex: 1;
    min-height: 320px;
    border-radius: 18px;
    border: 1px solid var(--dash-border);
    background: linear-gradient(
        180deg,
        rgba(var(--ds-primary-rgb), 0.03),
        rgba(var(--ds-primary-rgb), 0.01)
    );
    overflow: hidden;
}

.chart-grid-lines {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
}

.grid-line {
    border-top: 1px dashed var(--dash-grid);
}

.chart-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.chart-line {
    stroke: #4dbfaa;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.chart-dot {
    fill: #4dbfaa;
    stroke: var(--dash-bg);
    stroke-width: 2;
}

.chart-line-empty {
    stroke: rgba(77, 191, 170, 0.58);
}

.chart-dot-empty {
    fill: rgba(77, 191, 170, 0.68);
}

.chart-area-empty {
    opacity: 0.42;
}

.chart-xaxis {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 8px;
    height: 20px;
}

.chart-xaxis span {
    position: absolute;
    transform: translateX(-50%);
    font-size: 12px;
    color: var(--ds-text-muted);
    white-space: nowrap;
}

.chart-overlay-note {
    position: absolute;
    top: 14px;
    right: 14px;
    min-height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(8, 13, 17, 0.64);
    border: 1px solid var(--dash-border);
    color: var(--ds-text-muted);
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 700;
}

.admin-app.theme-light .chart-overlay-note {
    background: rgba(255, 255, 255, 0.9);
}

.source-layout {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 16px;
    align-items: center;
}

.source-donut-wrap {
    position: relative;
    width: 220px;
    height: 220px;
    margin: 0 auto;
}

.source-donut {
    width: 220px;
    height: 220px;
    transform: rotate(-90deg);
}

.source-donut-base,
.source-donut-segment {
    fill: none;
    stroke-width: 18;
}

.source-donut-base {
    stroke: var(--dash-track);
}

.source-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.source-center span,
.source-center small {
    color: var(--ds-text-muted);
    font-size: 12px;
}

.source-center strong {
    margin: 6px 0;
    color: var(--ds-text-emphasis);
    font-size: 22px;
}

.source-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.source-row,
.signal-box,
.leader-row,
.account-row,
.system-row,
.milestone-row {
    padding: 14px 16px;
}

.source-row,
.leader-row,
.account-row,
.system-row,
.milestone-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.source-left,
.leader-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.source-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    flex: 0 0 auto;
}

.source-left strong,
.leader-name,
.system-label,
.milestone-name,
.account-row strong {
    color: var(--ds-text-emphasis);
}

.source-right,
.leader-total,
.system-value,
.milestone-count {
    text-align: right;
}

.source-right strong,
.leader-total,
.system-value,
.milestone-count {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}

.leader-index {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-text-emphasis);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex: 0 0 auto;
}

.account-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.state-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--ds-gray-400);
}

.state-dot.success {
    background: var(--ds-success);
}

.state-dot.danger {
    background: var(--ds-danger);
}

.state-dot.muted {
    background: var(--ds-gray-400);
}

.table-wrap {
    overflow-x: auto;
}

.dashboard-page table {
    width: 100%;
    border-collapse: collapse;
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
    border-bottom: 1px dashed var(--dash-grid);
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

.cell-strong {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}

.cell-muted {
    color: var(--ds-text-muted);
}

.panel-empty,
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 24px;
}

@media (max-width: 1480px) {
    .kpi-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .panel-xl,
    .transaction-panel {
        grid-column: span 12;
    }

    .panel-lg,
    .panel-md {
        grid-column: span 6;
    }
}

@media (max-width: 1180px) {
    .ops-hero {
        grid-template-columns: 1fr;
    }

    .source-layout {
        grid-template-columns: 1fr;
    }

    .hero-strip,
    .mini-kpi-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .panel-lg,
    .panel-md {
        grid-column: span 12;
    }

    .chart-shell {
        flex-direction: column;
    }

    .chart-yaxis {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        padding: 0;
    }
}

@media (max-width: 720px) {
    .kpi-grid,
    .hero-strip,
    .mini-kpi-row {
        grid-template-columns: 1fr;
    }

    .hero-health {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
    }

    .top-actions {
        width: 100%;
    }

    .panel,
    .ops-hero {
        padding: 18px;
    }

    .hero-main h3 {
        font-size: 22px;
    }
}
</style>
