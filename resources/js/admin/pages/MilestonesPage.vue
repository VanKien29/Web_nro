<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Mốc thưởng</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">{{ currentTypeLabel }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.milestones.create', params: { type: currentType } }"
                class="btn btn-primary"
            >
                <span class="mi" style="font-size: 16px">add</span>
                Tạo mốc quà
            </router-link>
        </div>

        <div class="type-tabs">
            <button
                v-for="t in typeTabs"
                :key="'tab-' + t.key"
                class="type-tab"
                :class="{ active: currentType === t.key }"
                @click="switchType(t.key)"
            >
                {{ t.label }}
            </button>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm theo ID hoặc thông tin..."
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
                            <th>Thông tin</th>
                            <th>Quà</th>
                            <th>Số item</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>{{ row.id }}</td>
                            <td>
                                <div class="info-text">{{ row.info }}</div>
                            </td>
                            <td>
                                <div class="item-icons">
                                    <AdminIcon
                                        v-for="(it, idx) in parseDetail(row.detail).slice(0, 6)"
                                        :key="'item-' + row.id + '-' + idx"
                                        :icon-id="itemIconId(it.temp_id)"
                                        :title="
                                            'ID: ' +
                                            it.temp_id +
                                            ' x' +
                                            (it.quantity || 1)
                                        "
                                        class="item-icon-sm"
                                    />
                                    <span
                                        v-if="parseDetail(row.detail).length > 6"
                                        class="more-items"
                                    >
                                        +{{ parseDetail(row.detail).length - 6 }}
                                    </span>
                                    <span
                                        v-if="!parseDetail(row.detail).length"
                                        class="empty-mark"
                                    >
                                        —
                                    </span>
                                </div>
                            </td>
                            <td>{{ row.detail_count || 0 }}</td>
                            <td style="text-align: right">
                                <router-link
                                    :to="{
                                        name: 'admin.milestones.edit',
                                        params: { type: currentType, id: row.id },
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
                        <tr v-if="!rows.length && !loading">
                            <td colspan="5" class="empty-cell">
                                Không có dữ liệu.
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
                <button :disabled="page >= totalPages" @click="goToPage(page + 1)">
                    &raquo;
                </button>
                <button :disabled="page >= totalPages" @click="goToPage(totalPages)">
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
const TYPE_MAP = {
    moc_nap: "Mốc nạp",
    moc_nap_top: "Mốc nạp top",
    moc_nhiem_vu_top: "Mốc nhiệm vụ top",
    moc_suc_manh_top: "Mốc sức mạnh top",
};

export default {
    data() {
        return {
            rows: [],
            search: "",
            page: 1,
            pageInput: "1",
            totalPages: 1,
            loading: false,
            itemIconMap: {},
        };
    },
    computed: {
        currentType() {
            return this.$route.params.type;
        },
        currentTypeLabel() {
            return TYPE_MAP[this.currentType] || "Mốc thưởng";
        },
        typeTabs() {
            return Object.keys(TYPE_MAP).map((key) => ({
                key,
                label: TYPE_MAP[key],
            }));
        },
        paginationItems() {
            return this.buildPaginationItems(this.page, this.totalPages);
        },
    },
    watch: {
        "$route.params.type"() {
            this.ensureType();
            this.loadPage(1);
        },
    },
    created() {
        this.ensureType();
        this.loadPage(1);
    },
    methods: {
        ensureType() {
            if (!TYPE_MAP[this.currentType]) {
                this.$router.replace({
                    name: "admin.milestones",
                    params: { type: "moc_nap" },
                });
            }
        },
        switchType(type) {
            if (type === this.currentType) return;
            this.$router.push({
                name: "admin.milestones",
                params: { type },
            });
        },
        buildPaginationItems(current, total) {
            if (total <= 7) {
                return Array.from({ length: total }, (_, index) => index + 1);
            }

            const pages = new Set([1, total, current - 1, current, current + 1]);
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
            return Math.min(Math.max(1, Math.trunc(value)), this.totalPages || 1);
        },
        goToPage(page) {
            const target = this.normalizePage(page);
            if (target === this.page && this.rows.length) {
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
            s = s.replace(/,\s*([\]\}])/g, "$1");
            s = s.replace(/([\[\{])\s*,/g, "$1");
            s = s.replace(/,\s*,/g, ",");
            return s;
        },
        parseDetail(detail) {
            try {
                const raw =
                    typeof detail === "string" ? detail : JSON.stringify(detail);
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
        async loadPage(p) {
            this.loading = true;
            this.page = this.normalizePage(p);
            this.pageInput = String(this.page);
            try {
                const params = new URLSearchParams({
                    page: String(this.page),
                    search: this.search,
                });
                const res = await fetch(
                    `/admin/api/milestones/${this.currentType}?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                this.itemIconMap = {
                    ...this.itemIconMap,
                    ...(data.item_icons || {}),
                };
                this.rows = data.data || [];
                this.totalPages = data.total_pages || 1;
                this.page = this.normalizePage(data.page || this.page);
                this.pageInput = String(this.page);

                const allIds = new Set();
                this.rows.forEach((row) => {
                    this.parseDetail(row.detail).forEach((it) => {
                        if (it.temp_id) allIds.add(it.temp_id);
                    });
                });
                if (allIds.size) {
                    await this.resolveIcons([...allIds]);
                }
            } catch {
                this.rows = [];
                this.totalPages = 1;
            } finally {
                this.loading = false;
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
    margin-bottom: 16px;
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
.type-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.type-tab {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface);
    color: var(--ds-text);
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s;
}
.type-tab:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.45);
}
.type-tab.active {
    background: rgba(var(--ds-primary-rgb), 0.15);
    border-color: rgba(var(--ds-primary-rgb), 0.55);
    color: var(--ds-primary);
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
    width: 320px;
}
.info-text {
    max-width: 380px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
.empty-mark {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
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
