<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">Sửa Tab Shop</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <router-link :to="{ name: 'admin.shops' }"
                        >Shop</router-link
                    >
                    <span>/</span>
                    <span class="current"
                        >{{ shopName }} — {{ form.tab_name || "..." }}</span
                    >
                </nav>
            </div>
            <router-link :to="{ name: 'admin.shops' }" class="btn btn-outline">
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
                        <div class="card-header"><h3>Thông tin Tab</h3></div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">Tên tab</label>
                                <input
                                    v-model="form.tab_name"
                                    class="form-input"
                                    placeholder="Tên tab..."
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tab index</label>
                                <input
                                    v-model.number="form.tab_index"
                                    class="form-input"
                                    type="number"
                                    min="0"
                                />
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
                                >
                                Vật phẩm
                            </h3>
                            <div class="card-header-actions">
                                <span class="item-count"
                                    >{{ items.length }} vật phẩm</span
                                >
                                <div class="view-toggle">
                                    <button
                                        type="button"
                                        class="view-toggle-btn"
                                        :class="{
                                            active: viewMode === 'table',
                                        }"
                                        @click="viewMode = 'table'"
                                        title="Dạng bảng"
                                    >
                                        <span class="mi" style="font-size: 18px"
                                            >table_rows</span
                                        >
                                    </button>
                                    <button
                                        type="button"
                                        class="view-toggle-btn"
                                        :class="{ active: viewMode === 'card' }"
                                        @click="viewMode = 'card'"
                                        title="Dạng thẻ"
                                    >
                                        <span class="mi" style="font-size: 18px"
                                            >grid_view</span
                                        >
                                    </button>
                                </div>
                            </div>
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

                        <!-- ═══ TABLE VIEW ═══ -->
                        <div
                            v-if="items.length && viewMode === 'table'"
                            class="items-table-wrap"
                        >
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th class="th-idx">#</th>
                                        <th class="th-icon"></th>
                                        <th class="th-name">Vật phẩm</th>
                                        <th class="th-num">Giá</th>
                                        <th class="th-type">Loại</th>
                                        <th class="th-num">Spec</th>
                                        <th class="th-check">Mới</th>
                                        <th class="th-check">Bán</th>
                                        <th class="th-opts">Options</th>
                                        <th class="th-act"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, idx) in items"
                                        :key="'t' + idx"
                                    >
                                        <td class="td-idx">{{ idx + 1 }}</td>
                                        <td class="td-icon">
                                            <img
                                                :src="
                                                    iconBase +
                                                    item.icon_id +
                                                    '.png'
                                                "
                                                @error="
                                                    $event.target.style.visibility =
                                                        'hidden'
                                                "
                                            />
                                        </td>
                                        <td class="td-name">
                                            <div class="t-name">
                                                {{ item.name }}
                                            </div>
                                            <div class="t-id">
                                                ID: {{ item.temp_id }}
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                v-model.number="item.cost"
                                                type="number"
                                                class="form-input input-sm t-input"
                                                min="0"
                                            />
                                        </td>
                                        <td>
                                            <select
                                                v-model.number="item.type_sell"
                                                class="form-input input-sm t-input"
                                            >
                                                <option :value="0">Gold</option>
                                                <option :value="1">Ngọc</option>
                                                <option :value="3">
                                                    Đặc biệt
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="spec-cell">
                                                <img
                                                    v-if="item.item_spec"
                                                    class="spec-icon"
                                                    :src="
                                                        iconBase +
                                                        specIconId(
                                                            item.item_spec,
                                                        ) +
                                                        '.png'
                                                    "
                                                    @error="
                                                        $event.target.style.display =
                                                            'none'
                                                    "
                                                />
                                                <input
                                                    v-model.number="
                                                        item.item_spec
                                                    "
                                                    type="number"
                                                    class="form-input input-sm t-input"
                                                    min="0"
                                                />
                                            </div>
                                        </td>
                                        <td class="td-check">
                                            <input
                                                type="checkbox"
                                                v-model="item.is_new"
                                            />
                                        </td>
                                        <td class="td-check">
                                            <input
                                                type="checkbox"
                                                v-model="item.is_sell"
                                            />
                                        </td>
                                        <td class="td-opts">
                                            <div class="t-opts-list">
                                                <span
                                                    v-for="(
                                                        opt, oi
                                                    ) in item.options.filter(
                                                        (o) => !o._pending,
                                                    )"
                                                    :key="oi"
                                                    class="t-opt-pill"
                                                >
                                                    {{
                                                        optionLabel(
                                                            opt.id,
                                                            opt.param,
                                                        )
                                                    }}
                                                    <button
                                                        type="button"
                                                        class="t-opt-rm"
                                                        @click="
                                                            item.options.splice(
                                                                item.options.indexOf(
                                                                    opt,
                                                                ),
                                                                1,
                                                            )
                                                        "
                                                    >
                                                        &times;
                                                    </button>
                                                </span>
                                                <button
                                                    v-if="
                                                        !item.options.some(
                                                            (o) => o._pending,
                                                        )
                                                    "
                                                    type="button"
                                                    class="t-opt-add"
                                                    @click="
                                                        item.options.push({
                                                            id: 0,
                                                            param: 0,
                                                            search: '',
                                                            showDrop: false,
                                                            _pending: true,
                                                        })
                                                    "
                                                    title="Thêm option"
                                                >
                                                    <span
                                                        class="mi"
                                                        style="font-size: 14px"
                                                        >add</span
                                                    >
                                                </button>
                                            </div>
                                            <!-- Inline option editor for pending -->
                                            <div
                                                v-if="
                                                    item.options.some(
                                                        (o) => o._pending,
                                                    )
                                                "
                                                class="t-opt-editor"
                                            >
                                                <div class="option-select-wrap">
                                                    <input
                                                        v-model="
                                                            pendingOpt(item)
                                                                .search
                                                        "
                                                        class="form-input input-sm"
                                                        placeholder="Tìm chỉ số..."
                                                        autocomplete="off"
                                                        @focus="
                                                            pendingOpt(
                                                                item,
                                                            ).showDrop = true
                                                        "
                                                        @input="
                                                            pendingOpt(
                                                                item,
                                                            ).showDrop = true
                                                        "
                                                    />
                                                    <div
                                                        v-if="
                                                            pendingOpt(item)
                                                                .showDrop
                                                        "
                                                        class="option-dropdown"
                                                    >
                                                        <div
                                                            v-for="o in filteredOptions(
                                                                pendingOpt(item)
                                                                    .search,
                                                            )"
                                                            :key="o.id"
                                                            class="option-dropdown-item"
                                                            @mousedown.prevent="
                                                                selectOption(
                                                                    pendingOpt(
                                                                        item,
                                                                    ),
                                                                    o,
                                                                )
                                                            "
                                                        >
                                                            <span
                                                                class="opt-id"
                                                                >{{
                                                                    o.id
                                                                }}</span
                                                            >
                                                            <span>{{
                                                                o.name
                                                            }}</span>
                                                        </div>
                                                        <div
                                                            v-if="
                                                                !filteredOptions(
                                                                    pendingOpt(
                                                                        item,
                                                                    ).search,
                                                                ).length
                                                            "
                                                            class="option-dropdown-empty"
                                                        >
                                                            Không tìm thấy
                                                        </div>
                                                    </div>
                                                </div>
                                                <input
                                                    v-model.number="
                                                        pendingOpt(item).param
                                                    "
                                                    type="number"
                                                    class="form-input input-sm param-input"
                                                    placeholder="Param"
                                                />
                                                <button
                                                    type="button"
                                                    class="t-opt-confirm"
                                                    @click="confirmOption(item)"
                                                    title="Xác nhận"
                                                >
                                                    <span
                                                        class="mi"
                                                        style="font-size: 16px"
                                                        >check</span
                                                    >
                                                </button>
                                            </div>
                                        </td>
                                        <td class="td-act">
                                            <button
                                                type="button"
                                                class="item-remove-btn"
                                                @click="items.splice(idx, 1)"
                                                title="Xoá"
                                            >
                                                <span
                                                    class="mi"
                                                    style="font-size: 18px"
                                                    >delete</span
                                                >
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ═══ CARD VIEW ═══ -->
                        <div
                            v-if="items.length && viewMode === 'card'"
                            class="items-grid"
                        >
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
                                <!-- Shop-specific fields -->
                                <div class="item-card-fields">
                                    <div class="form-row-3">
                                        <div class="item-card-field">
                                            <label>Giá (cost)</label>
                                            <input
                                                v-model.number="item.cost"
                                                type="number"
                                                class="form-input input-sm"
                                                min="0"
                                            />
                                        </div>
                                        <div class="item-card-field">
                                            <label>Loại bán</label>
                                            <select
                                                v-model.number="item.type_sell"
                                                class="form-input input-sm"
                                            >
                                                <option :value="0">Gold</option>
                                                <option :value="1">Ngọc</option>
                                                <option :value="3">
                                                    Đặc biệt
                                                </option>
                                            </select>
                                        </div>
                                        <div class="item-card-field">
                                            <label>Item Spec</label>
                                            <div class="spec-cell">
                                                <img
                                                    v-if="item.item_spec"
                                                    class="spec-icon"
                                                    :src="
                                                        iconBase +
                                                        specIconId(
                                                            item.item_spec,
                                                        ) +
                                                        '.png'
                                                    "
                                                    @error="
                                                        $event.target.style.display =
                                                            'none'
                                                    "
                                                />
                                                <input
                                                    v-model.number="
                                                        item.item_spec
                                                    "
                                                    type="number"
                                                    class="form-input input-sm"
                                                    min="0"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row-2">
                                        <div class="item-card-field">
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    v-model="item.is_new"
                                                />
                                                Mới (is_new)
                                            </label>
                                        </div>
                                        <div class="item-card-field">
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    v-model="item.is_sell"
                                                />
                                                Bán (is_sell)
                                            </label>
                                        </div>
                                    </div>
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
                                                    search: '',
                                                    showDrop: false,
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
                                Tìm kiếm ở trên để thêm vật phẩm vào shop
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══ RIGHT: Sidebar (1/3) ═══ -->
                <div class="form-sidebar">
                    <!-- Save card -->
                    <div class="card">
                        <div class="card-header"><h3>Lưu</h3></div>
                        <button
                            class="btn btn-primary btn-block"
                            :disabled="saving"
                            style="margin-top: 8px"
                        >
                            <span class="mi" style="font-size: 16px">save</span>
                            {{ saving ? "Đang lưu..." : "Lưu thay đổi" }}
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
                        </div>
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
            form: { tab_name: "", tab_index: 0 },
            shopName: "",
            viewMode: "table",
            items: [],
            allOptions: [],
            specIconMap: {},
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
        tabId() {
            return this.$route.params.tabId;
        },
    },
    created() {
        this.loadOptions();
        this.loadTab();
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
            this.items.forEach((item) => {
                item.options.forEach((opt) => {
                    if (!e.target.closest(".option-select-wrap")) {
                        opt.showDrop = false;
                    }
                });
            });
        },
        fixJson(str) {
            if (typeof str !== "string") return str;
            let s = str.trim();
            s = s.replace(/,\s*([\]\}])/g, "$1");
            s = s.replace(/([\[\{])\s*,/g, "$1");
            s = s.replace(/,\s*,/g, ",");
            return s;
        },
        optionName(id) {
            const o = this.allOptions.find((a) => a.id === id);
            return o ? o.name : null;
        },
        optionLabel(id, param) {
            const o = this.allOptions.find((a) => a.id === id);
            if (!o) return String(param);
            return o.name.includes("#")
                ? o.name.replace("#", param)
                : o.name + ": " + param;
        },
        specIconId(specId) {
            if (!specId || specId <= 0) return specId;
            if (this.specIconMap[specId] !== undefined)
                return this.specIconMap[specId];
            // Try to fetch it lazily
            this.specIconMap[specId] = specId; // fallback to specId initially
            fetch(`/admin/api/items/batch?ids=${specId}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data[specId]) {
                        this.specIconMap = {
                            ...this.specIconMap,
                            [specId]: data[specId].icon_id,
                        };
                    }
                })
                .catch(() => {});
            return specId;
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
        confirmOption(item) {
            const opt = this.pendingOpt(item);
            if (opt._pending) delete opt._pending;
        },
        pendingOpt(item) {
            return (
                item.options.find((o) => o._pending) || {
                    search: "",
                    param: 0,
                    showDrop: false,
                }
            );
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
        async loadTab() {
            try {
                const res = await fetch(`/admin/api/shops/tab/${this.tabId}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (data.ok) {
                    const tab = data.data;
                    const shop = data.shop;
                    this.shopName = shop?.tag_name || "";
                    this.form.tab_name = tab.tab_name;
                    this.form.tab_index = tab.tab_index;

                    let detail = [];
                    try {
                        const raw =
                            typeof tab.items === "string"
                                ? tab.items
                                : JSON.stringify(tab.items);
                        detail = JSON.parse(this.fixJson(raw));
                        if (!Array.isArray(detail)) detail = [];
                    } catch {
                        detail = [];
                    }

                    // Batch fetch item info
                    const itemIds = detail
                        .map((d) => d.temp_id)
                        .filter((id) => id !== undefined && id !== null);
                    const specIds = detail
                        .map((d) => d.item_spec)
                        .filter((id) => id && id > 0);
                    const allIds = [...new Set([...itemIds, ...specIds])];
                    let itemMap = {};
                    if (allIds.length) {
                        try {
                            const batchRes = await fetch(
                                `/admin/api/items/batch?ids=${allIds.join(",")}`,
                                {
                                    headers: {
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                },
                            );
                            itemMap = await batchRes.json();
                        } catch {
                            // fallback
                        }
                    }

                    // Build spec icon map
                    for (const sid of specIds) {
                        if (itemMap[sid]) {
                            this.specIconMap[sid] = itemMap[sid].icon_id;
                        }
                    }

                    for (const d of detail) {
                        const itemInfo = itemMap[d.temp_id] || null;
                        this.items.push({
                            temp_id: d.temp_id,
                            name: itemInfo?.name || "Item #" + d.temp_id,
                            icon_id: itemInfo?.icon_id || d.temp_id,
                            cost: d.cost || 0,
                            type_sell: d.type_sell ?? 0,
                            is_new: !!d.is_new,
                            is_sell: d.is_sell !== false,
                            item_spec: d.item_spec || 0,
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
                cost: 0,
                type_sell: 0,
                is_new: false,
                is_sell: true,
                item_spec: 0,
                options: [],
            });
            this.itemQuery = "";
            this.searchResults = [];
            this.showResults = false;
        },
        buildItems() {
            return JSON.stringify(
                this.items.map((item) => ({
                    temp_id: item.temp_id,
                    cost: parseInt(item.cost) || 0,
                    type_sell: parseInt(item.type_sell) || 0,
                    is_new: !!item.is_new,
                    is_sell: item.is_sell !== false,
                    item_spec: parseInt(item.item_spec) || 0,
                    options: item.options
                        .filter((o) => !o._pending)
                        .map((o) => ({
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
                    tab_name: this.form.tab_name,
                    tab_index: this.form.tab_index,
                    items: this.buildItems(),
                };
                const res = await fetch(`/admin/api/shops/tab/${this.tabId}`, {
                    method: "PUT",
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
                } else {
                    this.error = data.message || "Lỗi";
                }
            } catch {
                this.error = "Lỗi kết nối";
            } finally {
                this.saving = false;
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
    grid-template-columns: 1fr 220px;
    gap: 20px;
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
    gap: 12px;
    position: sticky;
    top: 92px;
}
.form-sidebar .card {
    padding: 12px 14px;
}
.form-sidebar .card-header {
    margin-bottom: 6px;
}
.form-sidebar .card-header h3 {
    font-size: 13px;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
}
@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    .form-sidebar {
        position: static;
    }
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

.item-card-fields {
    margin-bottom: 12px;
}
.item-card-field {
    margin-bottom: 8px;
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
.item-card-field label input[type="checkbox"] {
    margin-right: 4px;
    vertical-align: middle;
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

/* ── Item count ── */
.item-count {
    font-size: 12px;
    color: var(--ds-text-muted);
    background: rgba(var(--ds-primary-rgb), 0.12);
    padding: 3px 10px;
    border-radius: 20px;
}

/* ── Card header actions ── */
.card-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── View toggle ── */
.view-toggle {
    display: flex;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    overflow: hidden;
}
.view-toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    color: var(--ds-text-muted);
    transition: all 0.15s;
}
.view-toggle-btn:hover {
    background: rgba(var(--ds-primary-rgb), 0.06);
}
.view-toggle-btn.active {
    background: rgba(var(--ds-primary-rgb), 0.14);
    color: var(--ds-primary);
}
.view-toggle-btn + .view-toggle-btn {
    border-left: 1px solid var(--ds-border);
}

/* ── Table view ── */
.items-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius);
}
.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.items-table th {
    background: var(--ds-surface-2);
    padding: 8px 10px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: var(--ds-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    border-bottom: 1px solid var(--ds-border);
}
.items-table td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--ds-border);
    vertical-align: middle;
}
.items-table tr:last-child td {
    border-bottom: none;
}
.items-table tr:hover td {
    background: rgba(var(--ds-primary-rgb), 0.03);
}
.th-idx {
    width: 36px;
    text-align: center;
}
.th-icon {
    width: 40px;
}
.th-name {
    min-width: 120px;
}
.th-num {
    width: 90px;
}
.th-type {
    width: 90px;
}
.th-check {
    width: 40px;
    text-align: center;
}
.th-opts {
    min-width: 160px;
}
.th-act {
    width: 40px;
}
.td-idx {
    text-align: center;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.td-icon img {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--ds-gray-100);
}
.td-name .t-name {
    font-weight: 600;
    font-size: 13px;
    color: var(--ds-text-emphasis);
}
.td-name .t-id {
    font-size: 11px;
    color: var(--ds-text-muted);
}
.td-check {
    text-align: center;
}
.td-check input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.td-act {
    text-align: center;
}
.td-num {
    text-align: center;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.t-input {
    width: 100% !important;
    min-width: 80px;
}
.spec-cell {
    display: flex;
    align-items: center;
    gap: 4px;
}
.spec-icon {
    width: 22px;
    height: 22px;
    border-radius: 4px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.t-opts-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}
.t-opt-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-text);
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    white-space: nowrap;
}
.t-opt-rm {
    background: none;
    border: none;
    color: var(--ds-text-muted);
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    padding: 0 2px;
}
.t-opt-rm:hover {
    color: var(--ds-danger);
}
.t-opt-add {
    display: inline-flex;
    align-items: center;
    background: none;
    border: 1px dashed rgba(var(--ds-primary-rgb), 0.4);
    color: var(--ds-primary);
    border-radius: 12px;
    padding: 1px 6px;
    cursor: pointer;
    transition: all 0.15s;
}
.t-opt-add:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
    border-color: var(--ds-primary);
}
.t-opt-editor {
    display: flex;
    gap: 6px;
    margin-top: 4px;
    align-items: flex-start;
}
.t-opt-confirm {
    background: var(--ds-primary, #4f8cff);
    color: #fff;
    border: none;
    border-radius: 4px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
}
.t-opt-confirm:hover {
    opacity: 0.85;
}
.td-opts {
    min-width: 160px;
}
</style>
