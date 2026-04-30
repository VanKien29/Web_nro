<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">Shop</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Shop</span>
                </nav>
            </div>
            <div class="page-actions">
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="runtimeLoading"
                    @click="reloadRuntimeShop"
                >
                    <span
                        v-if="runtimeLoading"
                        class="admin-loading-dot"
                    ></span>
                    <span class="mi" style="font-size: 16px">sync</span>
                    {{
                        runtimeLoading
                            ? "Đang cập nhật..."
                            : "Cập nhật shop trong game"
                    }}
                </button>
            </div>
        </div>

        <div v-if="runtimeError" class="alert alert-error">
            {{ runtimeError }}
        </div>
        <div v-if="runtimeMessage" class="alert alert-success">
            {{ runtimeMessage }}
        </div>

        <!-- Search bar -->
        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadShops">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm shop theo tên..."
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
                            <th>NPC</th>
                            <th>Tên Shop</th>
                            <th>Type</th>
                            <th>Tabs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="shop in shops" :key="shop.id">
                            <tr
                                class="shop-row"
                                @click="toggleShop(shop.id)"
                                style="cursor: pointer"
                            >
                                <td>{{ shop.id }}</td>
                                <td>
                                    <div class="npc-cell">
                                        <AdminIcon
                                            v-if="shop.npc_avatar"
                                            class="npc-avatar"
                                            :icon-id="shop.npc_avatar"
                                        />
                                        <span class="npc-id">{{
                                            shop.npc_id
                                        }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="shop-name-cell">
                                        <span
                                            class="mi expand-icon"
                                            :class="{
                                                rotated: expandedShops[shop.id],
                                            }"
                                            >expand_more</span
                                        >
                                        <strong>{{ shop.tag_name }}</strong>
                                        <span
                                            v-if="shop.npc_name"
                                            class="npc-name-label"
                                            >{{ shop.npc_name }}</span
                                        >
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="'badge-type-' + shop.type_shop"
                                    >
                                        {{ typeLabel(shop.type_shop) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info"
                                        >{{ shop.tabs.length }} tab</span
                                    >
                                </td>
                            </tr>
                            <tr
                                v-for="tab in shop.tabs"
                                :key="'tab-' + tab.id"
                                class="tab-row"
                                v-show="expandedShops[shop.id]"
                                @click="
                                    $router.push({
                                        name: 'admin.shops.tab.edit',
                                        params: { tabId: tab.id },
                                    })
                                "
                                style="cursor: pointer"
                            >
                                <td></td>
                                <td></td>
                                <td colspan="2">
                                    <div class="tab-info">
                                        <span class="mi tab-icon">folder</span>
                                        <span>{{
                                            tab.tab_name.replace(/<>/g, " ")
                                        }}</span>
                                        <span class="tab-index"
                                            >#{{ tab.tab_index }}</span
                                        >
                                        <span
                                            class="badge badge-warning tab-item-badge"
                                            >{{ tab.item_count }} items</span
                                        >
                                    </div>
                                </td>
                                <td style="text-align: right">
                                    <button
                                        type="button"
                                        class="edit-link"
                                        @click.stop="
                                            $router.push({
                                                name: 'admin.shops.tab.edit',
                                                params: { tabId: tab.id },
                                            })
                                        "
                                    >
                                        <span class="mi" style="font-size: 14px"
                                            >edit</span
                                        >
                                        Sửa
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <!-- <tr v-if="loading" class="admin-loading-row">
                            <td colspan="5">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr> -->
                        <tr v-if="!shops.length && !loading">
                            <td
                                colspan="5"
                                style="
                                    text-align: center;
                                    color: var(--ds-text-muted);
                                    padding: 32px;
                                "
                            >
                                Không có shop nào.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            shops: [],
            search: "",
            loading: false,
            runtimeLoading: false,
            runtimeMessage: "",
            runtimeError: "",
            expandedShops: {},
        };
    },
    created() {
        this.loadShops();
    },
    methods: {
        typeLabel(type) {
            const labels = { 0: "Gold", 3: "Special" };
            return labels[type] || "Type " + type;
        },
        toggleShop(id) {
            this.expandedShops = {
                ...this.expandedShops,
                [id]: !this.expandedShops[id],
            };
        },
        async reloadRuntimeShop() {
            this.runtimeLoading = true;
            this.runtimeMessage = "";
            this.runtimeError = "";
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch("/admin/api/runtime/shop/reload", {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Reload shop thất bại");
                }
                this.runtimeMessage =
                    data.message || "Đã cập nhật shop trong game.";
            } catch (e) {
                this.runtimeError =
                    e?.message || "Không gọi được game runtime API.";
            } finally {
                this.runtimeLoading = false;
            }
        },
        async loadShops() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ search: this.search });
                const res = await fetch(
                    `/admin/api/shops?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                this.shops = data.data || [];
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
.page-actions {
    display: flex;
    align-items: center;
    gap: 10px;
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
}

.shop-row {
    background: var(--ds-surface-2);
}
.shop-row:hover {
    background: rgba(var(--ds-primary-rgb), 0.04);
}
.shop-row td {
    border-top: 2px solid var(--ds-border) !important;
}
.shop-name-cell {
    display: flex;
    align-items: center;
    gap: 6px;
}
.npc-name-label {
    font-size: 11px;
    color: var(--ds-text-muted);
    background: var(--ds-gray-100);
    padding: 1px 6px;
    border-radius: 4px;
    margin-left: 4px;
}
.npc-cell {
    display: flex;
    align-items: center;
    gap: 6px;
}
.npc-avatar {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.npc-id {
    font-size: 12px;
    color: var(--ds-text-muted);
}
.expand-icon {
    font-size: 20px;
    color: var(--ds-text-muted);
    transition: transform 0.2s;
    transform: rotate(-90deg);
}
.expand-icon.rotated {
    transform: rotate(0deg);
}
.tab-row:hover td {
    background: rgba(var(--ds-primary-rgb), 0.06);
}
.tab-row td {
    padding-top: 6px !important;
    padding-bottom: 6px !important;
}
.tab-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-left: 16px;
}
.tab-icon {
    font-size: 16px;
    color: var(--ds-text-muted);
}
.tab-index {
    font-size: 11px;
    color: var(--ds-text-muted);
    background: var(--ds-gray-100);
    padding: 1px 6px;
    border-radius: 4px;
}
.tab-item-badge {
    margin-left: 4px;
}
.edit-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: var(--ds-primary);
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.35);
    border-radius: 6px;
    background: rgba(var(--ds-primary-rgb), 0.08);
    cursor: pointer;
    font-family: inherit;
}
.tab-row:hover .edit-link {
    text-decoration: none;
    background: rgba(var(--ds-primary-rgb), 0.14);
    border-color: rgba(var(--ds-primary-rgb), 0.55);
}

.badge-type-0 {
    background: rgba(var(--ds-warning-rgb), 0.15);
    color: var(--ds-warning);
}
.badge-type-3 {
    background: rgba(var(--ds-info-rgb), 0.15);
    color: var(--ds-info);
}
</style>
