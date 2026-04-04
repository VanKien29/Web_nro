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
                                    <img
                                        v-for="(item, i) in parseDetail(
                                            gc.detail,
                                        ).slice(0, 5)"
                                        :key="i"
                                        :src="iconUrl(item.temp_id)"
                                        :title="
                                            'ID: ' +
                                            item.temp_id +
                                            ' x' +
                                            (item.quantity || 1)
                                        "
                                        class="item-icon-sm"
                                        @error="
                                            $event.target.style.display = 'none'
                                        "
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
                            <td>{{ gc.count_left }}</td>
                            <td>{{ gc.mtv ? "Có" : "Không" }}</td>
                            <td style="color: var(--ds-text-muted)">
                                {{ gc.expired || "—" }}
                            </td>
                            <td>
                                <span
                                    v-if="gc.active"
                                    class="badge badge-success"
                                    >Active</span
                                >
                                <span v-else class="badge badge-danger"
                                    >Inactive</span
                                >
                            </td>
                            <td style="text-align: right">
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
                            </td>
                        </tr>
                        <tr v-if="!giftcodes.length && !loading">
                            <td
                                colspan="8"
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
            giftcodes: [],
            itemIconMap: {},
            search: "",
            page: 1,
            totalPages: 1,
            loading: false,
        };
    },
    created() {
        this.loadPage(1);
    },
    methods: {
        parseDetail(detail) {
            try {
                const d =
                    typeof detail === "string" ? JSON.parse(detail) : detail;
                return Array.isArray(d) ? d : [];
            } catch {
                return [];
            }
        },
        iconUrl(tempId) {
            const iconId = this.itemIconMap[tempId] || tempId;
            return `/assets/frontend/home/v1/images/x4/${iconId}.png`;
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
                    `/admin/api/giftcodes?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                this.giftcodes = data.data || [];
                this.totalPages = data.total_pages || 1;

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
            // Batch fetch all item icons in a single request
            const unknownIds = ids.filter((id) => !this.itemIconMap[id]);
            if (!unknownIds.length) return;
            try {
                const res = await fetch(
                    `/admin/api/items/batch?ids=${unknownIds.join(",")}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                for (const [id, item] of Object.entries(data)) {
                    if (item.icon_id) {
                        this.itemIconMap[parseInt(id)] = item.icon_id;
                    }
                }
            } catch {
                // fallback: icons won't resolve
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
</style>
