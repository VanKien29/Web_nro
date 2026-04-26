<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">Giftcode</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Giftcode</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.giftcodes.create' }"
                class="btn btn-primary"
            >
                <span class="mi" style="font-size: 16px">add</span> Tạo giftcode
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
                        placeholder="Tìm code..."
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="form-input compact-filter"
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="available">Đang hiển thị và dùng được</option>
                    <option value="active">Đang hiển thị</option>
                    <option value="inactive">Đang ẩn</option>
                    <option value="expired">Đã hết hạn</option>
                    <option value="depleted">Hết lượt</option>
                </select>
                <select v-model="mtvFilter" class="form-input compact-filter">
                    <option value="">Tất cả MTV</option>
                    <option value="1">Chỉ 1 lần/tài khoản</option>
                    <option value="0">Không giới hạn tài khoản</option>
                </select>
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
                            <th>Code</th>
                            <th>Vật phẩm</th>
                            <th>Còn lại</th>
                            <th>MTV</th>
                            <th>Hết hạn</th>
                            <th>Tạo lúc</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="gc in giftcodes" :key="gc.id">
                            <td>{{ gc.id }}</td>
                            <td>
                                <code class="code-badge">{{ gc.code }}</code>
                            </td>
                            <td>
                                <div class="item-icons">
                                    <AdminIcon
                                        v-for="(item, i) in parseDetail(
                                            gc.detail,
                                        ).slice(0, 5)"
                                        :key="i"
                                        :icon-id="itemIconId(item.temp_id)"
                                        :title="
                                            'ID: ' +
                                            item.temp_id +
                                            ' x' +
                                            (item.quantity || 1)
                                        "
                                        class="item-icon-sm"
                                    />
                                    <span
                                        v-if="parseDetail(gc.detail).length > 5"
                                        class="more-items"
                                    >
                                        +{{ parseDetail(gc.detail).length - 5 }}
                                    </span>
                                    <span
                                        v-if="!parseDetail(gc.detail).length"
                                        style="
                                            color: var(--ds-text-muted);
                                            font-size: 12px;
                                        "
                                        >—</span
                                    >
                                </div>
                            </td>
                            <td
                                class="editable-cell"
                                @dblclick="startEdit(gc, 'count_left')"
                            >
                                <input
                                    v-if="
                                        editing &&
                                        editing.id === gc.id &&
                                        editing.field === 'count_left'
                                    "
                                    v-model.number="editing.value"
                                    type="number"
                                    class="inline-input"
                                    @blur="saveEdit(gc)"
                                    @keydown.enter="$event.target.blur()"
                                    @keydown.escape="cancelEdit"
                                    ref="inlineInput"
                                />
                                <span
                                    v-else
                                    class="editable-value"
                                    :title="'Nhấp đúp để sửa'"
                                    >{{ gc.count_left }}</span
                                >
                            </td>
                            <td>{{ gc.mtv ? "Có" : "Không" }}</td>
                            <td style="color: var(--ds-text-muted)">
                                {{ gc.expired || "—" }}
                            </td>
                            <td style="color: var(--ds-text-muted)">
                                {{ gc.datecreate || "—" }}
                            </td>
                            <td>
                                <span
                                    v-if="!isHidden(gc) && !isExpired(gc)"
                                    class="badge badge-success"
                                    >Đang hiển thị</span
                                >
                                <span
                                    v-else-if="isExpired(gc)"
                                    class="badge badge-warning"
                                    >Hết hạn</span
                                >
                                <span v-else class="badge badge-danger"
                                    >Đang ẩn</span
                                >
                            </td>
                            <td style="text-align: right">
                                <div class="row-actions">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-sm"
                                        @click="cloneGiftcode(gc)"
                                    >
                                        <span class="mi" style="font-size: 14px"
                                            >content_copy</span
                                        >
                                        Clone
                                    </button>
                                    <router-link
                                        :to="{
                                            name: 'admin.giftcodes.edit',
                                            params: { id: gc.id },
                                        }"
                                        class="btn btn-primary btn-sm"
                                    >
                                        <span class="mi" style="font-size: 14px"
                                            >edit</span
                                        >
                                        Sửa
                                    </router-link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!giftcodes.length && !loading">
                            <td
                                colspan="9"
                                style="
                                    text-align: center;
                                    color: var(--ds-text-muted);
                                    padding: 32px;
                                "
                            >
                                Không có giftcode nào.
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
export default {
    data() {
        return {
            giftcodes: [],
            itemIconMap: {},
            search: "",
            statusFilter: "",
            mtvFilter: "",
            page: 1,
            pageInput: "1",
            totalPages: 1,
            loading: false,
            editing: null,
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
            if (target === this.page && this.giftcodes.length) {
                this.pageInput = String(target);
                return;
            }
            this.loadPage(target);
        },
        jumpToPage() {
            this.goToPage(this.pageInput);
        },
        fixJson(str) {
            if (typeof str !== "string") return str;
            let s = str.trim();
            // Fix trailing commas before ] or }
            s = s.replace(/,\s*([\]\}])/g, "$1");
            // Fix leading commas after [ or {
            s = s.replace(/([\[\{])\s*,/g, "$1");
            // Fix double commas
            s = s.replace(/,\s*,/g, ",");
            return s;
        },
        parseDetail(detail) {
            try {
                const raw =
                    typeof detail === "string"
                        ? detail
                        : JSON.stringify(detail);
                const d = JSON.parse(this.fixJson(raw));
                return Array.isArray(d) ? d : [];
            } catch {
                return [];
            }
        },
        hasResolvedIcon(tempId) {
            return Object.prototype.hasOwnProperty.call(
                this.itemIconMap,
                Number(tempId),
            );
        },
        itemIconId(tempId) {
            return this.hasResolvedIcon(tempId)
                ? this.itemIconMap[Number(tempId)]
                : null;
        },
        isExpired(gc) {
            if (!gc?.expired) return false;
            const date = new Date(String(gc.expired).replace(" ", "T"));
            return !Number.isNaN(date.getTime()) && date.getTime() < Date.now();
        },
        isHidden(gc) {
            // DB rule: active = 1 -> hidden, active = 0 -> visible
            return Number(gc?.active) === 1;
        },
        async loadPage(p) {
            this.loading = true;
            this.page = this.normalizePage(p);
            this.pageInput = String(this.page);
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    search: this.search,
                });
                if (this.statusFilter) {
                    params.set("status", this.statusFilter);
                }
                if (this.mtvFilter !== "") {
                    params.set("mtv", this.mtvFilter);
                }
                const res = await fetch(
                    `/admin/api/giftcodes?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                this.itemIconMap = {
                    ...this.itemIconMap,
                    ...(data.item_icons || {}),
                };
                this.giftcodes = data.data || [];
                this.totalPages = data.total_pages || 1;
                this.page = this.normalizePage(data.page || this.page);
                this.pageInput = String(this.page);

                // Resolve icon_ids for all items
                const allIds = new Set();
                this.giftcodes.forEach((gc) => {
                    this.parseDetail(gc.detail).forEach((item) => {
                        if (item.temp_id) allIds.add(item.temp_id);
                    });
                });
                if (allIds.size) {
                    await this.resolveIcons([...allIds]);
                }
            } catch {
                //
            } finally {
                this.loading = false;
            }
        },
        async resolveIcons(ids) {
            const unknownIds = ids.filter((id) => !this.hasResolvedIcon(id));
            if (!unknownIds.length) return;

            const chunks = [];
            for (let i = 0; i < unknownIds.length; i += 100) {
                chunks.push(unknownIds.slice(i, i + 100));
            }

            for (const chunk of chunks) {
                try {
                    const res = await fetch(
                        `/admin/api/items/batch?ids=${chunk.join(",")}`,
                        {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                Accept: "application/json",
                            },
                        },
                    );
                    if (!res.ok) continue;
                    const data = await res.json();
                    const returnedIds = new Set(
                        Object.keys(data || {}).map((id) => Number(id)),
                    );
                    const nextMap = { ...this.itemIconMap };
                    for (const [id, item] of Object.entries(data || {})) {
                        nextMap[Number(id)] =
                            item?.icon_id !== undefined && item?.icon_id !== null
                                ? item.icon_id
                                : null;
                    }
                    chunk.forEach((id) => {
                        const numericId = Number(id);
                        if (!returnedIds.has(numericId)) {
                            nextMap[numericId] = null;
                        }
                    });
                    this.itemIconMap = nextMap;
                } catch {
                    // Keep these IDs unresolved so a later page refresh/search can retry.
                }
            }
        },
        async cloneGiftcode(row) {
            if (!confirm(`Clone giftcode "${row.code}"?`)) return;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch(
                    `/admin/api/giftcodes/${row.id}/clone`,
                    {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": token,
                        },
                    },
                );
                const data = await res.json();
                if (data.ok && data.id) {
                    this.$router.push({
                        name: "admin.giftcodes.edit",
                        params: { id: data.id },
                    });
                }
            } catch {
                //
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
                const res = await fetch(`/admin/api/giftcodes/${row.id}`, {
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
    display: grid;
    grid-template-columns: minmax(220px, 1fr) 220px 220px auto;
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
    width: 100%;
}
.compact-filter {
    min-width: 0;
}
.code-badge {
    color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.12);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 13px;
    font-family: "SF Mono", "Fira Code", monospace;
}
.item-icons {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    align-items: center;
}
.item-icon-sm {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: var(--ds-gray-100);
}
.more-items {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.row-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
@media (max-width: 980px) {
    .search-form {
        grid-template-columns: 1fr;
    }
}
</style>
