<template>
    <div class="accounts-page">
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">Tài khoản</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Tài khoản</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.accounts.create' }"
                class="btn btn-primary"
            >
                <span class="mi">person_add</span>
                <span>Tạo tài khoản</span>
            </router-link>
        </div>

        <!-- Search bar -->
        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm username..."
                    />
                </div>
                <button class="btn btn-primary btn-sm" type="submit">
                    Tìm kiếm
                </button>
            </form>
        </div>

        <div class="card">
            <div
                v-if="loadError"
                class="alert alert-error"
                style="margin: 16px"
            >
                {{ loadError }}
            </div>
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
                            <th>Điểm Đã Nhận</th>
                            <th>Điểm Danh</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="acc in accounts" :key="acc.id">
                            <td>{{ acc.id }}</td>
                            <td style="font-weight: 500">{{ acc.username }}</td>
                            <td>
                                <span v-if="acc.ban" class="badge badge-danger"
                                    >Có</span
                                >
                                <span v-else style="color: var(--ds-text-muted)"
                                    >—</span
                                >
                            </td>
                            <td>
                                <span
                                    v-if="acc.is_admin"
                                    class="badge badge-warning"
                                    >Admin</span
                                >
                                <span v-else style="color: var(--ds-text-muted)"
                                    >—</span
                                >
                            </td>
                            <td>
                                <span
                                    v-if="acc.active"
                                    class="badge badge-success"
                                    >Active</span
                                >
                                <span v-else style="color: var(--ds-text-muted)"
                                    >—</span
                                >
                            </td>
                            <td
                                class="editable-cell"
                                @dblclick="startEdit(acc, 'cash')"
                            >
                                <input
                                    v-if="
                                        editing &&
                                        editing.id === acc.id &&
                                        editing.field === 'cash'
                                    "
                                    v-model.number="editing.value"
                                    type="number"
                                    class="inline-input"
                                    @blur="saveEdit(acc)"
                                    @keydown.enter="$event.target.blur()"
                                    @keydown.escape="cancelEdit"
                                    ref="inlineInput"
                                />
                                <span
                                    v-else
                                    class="editable-value"
                                    :title="'Nhấp đúp để sửa'"
                                    >{{ fmt(acc.cash) }}</span
                                >
                            </td>
                            <td
                                class="editable-cell"
                                @dblclick="startEdit(acc, 'danap')"
                            >
                                <input
                                    v-if="
                                        editing &&
                                        editing.id === acc.id &&
                                        editing.field === 'danap'
                                    "
                                    v-model.number="editing.value"
                                    type="number"
                                    class="inline-input"
                                    @blur="saveEdit(acc)"
                                    @keydown.enter="$event.target.blur()"
                                    @keydown.escape="cancelEdit"
                                    ref="inlineInput"
                                />
                                <span
                                    v-else
                                    class="editable-value"
                                    :title="'Nhấp đúp để sửa'"
                                    >{{ fmt(acc.danap) }}</span
                                >
                            </td>
                            <td
                                class="editable-cell"
                                @dblclick="startEdit(acc, 'coin')"
                            >
                                <input
                                    v-if="
                                        editing &&
                                        editing.id === acc.id &&
                                        editing.field === 'coin'
                                    "
                                    v-model.number="editing.value"
                                    type="number"
                                    class="inline-input"
                                    @blur="saveEdit(acc)"
                                    @keydown.enter="$event.target.blur()"
                                    @keydown.escape="cancelEdit"
                                    ref="inlineInput"
                                />
                                <span
                                    v-else
                                    class="editable-value"
                                    :title="'Nhấp đúp để sửa'"
                                    >{{ fmt(acc.coin) }}</span
                                >
                            </td>
                            <td
                                class="editable-cell"
                                @dblclick="startEdit(acc, 'diem_da_nhan')"
                            >
                                <input
                                    v-if="
                                        editing &&
                                        editing.id === acc.id &&
                                        editing.field === 'diem_da_nhan'
                                    "
                                    v-model.number="editing.value"
                                    type="number"
                                    class="inline-input"
                                    @blur="saveEdit(acc)"
                                    @keydown.enter="$event.target.blur()"
                                    @keydown.escape="cancelEdit"
                                    ref="inlineInput"
                                />
                                <span
                                    v-else
                                    class="editable-value"
                                    :title="'Nhấp đúp để sửa'"
                                    >{{ fmt(acc.diem_da_nhan) }}</span
                                >
                            </td>
                            <td
                                class="editable-cell"
                                @dblclick="startEdit(acc, 'diem_danh')"
                            >
                                <input
                                    v-if="
                                        editing &&
                                        editing.id === acc.id &&
                                        editing.field === 'diem_danh'
                                    "
                                    v-model.number="editing.value"
                                    type="number"
                                    class="inline-input"
                                    @blur="saveEdit(acc)"
                                    @keydown.enter="$event.target.blur()"
                                    @keydown.escape="cancelEdit"
                                    ref="inlineInput"
                                />
                                <span
                                    v-else
                                    class="editable-value"
                                    :title="'Nhấp đúp để sửa'"
                                    >{{ fmt(acc.diem_danh) }}</span
                                >
                            </td>
                            <td style="text-align: right">
                                <router-link
                                    :to="{
                                        name: 'admin.accounts.edit',
                                        params: { id: acc.id },
                                    }"
                                    class="btn btn-primary btn-sm"
                            >
                                <span class="mi" style="font-size: 14px"
                                    >edit</span
                                >
                                <span>Sửa</span>
                            </router-link>
                            </td>
                        </tr>
                        <!-- <tr v-if="loading" class="admin-loading-row">
                            <td colspan="11">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr> -->
                        <tr v-if="!accounts.length && !loading">
                            <td
                                colspan="11"
                                style="
                                    text-align: center;
                                    color: var(--ds-text-muted);
                                    padding: 32px;
                                "
                            >
                                Không có tài khoản nào.
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
                    >
                        ...
                    </span>
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
                <div class="pagination-jump">
                    <span class="pagination-summary"
                        >Trang {{ page }} / {{ totalPages }}</span
                    >
                    <input
                        v-model="pageInput"
                        type="number"
                        min="1"
                        :max="totalPages"
                        class="form-input pagination-input"
                        @keyup.enter="jumpToPage"
                    />
                    <button @click="jumpToPage">Đi</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { buildPaginationItems } from "../../shared/format";
export default {
    data() {
        return {
            accounts: [],
            search: "",
            page: 1,
            pageInput: "1",
            totalPages: 1,
            loading: false,
            editing: null,
            loadError: "",
        };
    },
    computed: {
        paginationItems() {
            return buildPaginationItems(this.page, this.totalPages);
        },
    },
    created() {
        this.loadPage(1);
    },
    methods: {
        normalizePage(page) {
            const value = Number(page);
            if (!Number.isFinite(value)) return 1;
            return Math.min(
                Math.max(1, Math.trunc(value)),
                this.totalPages || 1,
            );
        },
        goToPage(page) {
            const target = this.normalizePage(page);
            if (target === this.page && this.accounts.length) {
                this.pageInput = String(target);
                return;
            }
            this.loadPage(target);
        },
        jumpToPage() {
            this.goToPage(this.pageInput);
        },
        fmt(n) {
            return Number(n || 0).toLocaleString("vi-VN");
        },
        async loadPage(p) {
            this.loading = true;
            this.loadError = "";
            this.page = this.normalizePage(p);
            this.pageInput = String(this.page);
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    search: this.search,
                });
                const res = await fetch(
                    `/admin/api/accounts?${params.toString()}`,
                    {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                const data = await res.json();
                this.accounts = data.data || [];
                this.totalPages = data.total_pages || 1;
                this.page = this.normalizePage(data.page || this.page);
                this.pageInput = String(this.page);
            } catch {
                this.accounts = [];
                this.totalPages = 1;
                this.loadError = "Không tải được danh sách tài khoản.";
            } finally {
                this.loading = false;
            }
        },
        startEdit(row, field) {
            this.editing = {
                id: row.id,
                field,
                value: row[field],
                original: row[field],
            };
            this.$nextTick(() => {
                const input = this.$refs.inlineInput;
                if (input) {
                    const el = Array.isArray(input) ? input[0] : input;
                    el.focus();
                    el.select();
                }
            });
        },
        cancelEdit() {
            this.editing = null;
        },
        async saveEdit(row) {
            if (!this.editing) return;
            const { field, value, original } = this.editing;
            this.editing = null;
            if (value === original) return;
            const prev = row[field];
            row[field] = value;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch(`/admin/api/accounts/${row.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                    body: JSON.stringify({ [field]: value }),
                });
                const data = await res.json();
                if (!data.ok) row[field] = prev;
            } catch {
                row[field] = prev;
            }
        },
    },
};
</script>

<style scoped>
.accounts-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.page-title {
    font-size: 26px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    line-height: 1.15;
    margin: 0 0 6px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 15px;
}
.breadcrumb a {
    color: var(--ds-primary);
    text-decoration: none;
}
.breadcrumb a:hover {
    color: var(--ds-primary);
}
.breadcrumb span {
    color: var(--ds-gray-300);
}
.breadcrumb .current {
    color: var(--ds-text);
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    line-height: 1;
    white-space: nowrap;
}
.mi {
    font-family: "Material Icons Round";
    font-weight: normal;
    font-style: normal;
    font-size: 18px;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    word-wrap: normal;
    direction: ltr;
    -webkit-font-feature-settings: "liga";
    -webkit-font-smoothing: antialiased;
}
.filter-bar {
    margin-bottom: 0;
}
.search-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.search-input-wrap {
    position: relative;
    width: min(460px, 100%);
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
    width: 100%;
}
.card {
    overflow: hidden;
}
.table-wrap {
    overflow: auto;
}
table {
    min-width: 1040px;
}
th,
td {
    padding: 13px 14px;
}
td:first-child,
th:first-child {
    width: 72px;
}
td:last-child,
th:last-child {
    width: 92px;
}
.editable-cell {
    cursor: default;
}
.editable-value {
    cursor: default;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 0.15s;
}
.editable-cell:hover .editable-value {
    background: rgba(var(--ds-primary-rgb), 0.08);
    outline: 1px dashed rgba(var(--ds-primary-rgb), 0.3);
}
.inline-input {
    width: 120px;
    max-width: 140px;
    padding: 4px 8px;
    font-size: 13px;
    border: 2px solid var(--ds-primary);
    border-radius: 6px;
    background: var(--ds-body-bg);
    color: var(--ds-text);
    outline: none;
    font-family: inherit;
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.15);
}
.pagination {
    flex-wrap: wrap;
    gap: 8px;
}
.pagination-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.pagination-jump {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
    flex-wrap: wrap;
}
.pagination-summary {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.pagination-input {
    width: 72px;
    min-width: 72px;
    padding: 6px 8px !important;
}
</style>

