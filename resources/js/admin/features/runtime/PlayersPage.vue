<template>
    <div class="players-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Người chơi</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Người chơi</span>
                </nav>
            </div>
            <button
                class="btn btn-outline"
                :disabled="loading"
                @click="loadPlayers(page)"
            >
                <span class="mi">refresh</span>
                Tải lại
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="players-layout">
            <section class="card players-list-card">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Danh sách</span>
                        <h3>Player</h3>
                    </div>
                    <span class="pill"
                        >{{ total.toLocaleString("vi-VN") }} nhân vật</span
                    >
                </div>

                <form class="search-row" @submit.prevent="loadPlayers(1)">
                    <span class="mi">search</span>
                    <input
                        v-model.trim="search"
                        class="form-input"
                        placeholder="Tìm tên nhân vật, account hoặc ID..."
                        @input="debouncedLoad"
                    />
                </form>

                <div class="players-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nhân vật</th>
                                <th>Sức mạnh</th>
                                <th>Tiền</th>
                                <th>Túi/Rương</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in players"
                                :key="row.id"
                                :class="{ active: selectedId === row.id }"
                                @click="selectPlayer(row.id)"
                            >
                                <td>#{{ row.id }}</td>
                                <td>
                                    <div class="player-name">
                                        {{ row.name }}
                                    </div>
                                    <div class="muted">
                                        {{ row.username || "Chưa rõ account" }}
                                    </div>
                                    <span v-if="row.ban" class="badge danger"
                                        >Ban</span
                                    >
                                    <span
                                        v-else-if="row.active"
                                        class="badge success"
                                        >Active</span
                                    >
                                </td>
                                <td>{{ formatNumber(row.power) }}</td>
                                <td>
                                    <div>Vàng {{ formatNumber(row.gold) }}</div>
                                    <div class="muted">
                                        Ngọc {{ formatNumber(row.gem) }} · Ruby
                                        {{ formatNumber(row.ruby) }}
                                    </div>
                                </td>
                                <td>
                                    {{ row.bag_count }} / {{ row.box_count }}
                                </td>
                            </tr>
                            <tr v-if="loading" class="admin-loading-row">
                                <td colspan="5">
                                    <span class="admin-loading-row__content">
                                        <span
                                            class="admin-loading-spinner"
                                        ></span>
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!players.length && !loading">
                                <td colspan="5" class="empty-cell">
                                    Chưa có người chơi phù hợp.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="totalPages > 1" class="pagination compact">
                    <button
                        :disabled="page <= 1"
                        @click="loadPlayers(page - 1)"
                    >
                        «
                    </button>
                    <span>Trang {{ page }} / {{ totalPages }}</span>
                    <button
                        :disabled="page >= totalPages"
                        @click="loadPlayers(page + 1)"
                    >
                        »
                    </button>
                </div>

                <div class="global-inventory">
                    <div class="tool-title">
                        <span class="mi">manage_search</span>
                        Kiểm tra item trong hành trang
                    </div>
                    <form
                        class="global-search-form"
                        @submit.prevent="searchInventoryGlobal"
                    >
                        <input
                            v-model.trim="globalInventory.search"
                            class="form-input"
                            placeholder="Nhập item ID để tìm trong 500 nhân vật đầu..."
                        />
                        <button
                            class="btn btn-outline"
                            :disabled="globalInventory.loading"
                        >
                            Tìm
                        </button>
                    </form>
                    <div
                        v-if="globalInventory.results.length"
                        class="global-results"
                    >
                        <button
                            v-for="slot in globalInventory.results"
                            :key="
                                slot.player_id +
                                '-' +
                                slot.location +
                                '-' +
                                slot.slot
                            "
                            class="global-result"
                            @click="selectPlayer(slot.player_id)"
                        >
                            <AdminIcon
                                v-if="slot.icon_id"
                                :icon-id="slot.icon_id"
                                class="mini-icon"
                            />
                            <span>
                                <strong>{{ slot.player_name }}</strong>
                                {{ slot.item_name }} ·
                                {{ locationLabel(slot.location) }} #{{
                                    slot.slot
                                }}
                                · SL {{ slot.quantity }}
                            </span>
                        </button>
                    </div>
                    <div
                        v-else-if="globalInventory.searched"
                        class="muted global-empty"
                    >
                        Không tìm thấy item phù hợp.
                    </div>
                </div>
            </section>

            <section class="card player-detail-card">
                <div v-if="!detail && !detailLoading" class="empty-detail">
                    <span class="mi">person_search</span>
                    Chọn một nhân vật để xem hành trang và chỉnh dữ liệu.
                </div>

                <template v-if="detail">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow">Chi tiết</span>
                            <h3>
                                {{ detail.summary.name }}
                                <small>#{{ detail.summary.id }}</small>
                            </h3>
                        </div>
                        <button
                            class="btn btn-primary"
                            :disabled="savingStats"
                            @click="saveStats"
                        >
                            <span class="mi">save</span>
                            Lưu chỉ số
                        </button>
                    </div>

                    <div class="stats-grid">
                        <label>
                            <span class="form-label">Tên nhân vật</span>
                            <input
                                v-model.trim="detail.form.name"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Head</span>
                            <input
                                v-model.number="detail.form.head"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Sức mạnh</span>
                            <input
                                v-model.number="detail.form.power"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Tiềm năng</span>
                            <input
                                v-model.number="detail.form.potential"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Vàng</span>
                            <input
                                v-model.number="detail.form.gold"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Ngọc xanh</span>
                            <input
                                v-model.number="detail.form.gem"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Ruby</span>
                            <input
                                v-model.number="detail.form.ruby"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">HP gốc</span>
                            <input
                                v-model.number="detail.form.hp"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">KI gốc</span>
                            <input
                                v-model.number="detail.form.ki"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Sức đánh</span>
                            <input
                                v-model.number="detail.form.dame"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Giáp</span>
                            <input
                                v-model.number="detail.form.def"
                                type="number"
                                class="form-input"
                            />
                        </label>
                        <label>
                            <span class="form-label">Chí mạng</span>
                            <input
                                v-model.number="detail.form.crit"
                                type="number"
                                class="form-input"
                            />
                        </label>
                    </div>

                    <div class="tool-box danger-box">
                        <div class="tool-title">
                            <span class="mi">delete_sweep</span>
                            Thu hồi vật phẩm
                        </div>
                        <div class="tool-fields revoke-fields">
                            <label>
                                <span class="form-label">Item ID</span>
                                <input
                                    v-model.number="revoke.temp_id"
                                    type="number"
                                    class="form-input"
                                    placeholder="VD: 457"
                                />
                            </label>
                            <label>
                                <span class="form-label">Vị trí</span>
                                <select
                                    v-model="revoke.location"
                                    class="form-input"
                                >
                                    <option value="all">Tất cả</option>
                                    <option value="bag">Túi</option>
                                    <option value="box">Rương</option>
                                    <option value="body">Đang mặc</option>
                                </select>
                            </label>
                        </div>
                        <button
                            class="btn btn-danger"
                            :disabled="inventorySaving"
                            @click="revokeInventory"
                        >
                            <span class="mi">delete</span>
                            <span>Thu hồi khỏi nhân vật</span>
                        </button>
                    </div>

                    <div class="inventory-head">
                        <div>
                            <span class="eyebrow">Hành trang</span>
                            <h3>Body / Túi / Rương</h3>
                        </div>
                        <div class="location-tabs">
                            <button
                                v-for="tab in inventoryTabs"
                                :key="tab.key"
                                :class="{ active: inventoryTab === tab.key }"
                                @click="inventoryTab = tab.key"
                            >
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="inventory-search">
                        <span class="mi">search</span>
                        <input
                            v-model.trim="inventoryQuery"
                            class="form-input"
                            placeholder="Lọc item trong nhân vật hiện tại theo ID..."
                        />
                    </div>

                    <div class="inventory-grid">
                        <div
                            v-for="slot in filteredInventory"
                            :key="slot.location + '-' + slot.slot"
                            class="inventory-slot"
                            :class="{ empty: slot.item_id < 0 }"
                            @click="prepareRevoke(slot)"
                        >
                            <span class="slot-index">#{{ slot.slot }}</span>
                            <AdminIcon
                                v-if="slot.icon_id"
                                :icon-id="slot.icon_id"
                                class="slot-icon"
                            />
                            <span v-else class="mi slot-empty"
                                >inventory_2</span
                            >
                            <div>
                                <strong>{{
                                    slot.item_id >= 0
                                        ? slot.item_name
                                        : "Ô trống"
                                }}</strong>
                                <span
                                    >ID {{ slot.item_id }} · SL
                                    {{ slot.quantity }}</span
                                >
                                <span v-if="slot.options.length"
                                    >{{ slot.options.length }} option</span
                                >
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </div>
</template>

<script>
import { readJsonResponse } from "../../shared/api";
import { csrfToken } from "../../shared/format";

export default {
    data() {
        return {
            players: [],
            search: "",
            page: 1,
            total: 0,
            totalPages: 1,
            loading: false,
            detailLoading: false,
            savingStats: false,
            inventorySaving: false,
            selectedId: null,
            detail: null,
            inventoryTab: "bag",
            inventoryQuery: "",
            globalInventory: {
                search: "",
                loading: false,
                searched: false,
                results: [],
            },
            error: "",
            success: "",
            searchTimer: null,
            buff: {
                temp_id: "",
                quantity: 1,
                location: "bag",
                optionsText: "",
            },
            revoke: {
                temp_id: "",
                location: "all",
            },
            inventoryTabs: [
                { key: "bag", label: "Túi" },
                { key: "box", label: "Rương" },
                { key: "body", label: "Đang mặc" },
            ],
        };
    },
    computed: {
        currentInventory() {
            return this.detail?.inventory?.[this.inventoryTab] || [];
        },
        filteredInventory() {
            const query = String(this.inventoryQuery || "").trim();
            if (!query) return this.currentInventory;
            return this.currentInventory.filter((slot) =>
                String(slot.item_id).includes(query),
            );
        },
    },
    created() {
        this.loadPlayers(1);
    },
    beforeUnmount() {
        window.clearTimeout(this.searchTimer);
    },
    methods: {
        formatNumber(value) {
            return Number(value || 0).toLocaleString("vi-VN");
        },
        locationLabel(value) {
            return (
                {
                    body: "Đang mặc",
                    bag: "Túi",
                    box: "Rương",
                }[value] || value
            );
        },
        debouncedLoad() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(
                () => this.loadPlayers(1),
                300,
            );
        },
        async loadPlayers(page = 1) {
            this.loading = true;
            this.error = "";
            try {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: "30",
                });
                if (this.search) params.set("search", this.search);
                const res = await fetch(`/admin/api/players?${params}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(
                    res,
                    "Không thể tải người chơi",
                );
                this.players = data.data || [];
                this.page = data.page || page;
                this.total = data.total || 0;
                this.totalPages = data.total_pages || 1;
                if (!this.selectedId && this.players.length) {
                    this.selectPlayer(this.players[0].id);
                }
            } catch (error) {
                this.error = error?.message || "Không thể tải người chơi";
            } finally {
                this.loading = false;
            }
        },
        async selectPlayer(id) {
            this.selectedId = id;
            this.detailLoading = true;
            this.error = "";
            try {
                const res = await fetch(`/admin/api/players/${id}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(
                    res,
                    "Không thể tải nhân vật",
                );
                this.detail = data.data;
                this.inventoryQuery = "";
            } catch (error) {
                this.error = error?.message || "Không thể tải nhân vật";
            } finally {
                this.detailLoading = false;
            }
        },
        async saveStats() {
            if (!this.detail) return;
            this.savingStats = true;
            this.error = "";
            try {
                const res = await fetch(
                    `/admin/api/players/${this.selectedId}/stats`,
                    {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": csrfToken(),
                        },
                        body: JSON.stringify(this.detail.form),
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể lưu nhân vật",
                );
                this.detail = data.data;
                this.success = data.message || "Đã lưu nhân vật";
                await this.loadPlayers(this.page);
            } catch (error) {
                this.error = error?.message || "Không thể lưu nhân vật";
            } finally {
                this.savingStats = false;
            }
        },
        parseOptionsText() {
            const text = String(this.buff.optionsText || "").trim();
            if (!text) return [];
            const parsed = JSON.parse(text);
            if (!Array.isArray(parsed))
                throw new Error("Options phải là JSON array");
            return parsed;
        },
        async buffInventory() {
            if (!this.selectedId) return;
            this.inventorySaving = true;
            this.error = "";
            try {
                const res = await fetch(
                    `/admin/api/players/${this.selectedId}/inventory/buff`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": csrfToken(),
                        },
                        body: JSON.stringify({
                            temp_id: this.buff.temp_id,
                            quantity: this.buff.quantity,
                            location: this.buff.location,
                            options: this.parseOptionsText(),
                        }),
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể buff vật phẩm",
                );
                this.detail = data.data;
                this.success = data.warning
                    ? `${data.message}. ${data.warning}`
                    : data.message || "Đã buff vật phẩm";
                await this.loadPlayers(this.page);
            } catch (error) {
                this.error = error?.message || "Không thể buff vật phẩm";
            } finally {
                this.inventorySaving = false;
            }
        },
        async revokeInventory() {
            if (!this.selectedId) return;
            if (
                !window.confirm(
                    "Thu hồi item này khỏi nhân vật? Thao tác này sẽ ghi trực tiếp vào DB.",
                )
            ) {
                return;
            }
            this.inventorySaving = true;
            this.error = "";
            try {
                const res = await fetch(
                    `/admin/api/players/${this.selectedId}/inventory/revoke`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": csrfToken(),
                        },
                        body: JSON.stringify(this.revoke),
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể thu hồi vật phẩm",
                );
                this.detail = data.data;
                this.success = data.warning
                    ? `${data.message}. ${data.warning}`
                    : data.message || "Đã thu hồi vật phẩm";
                await this.loadPlayers(this.page);
            } catch (error) {
                this.error = error?.message || "Không thể thu hồi vật phẩm";
            } finally {
                this.inventorySaving = false;
            }
        },
        prepareRevoke(slot) {
            if (!slot || slot.item_id < 0) return;
            this.revoke.temp_id = slot.item_id;
            this.revoke.location = slot.location;
        },
        async searchInventoryGlobal() {
            const query = String(this.globalInventory.search || "").trim();
            if (!query) {
                this.globalInventory.results = [];
                this.globalInventory.searched = false;
                return;
            }

            this.globalInventory.loading = true;
            this.globalInventory.searched = true;
            this.error = "";
            try {
                const params = new URLSearchParams({
                    search: query,
                    limit: "120",
                });
                const res = await fetch(
                    `/admin/api/players/inventory/search?${params}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể kiểm tra hành trang",
                );
                this.globalInventory.results = data.data || [];
            } catch (error) {
                this.error = error?.message || "Không thể kiểm tra hành trang";
                this.globalInventory.results = [];
            } finally {
                this.globalInventory.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.players-page {
    --players-card-pad: 18px;
}
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
}
.page-title {
    margin: 0 0 6px;
    color: var(--ds-text-emphasis);
    font-size: 26px;
    line-height: 1.15;
}
.breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    color: var(--ds-text-muted);
    font-size: 15px;
    line-height: 1.35;
}
.breadcrumb a {
    color: var(--ds-primary);
    text-decoration: none;
}
.breadcrumb .current {
    color: var(--ds-text-emphasis);
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
.players-layout {
    display: grid;
    grid-template-columns: minmax(720px, 1.08fr) minmax(640px, 0.92fr);
    gap: 20px;
    align-items: start;
}
.card {
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: var(--players-card-pad);
}
.section-head,
.inventory-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}
.section-head h3,
.inventory-head h3 {
    color: var(--ds-text-emphasis);
    font-size: 18px;
    line-height: 1.25;
    margin: 2px 0 0;
}
.section-head small {
    color: var(--ds-text-muted);
}
.eyebrow {
    color: var(--ds-primary);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
}
.pill,
.badge {
    border-radius: 999px;
    padding: 4px 9px;
    background: rgba(var(--ds-primary-rgb), 0.14);
    color: var(--ds-primary-lighter);
    font-size: 12px;
    font-weight: 700;
}
.badge.danger {
    background: rgba(var(--ds-danger-rgb), 0.16);
    color: var(--ds-danger);
}
.badge.success {
    background: rgba(var(--ds-success-rgb), 0.16);
    color: var(--ds-success);
}
.search-row,
.inventory-search {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.search-row .mi,
.inventory-search .mi {
    color: var(--ds-text-muted);
}
.players-table-wrap {
    max-height: calc(100vh - 292px);
    overflow: auto;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th,
td {
    padding: 14px 14px;
    border-bottom: 1px dashed var(--ds-border);
    text-align: left;
    vertical-align: middle;
}
th:nth-child(1),
td:nth-child(1) {
    width: 72px;
}
th:nth-child(2),
td:nth-child(2) {
    min-width: 150px;
}
th:nth-child(3),
td:nth-child(3) {
    min-width: 140px;
}
th:nth-child(4),
td:nth-child(4) {
    min-width: 190px;
}
th:nth-child(5),
td:nth-child(5) {
    width: 96px;
}
th {
    color: #99bee8;
    font-size: 12px;
    text-transform: uppercase;
}
tbody tr {
    cursor: pointer;
}
tbody tr:hover,
tbody tr.active {
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.player-name {
    color: var(--ds-text-emphasis);
    font-weight: 800;
    margin-bottom: 2px;
}
.muted {
    color: var(--ds-text-muted);
    font-size: 12px;
    line-height: 1.35;
}
.empty-cell,
.empty-detail {
    color: var(--ds-text-muted);
    text-align: center;
    padding: 42px 16px;
}
.empty-detail .mi {
    display: block;
    font-size: 42px;
    margin-bottom: 8px;
}
.pagination.compact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 14px;
}
.global-inventory {
    margin-top: 18px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 14px;
    background: rgba(8, 13, 18, 0.26);
}
.global-search-form {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
}
.global-results {
    display: grid;
    gap: 8px;
    max-height: 260px;
    overflow: auto;
    margin-top: 12px;
}
.global-result {
    display: grid;
    grid-template-columns: 30px 1fr;
    gap: 9px;
    align-items: center;
    width: 100%;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 8px;
    background: rgba(11, 17, 23, 0.72);
    color: var(--ds-text);
    text-align: left;
    cursor: pointer;
}
.global-result:hover {
    border-color: var(--ds-primary);
}
.global-result strong {
    color: var(--ds-text-emphasis);
}
.mini-icon {
    width: 28px;
    height: 28px;
}
.global-empty {
    margin-top: 10px;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.tools-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    margin: 18px 0;
}
.tools-grid.revoke-only {
    max-width: 620px;
}
.tool-box {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 14px;
    background: rgba(8, 13, 18, 0.32);
}
.tool-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--ds-text-emphasis);
    font-weight: 800;
    line-height: 1.3;
    margin-bottom: 12px;
}
.tool-title .mi {
    color: var(--ds-primary);
}
.tool-fields {
    display: grid;
    grid-template-columns: 1fr 0.8fr 0.8fr;
    gap: 14px;
}
.revoke-fields {
    grid-template-columns: minmax(180px, 1fr) minmax(160px, 0.8fr);
    align-items: end;
    margin-bottom: 14px;
}
.options-text {
    min-height: 72px;
    margin: 10px 0;
    resize: vertical;
}
.location-tabs {
    display: inline-flex;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    overflow: hidden;
}
.location-tabs button {
    border: 0;
    background: transparent;
    color: var(--ds-text);
    padding: 9px 13px;
    cursor: pointer;
    font-weight: 700;
}
.location-tabs button.active {
    background: rgba(var(--ds-primary-rgb), 0.18);
    color: var(--ds-primary-lighter);
}
.inventory-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 10px;
    max-height: 520px;
    overflow: auto;
    padding-right: 4px;
}
.inventory-slot {
    position: relative;
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 10px;
    align-items: center;
    min-height: 76px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 12px;
    background: rgba(11, 17, 23, 0.5);
    cursor: pointer;
}
.inventory-slot:hover {
    border-color: var(--ds-primary);
}
.inventory-slot.empty {
    opacity: 0.58;
}
.slot-index {
    position: absolute;
    top: 6px;
    right: 8px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.slot-icon {
    width: 36px;
    height: 36px;
}
.slot-empty {
    color: var(--ds-text-muted);
    font-size: 32px;
}
.inventory-slot strong,
.inventory-slot span {
    display: block;
}
.inventory-slot strong {
    color: var(--ds-text-emphasis);
    font-size: 13px;
}
.inventory-slot span {
    color: var(--ds-text-muted);
    font-size: 12px;
}
@media (max-width: 1500px) {
    .players-layout,
    .tools-grid {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 720px) {
    .page-top {
        flex-direction: column;
        align-items: stretch;
    }
    .page-top .btn {
        width: fit-content;
    }
    .stats-grid,
    .tool-fields,
    .revoke-fields {
        grid-template-columns: 1fr;
    }
}
</style>
