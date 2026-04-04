<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">Items</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span class="sep">/</span>
                    <span class="current">Items</span>
                </nav>
            </div>
            <span v-if="total" class="total-count"
                >{{ total.toLocaleString("vi-VN") }} items</span
            >
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm ID hoặc tên..."
                    />
                </div>
                <select
                    v-model="typeFilter"
                    class="form-input"
                    style="width: 160px"
                >
                    <option value="">Tất cả type</option>
                    <option v-for="t in types" :key="t" :value="t">
                        {{ t }}
                    </option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">
                    <span class="mi" style="font-size: 16px">filter_list</span>
                    Lọc
                </button>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Tên</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>{{ item.id }}</td>
                            <td>
                                <img
                                    :src="
                                        '/assets/frontend/home/v1/images/x4/' +
                                        item.icon_id +
                                        '.png'
                                    "
                                    class="item-icon"
                                    @error="
                                        $event.target.style.display = 'none'
                                    "
                                />
                            </td>
                            <td style="font-weight: 500">{{ item.name }}</td>
                            <td>
                                <span class="badge badge-info">{{
                                    item.type
                                }}</span>
                            </td>
                        </tr>
                        <tr v-if="!items.length && !loading">
                            <td colspan="4" class="empty-cell">
                                Không có item nào.
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
                    v-for="p in visiblePages"
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
            items: [],
            types: [],
            search: "",
            typeFilter: "",
            loading: false,
            page: 1,
            totalPages: 1,
            total: 0,
        };
    },
    computed: {
        visiblePages() {
            const pages = [];
            const start = Math.max(1, this.page - 3);
            const end = Math.min(this.totalPages, this.page + 3);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },
    },
    created() {
        this.loadPage(1);
    },
    methods: {
        async loadPage(p) {
            this.loading = true;
            this.page = p;
            try {
                const params = new URLSearchParams({ page: p });
                if (this.search) params.set("search", this.search);
                if (this.typeFilter) params.set("type", this.typeFilter);
                const res = await fetch(
                    `/admin/api/items?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                this.items = data.data || [];
                this.types = data.types || [];
                this.totalPages = data.total_pages || 1;
                this.total = data.total || 0;
            } catch {
                //
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
.total-count {
    font-size: 13px;
    color: var(--ds-text-muted);
    background: var(--ds-gray-100);
    padding: 4px 12px;
    border-radius: 20px;
}
.filter-bar {
    margin-bottom: 20px;
}
.search-form {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
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
    width: 280px;
}
.item-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--ds-gray-100);
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
}
</style>
