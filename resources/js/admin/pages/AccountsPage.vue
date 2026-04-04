<template>
    <div>
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
                <span class="mi" style="font-size: 16px">person_add</span> Tạo
                tài khoản
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
                                    Sửa
                                </router-link>
                            </td>
                        </tr>
                        <tr v-if="!accounts.length && !loading">
                            <td
                                colspan="9"
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
                <button :disabled="page <= 1" @click="loadPage(page - 1)">
                    &laquo;
                </button>
                <button
                    v-for="p in totalPages"
                    :key="p"
                    :class="{ active: p === page }"
                    @click="loadPage(p)"
                >
                    {{ p }}
                </button>
                <button
                    :disabled="page >= totalPages"
                    @click="loadPage(page + 1)"
                >
                    &raquo;
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            accounts: [],
            search: "",
            page: 1,
            totalPages: 1,
            loading: false,
            editing: null,
        };
    },
    created() {
        this.loadPage(1);
    },
    methods: {
        fmt(n) {
            return Number(n || 0).toLocaleString("vi-VN");
        },
        async loadPage(p) {
            this.loading = true;
            this.page = p;
            try {
                const params = new URLSearchParams({
                    page: p,
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
                const data = await res.json();
                this.accounts = data.data || [];
                this.totalPages = data.total_pages || 1;
            } catch {
                //
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
.breadcrumb span {
    color: var(--ds-gray-300);
}
.breadcrumb .current {
    color: var(--ds-text);
}
.filter-bar {
    margin-bottom: 20px;
}
.search-form {
    display: flex;
    gap: 8px;
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
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
    width: 300px;
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
</style>
