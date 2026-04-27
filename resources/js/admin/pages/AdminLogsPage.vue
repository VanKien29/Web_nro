<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Nhật ký admin</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Nhật ký admin</span>
                </nav>
            </div>
        </div>

        <div class="filter-bar">
            <form class="filter-grid" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="filters.search"
                        class="form-input search-input"
                        placeholder="Tìm admin, đối tượng hoặc nội dung..."
                    />
                </div>
                <select v-model="filters.target_type" class="form-input">
                    <option value="">Tất cả đối tượng</option>
                    <option value="account">Tài khoản</option>
                    <option value="giftcode">Giftcode</option>
                    <option value="milestone">Mốc thưởng</option>
                    <option value="shop_tab">Tab shop</option>
                </select>
                <select v-model="filters.action" class="form-input">
                    <option value="">Tất cả thao tác</option>
                    <option value="create">Tạo</option>
                    <option value="update">Cập nhật</option>
                    <option value="delete">Xoá</option>
                    <option value="clone">Clone</option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">
                    Lọc
                </button>
            </form>
        </div>

        <div class="card">
            <div v-if="error" class="alert alert-error">{{ error }}</div>

            <div class="table-wrap">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Admin</th>
                            <th>Thao tác</th>
                            <th>Đối tượng</th>
                            <th>Nội dung</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in rows" :key="row.id">
                            <tr>
                                <td class="cell-muted">
                                    {{ formatDateTime(row.created_at) }}
                                </td>
                                <td class="cell-strong">
                                    {{ row.admin_username || "admin" }}
                                </td>
                                <td>
                                    <span class="badge badge-info">{{
                                        actionLabel(row.action)
                                    }}</span>
                                </td>
                                <td>
                                    <div class="log-target-cell">
                                        <span>{{
                                            targetLabel(row.target_type)
                                        }}</span>
                                        <span
                                            v-if="row.target_id"
                                            class="log-id"
                                            >#{{ row.target_id }}</span
                                        >
                                    </div>
                                </td>
                                <td>
                                    <div
                                        class="log-summary-cell"
                                        :title="row.summary || ''"
                                    >
                                        {{ row.summary || "Không có mô tả" }}
                                    </div>
                                </td>
                                <td style="text-align: right">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        @click="toggleExpanded(row.id)"
                                    >
                                        {{
                                            expanded[row.id]
                                                ? "Thu gọn"
                                                : "Chi tiết"
                                        }}
                                    </button>
                                </td>
                            </tr>
                            <tr
                                v-if="expanded[row.id]"
                                class="log-expanded-row"
                            >
                                <td colspan="6">
                                    <div class="log-details">
                                        <div class="log-detail-meta">
                                            <span class="log-target">{{
                                                row.target_label || "—"
                                            }}</span>
                                        </div>
                                        <div class="log-detail-grid">
                                            <div class="log-detail-box">
                                                <div class="log-detail-title">
                                                    Trước khi sửa
                                                </div>
                                                <pre>{{
                                                    prettyState(
                                                        row.before_state,
                                                    )
                                                }}</pre>
                                            </div>
                                            <div class="log-detail-box">
                                                <div class="log-detail-title">
                                                    Sau khi sửa
                                                </div>
                                                <pre>{{
                                                    prettyState(row.after_state)
                                                }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <!-- <tr v-if="loading" class="admin-loading-row">
                            <td colspan="6">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr> -->
                        <tr v-if="!rows.length && !loading">
                            <td colspan="6" class="empty-state">
                                Chưa có nhật ký thao tác nào.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="goToPage(1)">Đầu</button>
                <button :disabled="page <= 1" @click="goToPage(page - 1)">
                    &laquo;
                </button>
                <template v-for="p in paginationItems" :key="String(p)">
                    <span
                        v-if="typeof p !== 'number'"
                        class="pagination-ellipsis"
                        >...</span
                    >
                    <button
                        v-else
                        :class="{ active: p === page }"
                        @click="goToPage(p)"
                    >
                        {{ p }}
                    </button>
                </template>
                <button
                    :disabled="page >= totalPages"
                    @click="goToPage(page + 1)"
                >
                    &raquo;
                </button>
                <button
                    :disabled="page >= totalPages"
                    @click="goToPage(totalPages)"
                >
                    Cuối
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            rows: [],
            page: 1,
            totalPages: 1,
            loading: false,
            error: "",
            expanded: {},
            filters: {
                search: "",
                target_type: "",
                action: "",
            },
        };
    },
    computed: {
        paginationItems() {
            return this.buildPaginationItems(this.page, this.totalPages);
        },
    },
    created() {
        this.loadPage(1);
    },
    methods: {
        buildPaginationItems(current, total) {
            if (total <= 7) {
                return Array.from({ length: total }, (_, index) => index + 1);
            }
            const pages = new Set([
                1,
                total,
                current - 1,
                current,
                current + 1,
            ]);
            if (current <= 3) {
                pages.add(2);
                pages.add(3);
                pages.add(4);
            }
            if (current >= total - 2) {
                pages.add(total - 1);
                pages.add(total - 2);
                pages.add(total - 3);
            }
            const sorted = [...pages]
                .filter((page) => page >= 1 && page <= total)
                .sort((a, b) => a - b);
            const items = [];
            sorted.forEach((page, index) => {
                if (index > 0 && page - sorted[index - 1] > 1) {
                    items.push(`ellipsis-${sorted[index - 1]}-${page}`);
                }
                items.push(page);
            });
            return items;
        },
        async loadPage(page) {
            this.loading = true;
            this.error = "";
            this.page = page;
            try {
                const params = new URLSearchParams({
                    page: String(page),
                    search: this.filters.search || "",
                    target_type: this.filters.target_type || "",
                    action: this.filters.action || "",
                });
                const res = await fetch(
                    `/admin/api/admin-logs?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(
                        data.message || "Không thể tải nhật ký admin",
                    );
                }
                this.rows = data.data || [];
                this.page = data.page || 1;
                this.totalPages = data.total_pages || 1;
            } catch (error) {
                this.error = error?.message || "Không thể tải nhật ký admin";
            } finally {
                this.loading = false;
            }
        },
        goToPage(page) {
            const target = Math.min(
                Math.max(Number(page) || 1, 1),
                this.totalPages || 1,
            );
            this.loadPage(target);
        },
        toggleExpanded(id) {
            this.expanded = {
                ...this.expanded,
                [id]: !this.expanded[id],
            };
        },
        actionLabel(action) {
            const map = {
                create: "Tạo",
                update: "Cập nhật",
                delete: "Xoá",
                clone: "Clone",
            };
            return map[action] || action;
        },
        targetLabel(type) {
            const map = {
                account: "Tài khoản",
                giftcode: "Giftcode",
                milestone: "Mốc thưởng",
                shop_tab: "Tab shop",
            };
            return map[type] || type;
        },
        prettyState(value) {
            if (!value || (Array.isArray(value) && !value.length)) {
                return "(không có)";
            }
            if (typeof value === "string") {
                return value;
            }
            try {
                return JSON.stringify(value, null, 2);
            } catch {
                return String(value);
            }
        },
        formatDateTime(value) {
            if (!value) return "—";
            const date = new Date(String(value).replace(" ", "T"));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString("vi-VN");
        },
    },
};
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
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
.breadcrumb .current,
.breadcrumb span {
    color: var(--ds-text);
}
.filter-bar {
    margin-bottom: 20px;
}
.filter-grid {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) 220px 180px auto;
    gap: 12px;
}
.search-input-wrap {
    position: relative;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
}
.search-input {
    padding-left: 40px;
}
.logs-table {
    table-layout: fixed;
}
.logs-table th:nth-child(1) {
    width: 160px;
}
.logs-table th:nth-child(2) {
    width: 120px;
}
.logs-table th:nth-child(3) {
    width: 110px;
}
.logs-table th:nth-child(4) {
    width: 140px;
}
.logs-table th:nth-child(6) {
    width: 110px;
}
.cell-strong {
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.cell-muted {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.log-target-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.log-target,
.log-id {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.log-summary-cell {
    color: var(--ds-text);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.log-details {
    padding: 8px 0 2px;
}
.log-detail-meta {
    margin-bottom: 10px;
}
.log-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.log-expanded-row td {
    background: rgba(var(--ds-primary-rgb), 0.03);
}
.log-detail-box {
    border: 1px dashed var(--ds-border);
    border-radius: 12px;
    padding: 12px;
    background: var(--ds-surface);
}
.log-detail-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 8px;
}
.log-detail-box pre {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 12px;
    color: var(--ds-text);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    max-height: 280px;
    overflow: auto;
}
.empty-state {
    text-align: center;
    padding: 32px;
    color: var(--ds-text-muted);
}
@media (max-width: 1100px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
    .log-detail-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 720px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>
