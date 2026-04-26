<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? "Sửa mốc quà" : "Tạo mốc quà" }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <router-link
                        :to="{ name: 'admin.milestones', params: { type: currentType } }"
                    >
                        {{ currentTypeLabel }}
                    </router-link>
                    <span>/</span>
                    <span class="current">{{ isEdit ? "#" + form.id : "Tạo mới" }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.milestones', params: { type: currentType } }"
                class="btn btn-outline"
            >
                <span class="mi" style="font-size: 16px">arrow_back</span>
                Quay lại
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

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <form @submit.prevent="save">
            <div class="card">
                <div class="card-header">
                    <h3>Thông tin mốc quà</h3>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">ID mốc</label>
                        <input
                            v-model.number="form.id"
                            class="form-input"
                            type="number"
                            min="1"
                            :readonly="isEdit"
                            :placeholder="isEdit ? '' : 'Để trống để tự tăng'"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thông tin</label>
                        <input
                            v-model="form.info"
                            class="form-input"
                            required
                            placeholder="Ví dụ: Mốc nạp 100.000"
                        />
                    </div>
                </div>
            </div>

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
                        Quà vật phẩm
                    </h3>
                    <div class="card-header-actions">
                        <span class="item-count">{{ items.length }} vật phẩm</span>
                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            @click="openItemPicker"
                        >
                            <span class="mi" style="font-size: 15px">list</span>
                            Chọn item
                        </button>
                    </div>
                </div>

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
                            <AdminIcon
                                class="item-result-icon"
                                :icon-id="item.icon_id"
                            />
                            <div class="item-result-info">
                                <div class="item-result-name">{{ item.name }}</div>
                                <div class="item-result-id">ID: {{ item.id }}</div>
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
                        <div class="no-result">Không tìm thấy vật phẩm</div>
                    </div>
                </div>

                <div v-if="items.length" class="items-table-wrap">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="th-idx">#</th>
                                <th class="th-icon"></th>
                                <th class="th-name">Vật phẩm</th>
                                <th class="th-qty">Số lượng</th>
                                <th class="th-opts">Options</th>
                                <th class="th-act"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in items" :key="'item-' + idx">
                                <td class="td-idx">{{ idx + 1 }}</td>
                                <td class="td-icon">
                                    <AdminIcon
                                        class="table-item-icon"
                                        :icon-id="item.icon_id"
                                    />
                                </td>
                                <td class="td-name">
                                    <div class="t-name">{{ item.name }}</div>
                                    <div class="t-id">ID: {{ item.temp_id }}</div>
                                </td>
                                <td>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        class="form-input input-sm t-input"
                                        min="1"
                                    />
                                </td>
                                <td class="td-opts">
                                    <div class="t-opts-list">
                                        <span
                                            v-for="(opt, oi) in item.options.filter((o) => !o._pending)"
                                            :key="'opt-' + oi"
                                            class="t-opt-pill"
                                            @click="editOption(item, opt)"
                                            title="Bấm để sửa option"
                                        >
                                            {{ optionLabel(opt.id, opt.param) }}
                                            <button
                                                type="button"
                                                class="t-opt-rm"
                                                @click.stop="
                                                    item.options.splice(
                                                        item.options.indexOf(opt),
                                                        1,
                                                    )
                                                "
                                            >
                                                &times;
                                            </button>
                                        </span>
                                        <button
                                            v-if="!item.options.some((o) => o._pending)"
                                            type="button"
                                            class="t-opt-add"
                                            @click="addPendingOption(item)"
                                            title="Thêm option"
                                        >
                                            <span class="mi" style="font-size: 14px"
                                                >add</span
                                            >
                                        </button>
                                    </div>

                                    <div
                                        v-if="item.options.some((o) => o._pending)"
                                        class="t-opt-editor"
                                    >
                                        <div class="option-select-wrap">
                                            <input
                                                v-model="pendingOpt(item).search"
                                                class="form-input input-sm"
                                                placeholder="Tìm chỉ số..."
                                                autocomplete="off"
                                                @focus="pendingOpt(item).showDrop = true"
                                                @input="pendingOpt(item).showDrop = true"
                                            />
                                            <div
                                                v-if="pendingOpt(item).showDrop"
                                                class="option-dropdown"
                                            >
                                                <div
                                                    v-for="o in filteredOptions(pendingOpt(item).search)"
                                                    :key="'o-' + o.id"
                                                    class="option-dropdown-item"
                                                    @mousedown.prevent="
                                                        selectOption(
                                                            pendingOpt(item),
                                                            o,
                                                        )
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
                                                            pendingOpt(item).search,
                                                        ).length
                                                    "
                                                    class="option-dropdown-empty"
                                                >
                                                    Không tìm thấy
                                                </div>
                                            </div>
                                        </div>
                                        <input
                                            v-model.number="pendingOpt(item).param"
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
                                            <span class="mi" style="font-size: 16px"
                                                >check</span
                                            >
                                        </button>
                                        <button
                                            type="button"
                                            class="t-opt-cancel"
                                            @click="cancelPendingOption(item)"
                                            title="Hủy"
                                        >
                                            <span class="mi" style="font-size: 16px"
                                                >close</span
                                            >
                                        </button>
                                    </div>
                                </td>
                                <td class="td-act">
                                    <button
                                        type="button"
                                        class="item-remove-btn"
                                        @click="items.splice(idx, 1)"
                                    >
                                        <span class="mi">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="empty-items">
                    <span class="mi" style="font-size: 42px; color: var(--ds-gray-300)"
                        >inventory_2</span
                    >
                    <p>Chưa có vật phẩm trong mốc quà</p>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" :disabled="saving">
                    <span class="mi" style="font-size: 16px">save</span>
                    {{ saving ? "Đang lưu..." : "Lưu thay đổi" }}
                </button>
                <button
                    v-if="isEdit"
                    type="button"
                    class="btn btn-danger"
                    @click="del"
                >
                    <span class="mi" style="font-size: 16px">delete</span>
                    Xóa
                </button>
            </div>
        </form>

        <div
            v-if="itemPicker.open"
            class="picker-overlay"
            @click.self="closeItemPicker"
        >
            <div class="picker-panel">
                <div class="picker-head">
                    <h3>Chọn vật phẩm</h3>
                    <button
                        type="button"
                        class="picker-close"
                        @click="closeItemPicker"
                    >
                        <span class="mi" style="font-size: 18px">close</span>
                    </button>
                </div>
                <div class="picker-tools">
                    <input
                        v-model="itemPicker.search"
                        class="form-input"
                        placeholder="Tìm theo ID hoặc tên..."
                        @keyup.enter="loadItemPicker(1)"
                    />
                    <select
                        v-model="itemPicker.type"
                        class="form-input"
                        @change="loadItemPicker(1)"
                    >
                        <option value="">Tất cả TYPE</option>
                        <option
                            v-for="t in itemPickerTypes"
                            :key="'picker-type-' + t.id"
                            :value="String(t.id)"
                        >
                            {{ t.name }} (TYPE {{ t.id }})
                        </option>
                    </select>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        @click="loadItemPicker(1)"
                    >
                        Lọc
                    </button>
                </div>
                <div class="picker-list">
                    <div v-if="itemPicker.loading" class="picker-empty">
                        Đang tải dữ liệu...
                    </div>
                    <div v-else-if="!itemPicker.rows.length" class="picker-empty">
                        Không có vật phẩm phù hợp.
                    </div>
                    <button
                        v-else
                        v-for="row in itemPicker.rows"
                        :key="'picker-item-' + row.id"
                        type="button"
                        class="picker-item"
                        @click="pickItemFromPicker(row)"
                    >
                        <AdminIcon
                            class="picker-item-icon"
                            :icon-id="row.icon_id"
                        />
                        <div class="picker-item-info">
                            <div class="picker-item-name">{{ row.name }}</div>
                            <div class="picker-item-meta">
                                ID: {{ row.id }} | {{ itemTypeLabel(row.type) }}
                            </div>
                        </div>
                        <span class="mi" style="font-size: 18px">add</span>
                    </button>
                </div>
                <div class="picker-foot">
                    <span>{{ itemPicker.total.toLocaleString("vi-VN") }} item</span>
                    <div class="picker-pagination">
                        <button
                            type="button"
                            class="btn btn-outline btn-xs"
                            :disabled="itemPicker.page <= 1"
                            @click="goToItemPickerPage(1)"
                        >
                            Đầu
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline btn-xs"
                            :disabled="itemPicker.page <= 1"
                            @click="goToItemPickerPage(itemPicker.page - 1)"
                        >
                            Trước
                        </button>
                        <div class="picker-page-list">
                            <template
                                v-for="p in itemPickerPaginationItems"
                                :key="'picker-page-' + String(p)"
                            >
                                <span
                                    v-if="typeof p !== 'number'"
                                    class="picker-pagination-ellipsis"
                                >
                                    ...
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    class="btn btn-outline btn-xs"
                                    :class="{ active: p === itemPicker.page }"
                                    @click="goToItemPickerPage(p)"
                                >
                                    {{ p }}
                                </button>
                            </template>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline btn-xs"
                            :disabled="itemPicker.page >= itemPicker.totalPages"
                            @click="goToItemPickerPage(itemPicker.page + 1)"
                        >
                            Sau
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline btn-xs"
                            :disabled="itemPicker.page >= itemPicker.totalPages"
                            @click="goToItemPickerPage(itemPicker.totalPages)"
                        >
                            Cuối
                        </button>
                        <span class="picker-pagination-summary"
                            >Trang {{ itemPicker.page }} /
                            {{ itemPicker.totalPages }}</span
                        >
                        <input
                            v-model="itemPicker.pageInput"
                            type="number"
                            min="1"
                            :max="itemPicker.totalPages"
                            class="form-input picker-page-input"
                            @keyup.enter="jumpItemPickerPage"
                        />
                        <button
                            type="button"
                            class="btn btn-primary btn-xs"
                            @click="jumpItemPickerPage"
                        >
                            Đi
                        </button>
                    </div>
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
            form: { id: "", info: "" },
            items: [],
            allOptions: [],
            itemPicker: {
                open: false,
                loading: false,
                rows: [],
                types: [],
                typeOptions: [],
                search: "",
                type: "",
                page: 1,
                pageInput: "1",
                totalPages: 1,
                total: 0,
            },
            itemQuery: "",
            searchResults: [],
            showResults: false,
            searching: false,
            error: "",
            success: "",
            saving: false,
            searchTimeout: null,
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
        },
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
        itemPickerTypes() {
            const fromOptions = Array.isArray(this.itemPicker?.typeOptions)
                ? this.itemPicker.typeOptions
                : [];
            if (fromOptions.length) {
                const normalized = fromOptions
                    .map((opt) => ({
                        id: Number(opt?.id),
                        name: String(opt?.name || "").trim(),
                    }))
                    .filter(
                        (opt) =>
                            Number.isFinite(opt.id) &&
                            opt.name &&
                            opt.name.toLowerCase() !== "undefined" &&
                            opt.name.toLowerCase() !== "null",
                    );
                const dedup = new Map();
                normalized.forEach((opt) => {
                    if (!dedup.has(opt.id)) dedup.set(opt.id, opt);
                });
                return Array.from(dedup.values()).sort((a, b) => a.id - b.id);
            }

            const rawTypes = this.itemPicker?.types;
            const src = Array.isArray(rawTypes)
                ? rawTypes
                : rawTypes && typeof rawTypes === "object"
                  ? Object.values(rawTypes)
                  : [];
            const fallbackFromRows = (this.itemPicker?.rows || []).map(
                (row) => row?.type,
            );
            const merged = [...src, ...fallbackFromRows];
            const cleaned = merged
                .map((t) => String(t ?? "").trim())
                .filter(
                    (t) =>
                        t !== "" &&
                        t.toLowerCase() !== "undefined" &&
                        t.toLowerCase() !== "null" &&
                        t.toLowerCase() !== "nan",
                );
            const ids = [...new Set(cleaned)];
            return ids.map((id) => ({ id: Number(id), name: `Type ${id}` }));
        },
        itemPickerPaginationItems() {
            return this.buildPaginationItems(
                this.itemPicker.page,
                this.itemPicker.totalPages,
            );
        },
    },
    watch: {
        "$route.params.type"() {
            this.ensureType();
            if (this.isEdit) {
                this.loadRecord();
            } else {
                this.form = { id: "", info: "" };
                this.items = [];
            }
        },
    },
    created() {
        this.ensureType();
        this.loadOptions();
        if (this.isEdit) {
            this.loadRecord();
        }
        document.addEventListener("click", this.closeResults);
    },
    unmounted() {
        document.removeEventListener("click", this.closeResults);
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
            if (!TYPE_MAP[type]) return;
            if (type === this.currentType) return;
            if (this.isEdit) {
                this.$router.push({ name: "admin.milestones", params: { type } });
                return;
            }
            this.$router.push({
                name: "admin.milestones.create",
                params: { type },
            });
        },
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
        normalizePickerPage(page) {
            const value = Number(page);
            if (!Number.isFinite(value)) return 1;
            return Math.min(
                Math.max(1, Math.trunc(value)),
                this.itemPicker.totalPages || 1,
            );
        },
        goToItemPickerPage(page) {
            const target = this.normalizePickerPage(page);
            if (target === this.itemPicker.page && this.itemPicker.rows.length) {
                this.itemPicker.pageInput = String(target);
                return;
            }
            this.loadItemPicker(target);
        },
        jumpItemPickerPage() {
            this.goToItemPickerPage(this.itemPicker.pageInput);
        },
        async openItemPicker() {
            this.itemPicker.open = true;
            if (!this.itemPicker.rows.length) {
                await this.loadItemPicker(1);
            }
        },
        closeItemPicker() {
            this.itemPicker.open = false;
        },
        async loadItemPicker(page = 1) {
            try {
                this.itemPicker.loading = true;
                this.itemPicker.page = this.normalizePickerPage(page);
                this.itemPicker.pageInput = String(this.itemPicker.page);
                const params = new URLSearchParams({
                    page: String(this.itemPicker.page),
                    per_page: "30",
                });
                if (this.itemPicker.search && this.itemPicker.search.trim()) {
                    params.set("search", this.itemPicker.search.trim());
                }
                if (this.itemPicker.type !== "") {
                    params.set("type", this.itemPicker.type);
                }
                const res = await fetch(`/admin/api/items?${params.toString()}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                this.itemPicker.rows = data?.data || [];
                this.itemPicker.types = data?.types || this.itemPicker.types;
                this.itemPicker.typeOptions =
                    data?.type_options || this.itemPicker.typeOptions;
                this.itemPicker.page = data?.page || 1;
                this.itemPicker.pageInput = String(this.itemPicker.page);
                this.itemPicker.totalPages = data?.total_pages || 1;
                this.itemPicker.total = data?.total || 0;
            } catch {
                this.itemPicker.rows = [];
                this.itemPicker.total = 0;
                this.itemPicker.totalPages = 1;
            } finally {
                this.itemPicker.loading = false;
            }
        },
        pickItemFromPicker(item) {
            this.addItem(item);
        },
        itemTypeLabel(typeValue) {
            const key = String(typeValue ?? "").trim();
            const found = this.itemPickerTypes.find((t) => String(t.id) === key);
            if (found) return `TYPE: ${found.id} - ${found.name}`;
            return `TYPE: ${typeValue}`;
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
                icon_id: item.icon_id ?? null,
                quantity: 1,
                options: [],
            });
            this.itemQuery = "";
            this.searchResults = [];
            this.showResults = false;
        },
        optionName(id) {
            const o = this.allOptions.find((a) => a.id === id);
            return o ? o.name : "";
        },
        optionLabel(id, param) {
            const o = this.allOptions.find((a) => a.id === id);
            if (!o) return String(param);
            return o.name.includes("#")
                ? o.name.replace("#", param)
                : o.name + ": " + param;
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
        addPendingOption(item) {
            if (item.options.some((o) => o._pending)) return;
            item.options.push({
                id: 0,
                param: 0,
                search: "",
                showDrop: false,
                _pending: true,
            });
        },
        editOption(item, opt) {
            if (item.options.some((o) => o._pending)) return;
            const name = this.optionName(opt.id);
            opt.search = name ? `${name} (ID: ${opt.id})` : `ID: ${opt.id}`;
            opt.showDrop = false;
            opt._pending = true;
            opt._editing = true;
            opt._backup = {
                id: opt.id,
                param: opt.param,
                search: opt.search,
            };
        },
        confirmOption(item) {
            const opt = this.pendingOpt(item);
            if (!opt || !opt._pending) return;
            if (!opt.id) return;
            if (!opt.search || !opt.search.trim()) {
                const name = this.optionName(opt.id);
                opt.search = name ? `${name} (ID: ${opt.id})` : `ID: ${opt.id}`;
            }
            delete opt._pending;
            delete opt._editing;
            delete opt._backup;
        },
        cancelPendingOption(item) {
            const opt = this.pendingOpt(item);
            if (!opt) return;
            opt.showDrop = false;
            if (opt._editing && opt._backup) {
                opt.id = opt._backup.id;
                opt.param = opt._backup.param;
                opt.search = opt._backup.search;
                delete opt._pending;
                delete opt._editing;
                delete opt._backup;
                return;
            }
            const idx = item.options.indexOf(opt);
            if (idx >= 0) item.options.splice(idx, 1);
        },
        pendingOpt(item) {
            return item.options.find((o) => o._pending) || null;
        },
        async loadOptions() {
            try {
                const res = await fetch("/admin/api/options", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                this.allOptions = Array.isArray(data)
                    ? data
                    : Array.isArray(data?.options)
                      ? data.options
                      : [];
            } catch {
                this.allOptions = [];
            }
        },
        async loadRecord() {
            this.error = "";
            this.success = "";
            try {
                const res = await fetch(
                    `/admin/api/milestones/${this.currentType}/${this.$route.params.id}`,
                    { headers: { "X-Requested-With": "XMLHttpRequest" } },
                );
                const data = await res.json();
                if (!data.ok) {
                    this.error = data.message || "Không thể tải dữ liệu";
                    return;
                }
                const d = data.data || {};
                this.form.id = d.id || this.$route.params.id;
                this.form.info = d.info || "";
                const arr = this.parseDetail(d.detail || "[]");
                this.items = arr.map((it) => ({
                    temp_id: it.temp_id || 0,
                    name: `Item #${it.temp_id || 0}`,
                    icon_id: null,
                    quantity: it.quantity || 1,
                    options: (it.options || []).map((o) => {
                        const name = this.optionName(o.id);
                        return {
                            id: o.id || 0,
                            param: o.param || 0,
                            search: name ? `${name} (ID: ${o.id})` : `ID: ${o.id}`,
                            showDrop: false,
                        };
                    }),
                }));

                const ids = [...new Set(this.items.map((i) => i.temp_id))].filter(
                    (id) => !!id,
                );
                if (ids.length) {
                    const iconRes = await fetch(
                        `/admin/api/items/batch?ids=${ids.join(",")}`,
                        { headers: { "X-Requested-With": "XMLHttpRequest" } },
                    );
                    const iconData = await iconRes.json();
                    this.items = this.items.map((it) => ({
                        ...it,
                        name: iconData[it.temp_id]?.name || it.name,
                        icon_id: iconData[it.temp_id]?.icon_id ?? null,
                    }));
                }
            } catch {
                this.error = "Lỗi tải dữ liệu";
            }
        },
        buildDetail() {
            return JSON.stringify(
                this.items.map((item) => ({
                    temp_id: parseInt(item.temp_id) || 0,
                    quantity: parseInt(item.quantity) || 1,
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
                    info: this.form.info,
                    detail: this.buildDetail(),
                };
                if (!this.isEdit && this.form.id) {
                    body.id = parseInt(this.form.id) || undefined;
                }
                const url = this.isEdit
                    ? `/admin/api/milestones/${this.currentType}/${this.$route.params.id}`
                    : `/admin/api/milestones/${this.currentType}`;
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
                    this.success = data.message || "Lưu thành công";
                    if (!this.isEdit) {
                        this.$router.push({
                            name: "admin.milestones",
                            params: { type: this.currentType },
                        });
                    }
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
            if (!confirm("Xóa mốc quà này?")) return;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                await fetch(
                    `/admin/api/milestones/${this.currentType}/${this.$route.params.id}`,
                    {
                        method: "DELETE",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": token,
                        },
                    },
                );
                this.$router.push({
                    name: "admin.milestones",
                    params: { type: this.currentType },
                });
            } catch {
                this.error = "Lỗi kết nối";
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
}
.type-tab.active {
    background: rgba(var(--ds-primary-rgb), 0.15);
    border-color: rgba(var(--ds-primary-rgb), 0.55);
    color: var(--ds-primary);
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.card-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.item-count {
    font-size: 12px;
    color: var(--ds-text-muted);
    background: rgba(var(--ds-primary-rgb), 0.12);
    padding: 3px 10px;
    border-radius: 20px;
}
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
}
.item-result:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.item-result-icon {
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
.th-qty {
    width: 90px;
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
.table-item-icon {
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
.td-act {
    text-align: center;
}
.t-input {
    width: 100% !important;
    min-width: 80px;
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
    border: 1px solid transparent;
    cursor: pointer;
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
.t-opt-add {
    display: inline-flex;
    align-items: center;
    background: none;
    border: 1px dashed rgba(var(--ds-primary-rgb), 0.4);
    color: var(--ds-primary);
    border-radius: 12px;
    padding: 1px 6px;
    cursor: pointer;
}
.t-opt-editor {
    display: flex;
    gap: 6px;
    margin-top: 4px;
    align-items: flex-start;
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
.t-opt-cancel {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
    border: 1px solid rgba(var(--ds-danger-rgb), 0.32);
    border-radius: 4px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
}
.item-remove-btn {
    background: none;
    border: none;
    color: var(--ds-text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    display: flex;
}
.item-remove-btn:hover {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}
.empty-items {
    text-align: center;
    padding: 30px 16px;
}
.empty-items p {
    color: var(--ds-text-muted);
    margin-top: 8px;
}
.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}
.picker-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5, 10, 18, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 1500;
}
.picker-panel {
    width: min(980px, 100%);
    max-height: calc(100vh - 48px);
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.picker-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border);
}
.picker-head h3 {
    margin: 0;
    font-size: 15px;
    color: var(--ds-text-emphasis);
}
.picker-close {
    background: transparent;
    border: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    border-radius: 8px;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.picker-tools {
    display: grid;
    grid-template-columns: 1fr 180px auto;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border);
}
.picker-list {
    overflow: auto;
    padding: 8px 10px;
    min-height: 320px;
}
.picker-item {
    width: 100%;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: var(--ds-text);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    cursor: pointer;
    text-align: left;
}
.picker-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
    border-color: rgba(var(--ds-primary-rgb), 0.3);
}
.picker-item-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.picker-item-info {
    flex: 1;
    min-width: 0;
}
.picker-item-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.picker-item-meta {
    font-size: 11px;
    color: var(--ds-text-muted);
}
.picker-empty {
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.picker-foot {
    border-top: 1px solid var(--ds-border);
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
    color: var(--ds-text-muted);
}
.picker-pagination {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.picker-page-list {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.picker-pagination-ellipsis {
    color: var(--ds-text-muted);
    font-size: 12px;
    padding: 0 2px;
}
.picker-pagination-summary {
    color: var(--ds-text-muted);
}
.picker-page-input {
    width: 72px;
    min-width: 72px;
    padding: 6px 8px !important;
}
@media (max-width: 900px) {
    .form-row-2 {
        grid-template-columns: 1fr;
    }
    .picker-overlay {
        padding: 12px;
    }
    .picker-tools {
        grid-template-columns: 1fr;
    }
}
</style>
