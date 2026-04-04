<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? "Sửa Giftcode" : "Tạo Giftcode mới" }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <router-link :to="{ name: 'admin.giftcodes' }"
                        >Giftcode</router-link
                    >
                    <span>/</span>
                    <span class="current">{{
                        isEdit ? form.code || "..." : "Tạo mới"
                    }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.giftcodes' }"
                class="btn btn-outline"
            >
                <span class="mi" style="font-size: 16px">arrow_back</span> Quay
                lại
            </router-link>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <form @submit.prevent="save">
            <div class="form-layout">
                <!-- ═══ LEFT: Main Content (2/3) ═══ -->
                <div class="form-main">
                    <!-- Basic info card -->
                    <div class="card">
                        <div class="card-header"><h3>Thông tin cơ bản</h3></div>
                        <div class="form-group">
                            <label class="form-label"
                                >Mã code <span class="required">*</span></label
                            >
                            <input
                                v-model="form.code"
                                class="form-input"
                                required
                                placeholder="Nhập mã giftcode..."
                            />
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label"
                                    >Số lượng (count_left)</label
                                >
                                <input
                                    v-model.number="form.count_left"
                                    class="form-input"
                                    type="number"
                                    min="0"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    >MTV (1 lần/tài khoản)</label
                                >
                                <div class="toggle-row">
                                    <label class="toggle">
                                        <input
                                            type="checkbox"
                                            v-model="form.mtv"
                                            true-value="1"
                                            false-value="0"
                                        />
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="toggle-label">{{
                                        form.mtv == 1 ? "Bật" : "Tắt"
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <span
                                    class="mi"
                                    style="
                                        font-size: 18px;
                                        vertical-align: middle;
                                        margin-right: 6px;
                                        color: var(--ds-info);
                                    "
                                    >inventory_2</span
                                >Vật phẩm
                            </h3>
                            <span class="item-count"
                                >{{ items.length }} vật phẩm</span
                            >
                        </div>

                        <!-- Item search -->
                        <div class="item-search-wrap">
                            <span class="mi search-icon">search</span>
                            <input
                                v-model="itemQuery"
                                class="form-input search-input"
                                placeholder="Tìm vật phẩm theo tên hoặc ID..."
                                autocomplete="off"
                                @input="searchItems"
                                @focus="showResults = true"
                            />
                            <div
                                v-if="showResults && searchResults.length"
                                class="item-search-results"
                            >
                                <div
                                    v-for="item in searchResults"
                                    :key="item.id"
                                    class="item-result"
                                    @click="addItem(item)"
                                >
                                    <img
                                        :src="iconBase + item.icon_id + '.png'"
                                        @error="
                                            $event.target.style.display = 'none'
                                        "
                                    />
                                    <div class="item-result-info">
                                        <div class="item-result-name">
                                            {{ item.name }}
                                        </div>
                                        <div class="item-result-id">
                                            ID: {{ item.id }}
                                        </div>
                                    </div>
                                    <span class="mi add-icon">add_circle</span>
                                </div>
                            </div>
                            <div
                                v-if="
                                    showResults &&
                                    itemQuery.length >= 1 &&
                                    !searchResults.length &&
                                    !searching
                                "
                                class="item-search-results"
                            >
                                <div class="no-result">
                                    Không tìm thấy vật phẩm
                                </div>
                            </div>
                        </div>

                        <!-- Items grid -->
                        <div v-if="items.length" class="items-grid">
                            <div
                                v-for="(item, idx) in items"
                                :key="idx"
                                class="item-card"
                            >
                                <div class="item-card-top">
                                    <div class="item-card-head">
                                        <img
                                            class="item-card-icon"
                                            :src="
                                                iconBase + item.icon_id + '.png'
                                            "
                                            @error="
                                                $event.target.style.visibility =
                                                    'hidden'
                                            "
                                        />
                                        <div class="item-card-title">
                                            <div class="item-card-name">
                                                {{ item.name }}
                                            </div>
                                            <div class="item-card-id">
                                                ID: {{ item.temp_id }}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="item-remove-btn"
                                        @click="items.splice(idx, 1)"
                                        title="Xoá vật phẩm"
                                    >
                                        <span class="mi">close</span>
                                    </button>
                                </div>
                                <div class="item-card-field">
                                    <label>Số lượng</label>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        class="form-input input-sm"
                                        min="1"
                                    />
                                </div>
                                <!-- Options -->
                                <div class="item-card-options">
                                    <div class="option-label-row">
                                        <label>Chỉ số (Options)</label>
                                        <button
                                            type="button"
                                            class="option-add-btn"
                                            @click="
                                                item.options.push({
                                                    id: 0,
                                                    param: 0,
                                                })
                                            "
                                        >
                                            <span
                                                class="mi"
                                                style="font-size: 14px"
                                                >add</span
                                            >
                                            Thêm
                                        </button>
                                    </div>
                                    <div
                                        v-for="(opt, oi) in item.options"
                                        :key="oi"
                                        class="option-row"
                                    >
                                        <div class="option-select-wrap">
                                            <input
                                                v-model="opt.search"
                                                class="form-input input-sm"
                                                placeholder="Tìm chỉ số..."
                                                autocomplete="off"
                                                @focus="opt.showDrop = true"
                                                @input="opt.showDrop = true"
                                            />
                                            <div
                                                v-if="opt.showDrop"
                                                class="option-dropdown"
                                            >
                                                <div
                                                    v-for="o in filteredOptions(
                                                        opt.search,
                                                    )"
                                                    :key="o.id"
                                                    class="option-dropdown-item"
                                                    @mousedown.prevent="
                                                        selectOption(opt, o)
                                                    "
                                                >
                                                    <span class="opt-id">{{
                                                        o.id
                                                    }}</span>
                                                    <span>{{ o.name }}</span>
                                                </div>
                                                <div
                                                    v-if="
                                                        !filteredOptions(
                                                            opt.search,
                                                        ).length
                                                    "
                                                    class="option-dropdown-empty"
                                                >
                                                    Không tìm thấy
                                                </div>
                                            </div>
                                        </div>
                                        <input
                                            v-model.number="opt.param"
                                            type="number"
                                            class="form-input input-sm param-input"
                                            placeholder="Param"
                                        />
                                        <button
                                            type="button"
                                            class="option-remove-btn"
                                            @click="item.options.splice(oi, 1)"
                                        >
                                            <span
                                                class="mi"
                                                style="font-size: 16px"
                                                >close</span
                                            >
                                        </button>
                                    </div>
                                    <div
                                        v-if="!item.options.length"
                                        class="no-options"
                                    >
                                        Chưa có chỉ số nào
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="empty-items">
                            <span
                                class="mi"
                                style="font-size: 40px; color: #2a3f5f"
                                >inventory_2</span
                            >
                            <p>Chưa có vật phẩm nào</p>
                            <p class="empty-sub">
                                Tìm kiếm ở trên để thêm vật phẩm vào giftcode
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══ RIGHT: Sidebar (1/3) ═══ -->
                <div class="form-sidebar">
                    <!-- Publish card -->
                    <div class="card">
                        <div class="card-header"><h3>Xuất bản</h3></div>
                        <div class="sidebar-field">
                            <label class="form-label">Trạng thái</label>
                            <div class="toggle-row">
                                <label class="toggle">
                                    <input
                                        type="checkbox"
                                        v-model="form.active"
                                        true-value="1"
                                        false-value="0"
                                    />
                                    <span class="toggle-slider"></span>
                                </label>
                                <span
                                    class="toggle-label"
                                    :class="form.active == 1 ? 'active-on' : ''"
                                >
                                    {{
                                        form.active == 1
                                            ? "Đang hoạt động"
                                            : "Đã tắt"
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="sidebar-field">
                            <label class="form-label">Ngày hết hạn</label>
                            <input
                                v-model="form.expired"
                                class="form-input"
                                type="date"
                            />
                        </div>
                        <button
                            class="btn btn-primary btn-block"
                            :disabled="saving"
                            style="margin-top: 16px"
                        >
                            <span class="mi" style="font-size: 16px">{{
                                isEdit ? "save" : "add"
                            }}</span>
                            {{
                                saving
                                    ? "Đang lưu..."
                                    : isEdit
                                      ? "Lưu thay đổi"
                                      : "Tạo giftcode"
                            }}
                        </button>
                    </div>

                    <!-- Summary card -->
                    <div class="card" v-if="items.length">
                        <div class="card-header"><h3>Tóm tắt</h3></div>
                        <div class="summary-list">
                            <div class="summary-row">
                                <span>Vật phẩm</span>
                                <span class="badge badge-info">{{
                                    items.length
                                }}</span>
                            </div>
                            <div class="summary-row">
                                <span>Tổng options</span>
                                <span class="badge badge-warning">{{
                                    items.reduce(
                                        (s, i) => s + i.options.length,
                                        0,
                                    )
                                }}</span>
                            </div>
                            <div class="summary-row">
                                <span>Tổng số lượng</span>
                                <span class="badge badge-success">{{
                                    items.reduce(
                                        (s, i) =>
                                            s + (parseInt(i.quantity) || 0),
                                        0,
                                    )
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Danger zone -->
                    <div class="card danger-card" v-if="isEdit">
                        <div class="card-header">
                            <h3 style="color: var(--ds-danger)">
                                Vùng nguy hiểm
                            </h3>
                        </div>
                        <p class="danger-text">
                            Xoá giftcode sẽ không thể hoàn tác.
                        </p>
                        <button
                            type="button"
                            class="btn btn-danger btn-block btn-sm"
                            @click="del"
                        >
                            <span class="mi" style="font-size: 14px"
                                >delete</span
                            >
                            Xoá giftcode
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            form: {
                code: "",
                count_left: 100,
                mtv: "0",
                expired: "",
                active: "1",
            },
            giftcode: {},
            items: [],
            allOptions: [],
            itemQuery: "",
            searchResults: [],
            showResults: false,
            searching: false,
            error: "",
            success: "",
            saving: false,
            iconBase: "/assets/frontend/home/v1/images/x4/",
            searchTimeout: null,
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
        },
    },
    created() {
        this.loadOptions();
        if (this.isEdit) {
            this.loadGiftcode();
        }
        document.addEventListener("click", this.closeResults);
    },
    unmounted() {
        document.removeEventListener("click", this.closeResults);
    },
    methods: {
        closeResults(e) {
            if (!e.target.closest(".item-search-wrap")) {
                this.showResults = false;
            }
            // Close option dropdowns
            this.items.forEach((item) => {
                item.options.forEach((opt) => {
                    if (!e.target.closest(".option-select-wrap")) {
                        opt.showDrop = false;
                    }
                });
            });
        },
        filteredOptions(search) {
            if (!search || !search.trim()) return this.allOptions.slice(0, 30);
            const q = search.trim().toLowerCase();
            return this.allOptions
                .filter(
                    (o) =>
                        o.name.toLowerCase().includes(q) ||
                        String(o.id).includes(q),
                )
                .slice(0, 20);
        },
        selectOption(opt, o) {
            opt.id = o.id;
            opt.search = `${o.name} (ID: ${o.id})`;
            opt.showDrop = false;
        },
        async loadOptions() {
            try {
                const res = await fetch("/admin/api/options", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                this.allOptions = data || [];
            } catch {
                //
            }
        },
        async loadGiftcode() {
            try {
                const res = await fetch(
                    `/admin/api/giftcodes/${this.$route.params.id}`,
                    { headers: { "X-Requested-With": "XMLHttpRequest" } },
                );
                const data = await res.json();
                if (data.ok) {
                    const gc = data.data;
                    this.giftcode = gc;
                    this.form.code = gc.code;
                    this.form.count_left = gc.count_left;
                    this.form.mtv = gc.mtv ? "1" : "0";
                    this.form.expired = gc.expired || "";
                    this.form.active = gc.active ? "1" : "0";

                    let detail = [];
                    try {
                        detail =
                            typeof gc.detail === "string"
                                ? JSON.parse(gc.detail)
                                : gc.detail;
                        if (!Array.isArray(detail)) detail = [];
                    } catch {
                        detail = [];
                    }

                    // Batch fetch all item info at once
                    const itemIds = detail
                        .map((d) => d.temp_id)
                        .filter(Boolean);
                    let itemMap = {};
                    if (itemIds.length) {
                        try {
                            const batchRes = await fetch(
                                `/admin/api/items/batch?ids=${itemIds.join(",")}`,
                                {
                                    headers: {
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                },
                            );
                            itemMap = await batchRes.json();
                        } catch {
                            // fallback: empty map
                        }
                    }

                    for (const d of detail) {
                        const itemInfo = itemMap[d.temp_id] || null;
                        this.items.push({
                            temp_id: d.temp_id,
                            name: itemInfo?.name || "Item #" + d.temp_id,
                            icon_id: itemInfo?.icon_id || d.temp_id,
                            quantity: d.quantity || 1,
                            options: (d.options || []).map((o) => {
                                const matched = this.allOptions.find(
                                    (ao) => ao.id === o.id,
                                );
                                return {
                                    id: o.id || 0,
                                    param: o.param || 0,
                                    search: matched
                                        ? `${matched.name} (ID: ${matched.id})`
                                        : o.id
                                          ? `ID: ${o.id}`
                                          : "",
                                    showDrop: false,
                                };
                            }),
                        });
                    }
                }
            } catch {
                this.error = "Không thể tải dữ liệu";
            }
        },
        async fetchItemById(id) {
            try {
                const res = await fetch(`/admin/api/items/search?q=${id}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                return data.length ? data[0] : null;
            } catch {
                return null;
            }
        },
        searchItems() {
            clearTimeout(this.searchTimeout);
            const q = this.itemQuery.trim();
            if (q.length < 1) {
                this.searchResults = [];
                this.showResults = false;
                return;
            }
            this.searching = true;
            this.searchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(
                        `/admin/api/items/search?q=${encodeURIComponent(q)}`,
                        {
                            headers: { "X-Requested-With": "XMLHttpRequest" },
                        },
                    );
                    this.searchResults = await res.json();
                    this.showResults = true;
                } catch {
                    this.searchResults = [];
                } finally {
                    this.searching = false;
                }
            }, 300);
        },
        addItem(item) {
            this.items.push({
                temp_id: item.id,
                name: item.name,
                icon_id: item.icon_id,
                quantity: 1,
                options: [],
            });
            this.itemQuery = "";
            this.searchResults = [];
            this.showResults = false;
        },
        buildDetail() {
            return JSON.stringify(
                this.items.map((item) => ({
                    temp_id: item.temp_id,
                    quantity: parseInt(item.quantity) || 1,
                    options: item.options.map((o) => ({
                        id: parseInt(o.id) || 0,
                        param: parseInt(o.param) || 0,
                    })),
                })),
            );
        },
        async save() {
            this.error = "";
            this.success = "";
            this.saving = true;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const body = {
                    code: this.form.code,
                    count_left: this.form.count_left,
                    mtv: parseInt(this.form.mtv),
                    expired: this.form.expired || null,
                    active: parseInt(this.form.active),
                    detail: this.buildDetail(),
                };
                const url = this.isEdit
                    ? `/admin/api/giftcodes/${this.$route.params.id}`
                    : "/admin/api/giftcodes";
                const method = this.isEdit ? "PUT" : "POST";
                const res = await fetch(url, {
                    method,
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.ok) {
                    this.success = data.message;
                    if (!this.isEdit)
                        this.$router.push({ name: "admin.giftcodes" });
                } else {
                    this.error = data.message || "Lỗi";
                }
            } catch {
                this.error = "Lỗi kết nối";
            } finally {
                this.saving = false;
            }
        },
        async del() {
            if (!confirm("Xoá giftcode này?")) return;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                await fetch(`/admin/api/giftcodes/${this.$route.params.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                });
                this.$router.push({ name: "admin.giftcodes" });
            } catch {
                this.error = "Lỗi kết nối";
            }
        },
    },
};
</script>

<style scoped>
/* ── Page header ── */
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
.required {
    color: var(--ds-danger);
}

/* ── 2-column layout ── */
.form-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}
.form-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.form-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 92px;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.sidebar-field {
    margin-bottom: 16px;
}
@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    .form-sidebar {
        position: static;
    }
}

/* ── Toggle ── */
.toggle-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 4px;
}
.toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--ds-gray-200);
    border-radius: 24px;
    transition: 0.3s;
}
.toggle-slider::before {
    content: "";
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: var(--ds-gray-400);
    border-radius: 50%;
    transition: 0.3s;
}
.toggle input:checked + .toggle-slider {
    background: var(--ds-primary);
}
.toggle input:checked + .toggle-slider::before {
    transform: translateX(20px);
    background: #fff;
}
.toggle-label {
    font-size: 13px;
    color: var(--ds-text-muted);
}
.toggle-label.active-on {
    color: var(--ds-success);
}

/* ── Item search ── */
.item-search-wrap {
    position: relative;
    margin-bottom: 16px;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
    z-index: 1;
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
}
.item-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 20;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius);
    max-height: 320px;
    overflow-y: auto;
    box-shadow: var(--ds-shadow-xl);
    margin-top: 4px;
}
.item-result {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    cursor: pointer;
    transition: background 0.15s;
    color: var(--ds-text);
}
.item-result:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.item-result img {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.item-result-info {
    flex: 1;
    min-width: 0;
}
.item-result-name {
    font-size: 14px;
    font-weight: 500;
}
.item-result-id {
    font-size: 11px;
    color: var(--ds-text-muted);
}
.add-icon {
    color: var(--ds-primary);
    font-size: 20px;
}
.no-result {
    padding: 16px;
    color: var(--ds-text-muted);
    font-size: 13px;
    text-align: center;
}

/* ── Items grid ── */
.items-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 900px) {
    .items-grid {
        grid-template-columns: 1fr;
    }
}

.item-card {
    background: var(--ds-body-bg);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius);
    padding: 16px;
    transition: border-color 0.2s;
}
.item-card:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.3);
}
.item-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12px;
}
.item-card-head {
    display: flex;
    align-items: center;
    gap: 12px;
}
.item-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.item-card-title {
    min-width: 0;
}
.item-card-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.item-card-id {
    font-size: 11px;
    color: var(--ds-text-muted);
    margin-top: 2px;
}
.item-remove-btn {
    background: none;
    border: none;
    color: var(--ds-text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
}
.item-remove-btn:hover {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}

.item-card-field {
    margin-bottom: 12px;
}
.item-card-field label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--ds-text-muted);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.input-sm {
    padding: 7px 10px !important;
    font-size: 13px !important;
}

/* ── Options ── */
.option-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.option-label-row label {
    font-size: 11px;
    font-weight: 600;
    color: var(--ds-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.option-add-btn {
    display: flex;
    align-items: center;
    gap: 2px;
    background: none;
    border: 1px dashed rgba(var(--ds-primary-rgb), 0.4);
    color: var(--ds-primary);
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
}
.option-add-btn:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
    border-color: var(--ds-primary);
}
.option-row {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 8px;
}
.option-select-wrap {
    position: relative;
    flex: 1;
    min-width: 0;
}
.option-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 30;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: var(--ds-shadow-xl);
    margin-top: 2px;
}
.option-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
    color: var(--ds-text);
    transition: background 0.15s;
}
.option-dropdown-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.opt-id {
    font-size: 11px;
    color: var(--ds-text-muted);
    min-width: 32px;
}
.option-dropdown-empty {
    padding: 12px;
    color: var(--ds-text-muted);
    font-size: 12px;
    text-align: center;
}
.param-input {
    width: 90px !important;
    flex-shrink: 0;
}
.option-remove-btn {
    background: none;
    border: none;
    color: var(--ds-text-muted);
    cursor: pointer;
    padding: 2px;
    border-radius: 4px;
    display: flex;
    transition: all 0.2s;
}
.option-remove-btn:hover {
    color: var(--ds-danger);
    background: rgba(var(--ds-danger-rgb), 0.1);
}
.no-options {
    font-size: 12px;
    color: var(--ds-gray-300);
    font-style: italic;
    padding: 4px 0;
}

/* ── Empty state ── */
.empty-items {
    text-align: center;
    padding: 40px 20px;
}
.empty-items p {
    color: var(--ds-text-muted);
    font-size: 14px;
    margin-top: 8px;
}
.empty-sub {
    font-size: 12px !important;
    color: var(--ds-gray-300) !important;
    margin-top: 4px !important;
}

/* ── Summary ── */
.summary-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    color: var(--ds-text);
}

/* ── Danger card ── */
.danger-card {
    border: 1px solid rgba(var(--ds-danger-rgb), 0.2) !important;
}
.danger-text {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-bottom: 12px;
}

/* ── Item count ── */
.item-count {
    font-size: 12px;
    color: var(--ds-text-muted);
    background: rgba(var(--ds-primary-rgb), 0.12);
    padding: 3px 10px;
    border-radius: 20px;
}
</style>
