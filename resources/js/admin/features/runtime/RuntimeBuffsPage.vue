<template>
    <div class="runtime-buffs-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Buff command</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Buff command</span>
                </nav>
            </div>
            <div class="page-top-actions">
                <button
                    type="button"
                    class="btn btn-outline"
                    @click="resetMailForm"
                >
                    <span class="mi">refresh</span>
                    Làm mới hòm thư
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="mailSaving"
                    @click="submitMailBuff"
                >
                    <span
                        v-if="mailSaving"
                        class="admin-loading-spinner"
                    ></span>
                    <span v-else class="mi">outbox</span>
                    Gửi hòm thư
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="buff-layout">
            <main class="buff-main">
                <section class="card command-card">
                    <div class="card-header command-head">
                        <div>
                            <div class="panel-kicker">HÒM THƯ</div>
                            <h3>Buff vật phẩm và option</h3>
                        </div>
                        <div class="summary-strip">
                            <div>
                                <strong>{{ mail.items.length }}</strong>
                                <span>item</span>
                            </div>
                            <div>
                                <strong>{{ totalOptions }}</strong>
                                <span>option</span>
                            </div>
                            <div>
                                <strong>{{
                                    totalQuantity.toLocaleString("vi-VN")
                                }}</strong>
                                <span>số lượng</span>
                            </div>
                        </div>
                    </div>

                    <div class="mail-target-grid">
                        <div class="form-group">
                            <label class="form-label">Tên nhân vật nhận</label>
                            <input
                                v-model.trim="mail.target"
                                class="form-input"
                                placeholder="Nhập tên nhân vật, hoặc all để gửi toàn server"
                            />
                        </div>
                        <div class="toggle-field">
                            <label class="toggle">
                                <input v-model="mail.notify" type="checkbox" />
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <strong>Thông báo online</strong>
                            </div>
                        </div>
                    </div>

                    <div class="item-toolbar">
                        <div class="item-search-wrap">
                            <span class="mi search-icon">search</span>
                            <input
                                v-model="itemQuery"
                                class="form-input search-input"
                                placeholder="Tìm nhanh item theo tên hoặc ID..."
                                autocomplete="off"
                                @input="searchItems"
                                @focus="showItemResults = true"
                            />
                            <div
                                v-if="showItemResults && itemResults.length"
                                class="item-search-results"
                            >
                                <button
                                    v-for="item in itemResults"
                                    :key="'quick-' + item.id"
                                    type="button"
                                    class="item-result"
                                    @mousedown.prevent="addItem(item)"
                                >
                                    <AdminIcon :icon-id="item.icon_id" />
                                    <span class="item-result-info">
                                        <strong>{{ item.name }}</strong>
                                        <small
                                            >ID: {{ item.id }} ·
                                            {{
                                                itemTypeLabel(item.type)
                                            }}</small
                                        >
                                    </span>
                                    <span class="mi">add_circle</span>
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="openItemPicker"
                        >
                            <span class="mi">inventory_2</span>
                            Chọn item
                        </button>
                    </div>

                    <div v-if="!mail.items.length" class="empty-panel">
                        <span class="mi">inventory_2</span>
                        <strong>Chưa có vật phẩm</strong>
                        <p>
                            Chọn item bằng modal hoặc ô tìm nhanh, sau đó thêm
                            option tương ứng cho từng item.
                        </p>
                    </div>

                    <div v-else class="buff-items-table-wrap">
                        <table class="buff-items-table">
                            <colgroup>
                                <col class="col-index" />
                                <col class="col-item" />
                                <col class="col-qty" />
                                <col class="col-options" />
                                <col class="col-actions" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="col-index">#</th>
                                    <th>Vật phẩm</th>
                                    <th class="col-qty">Số lượng</th>
                                    <th>Options</th>
                                    <th class="col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in mail.items"
                                    :key="item.local_id"
                                >
                                    <td class="item-index">{{ index + 1 }}</td>
                                    <td>
                                        <div class="table-item">
                                            <AdminIcon
                                                :icon-id="item.icon_id"
                                            />
                                            <div class="table-item-info">
                                                <strong>{{ item.name }}</strong>
                                                <span
                                                    >ID:
                                                    {{ item.temp_id }}</span
                                                >
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            max="99999"
                                            class="form-input input-sm qty-input"
                                        />
                                    </td>
                                    <td>
                                        <div class="table-options">
                                            <template
                                                v-for="(
                                                    option, optionIndex
                                                ) in item.options"
                                                :key="option.local_id"
                                            >
                                                <span
                                                    v-if="
                                                        isOptionSelected(option)
                                                    "
                                                    class="option-pill"
                                                >
                                                    {{ optionDisplay(option) }}
                                                    <button
                                                        type="button"
                                                        @click="
                                                            item.options.splice(
                                                                optionIndex,
                                                                1,
                                                            )
                                                        "
                                                    >
                                                        ×
                                                    </button>
                                                </span>
                                                <span
                                                    v-else
                                                    class="option-editor-pill"
                                                >
                                                    <span
                                                        class="option-select-wrap"
                                                    >
                                                        <input
                                                            v-model="
                                                                option.search
                                                            "
                                                            class="form-input option-search-input"
                                                            placeholder="Tìm option..."
                                                            autocomplete="off"
                                                            @focus="
                                                                option.open = true
                                                            "
                                                            @input="
                                                                handleOptionSearchInput(
                                                                    option,
                                                                )
                                                            "
                                                            @keyup.enter="
                                                                confirmOption(
                                                                    option,
                                                                )
                                                            "
                                                        />
                                                        <div
                                                            v-if="option.open"
                                                            class="option-dropdown"
                                                        >
                                                            <button
                                                                v-for="matched in filteredOptions(
                                                                    option.search,
                                                                )"
                                                                :key="
                                                                    matched.id
                                                                "
                                                                type="button"
                                                                class="option-dropdown-item"
                                                                @mousedown.prevent.stop="
                                                                    selectOption(
                                                                        option,
                                                                        matched,
                                                                    )
                                                                "
                                                            >
                                                                <span
                                                                    class="opt-id"
                                                                    >{{
                                                                        matched.id
                                                                    }}</span
                                                                >
                                                                <span>{{
                                                                    matched.name
                                                                }}</span>
                                                            </button>
                                                            <div
                                                                v-if="
                                                                    !filteredOptions(
                                                                        option.search,
                                                                    ).length
                                                                "
                                                                class="option-dropdown-empty"
                                                            >
                                                                Không tìm thấy
                                                            </div>
                                                        </div>
                                                    </span>
                                                    <input
                                                        v-model.number="
                                                            option.param
                                                        "
                                                        type="number"
                                                        class="form-input option-param-input"
                                                        placeholder="Param"
                                                        @keyup.enter="
                                                            confirmOption(
                                                                option,
                                                            )
                                                        "
                                                    />
                                                    <button
                                                        type="button"
                                                        class="option-confirm-btn"
                                                        title="Xác nhận option"
                                                        @click.prevent.stop="
                                                            confirmOption(
                                                                option,
                                                            )
                                                        "
                                                    >
                                                        <span class="mi"
                                                            >check</span
                                                        >
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="option-cancel-btn"
                                                        @click="
                                                            item.options.splice(
                                                                optionIndex,
                                                                1,
                                                            )
                                                        "
                                                    >
                                                        <span class="mi"
                                                            >close</span
                                                        >
                                                    </button>
                                                </span>
                                            </template>
                                            <button
                                                type="button"
                                                class="option-plus-btn"
                                                title="Thêm option"
                                                @click="addOption(item)"
                                            >
                                                <span class="mi">add</span>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="row-delete-btn"
                                            title="Xóa item"
                                            @click="removeItem(index)"
                                        >
                                            <span class="mi">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <aside class="buff-sidebar">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <div class="panel-kicker">ACCOUNT</div>
                            <h3>Buff cash, danap, active</h3>
                        </div>
                    </div>
                    <div class="account-form">
                        <div class="form-group">
                            <label class="form-label">Kiểu tìm</label>
                            <select
                                v-model="account.target_type"
                                class="form-input"
                            >
                                <option value="player_name">
                                    Tên nhân vật
                                </option>
                                <option value="username">
                                    Username account
                                </option>
                                <option value="account_id">Account ID</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Giá trị tìm</label>
                            <input
                                v-model.trim="account.target"
                                class="form-input"
                                placeholder="Nhập nhân vật hoặc account"
                            />
                        </div>
                        <div class="money-grid">
                            <div class="form-group">
                                <label class="form-label">Cash cộng thêm</label>
                                <input
                                    v-model.number="account.cash"
                                    type="number"
                                    min="0"
                                    class="form-input"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    >Danap cộng thêm</label
                                >
                                <input
                                    v-model.number="account.danap"
                                    type="number"
                                    min="0"
                                    class="form-input"
                                />
                            </div>
                        </div>
                        <label class="checkbox-card">
                            <input v-model="account.active" type="checkbox" />
                            <span class="box"></span>
                            <span>Mở thành viên active</span>
                        </label>
                        <label class="checkbox-card">
                            <input v-model="account.notify" type="checkbox" />
                            <span class="box"></span>
                            <span>Thông báo nếu online</span>
                        </label>
                        <button
                            type="button"
                            class="btn btn-primary btn-block"
                            :disabled="accountSaving"
                            @click="submitAccountBuff"
                        >
                            <span
                                v-if="accountSaving"
                                class="admin-loading-spinner"
                            ></span>
                            <span v-else class="mi">payments</span>
                            Buff account
                        </button>
                    </div>
                </section>

                <section class="card suggestions-card">
                    <div class="panel-kicker">NÊN LÀM TIẾP</div>
                    <div class="suggestion-list">
                        <div
                            v-for="suggestion in suggestions"
                            :key="suggestion.title"
                            class="suggestion-item"
                        >
                            <span class="mi">{{ suggestion.icon }}</span>
                            <div>
                                <strong>{{ suggestion.title }}</strong>
                                <small>{{ suggestion.desc }}</small>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        <div
            v-if="itemPicker.open"
            class="picker-overlay"
            @click.self="closeItemPicker"
        >
            <div class="picker-panel">
                <div class="picker-head">
                    <div>
                        <h3>Chọn vật phẩm</h3>
                        <p>
                            Lọc theo tên, ID, type hoặc hệ rồi thêm vào danh
                            sách buff.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="picker-close"
                        @click="closeItemPicker"
                    >
                        <span class="mi">close</span>
                    </button>
                </div>
                <div class="picker-tools">
                    <input
                        v-model="itemPicker.search"
                        class="form-input"
                        placeholder="Tìm theo ID hoặc tên..."
                        @input="debouncedLoadItemPicker"
                        @keyup.enter="loadItemPicker(1)"
                    />
                    <select
                        v-model="itemPicker.type"
                        class="form-input"
                        @change="loadItemPicker(1)"
                    >
                        <option value="">Tất cả TYPE</option>
                        <option
                            v-for="type in itemPickerTypes"
                            :key="'type-' + type.id"
                            :value="String(type.id)"
                        >
                            {{ type.name }} (TYPE {{ type.id }})
                        </option>
                    </select>
                    <select
                        v-model="itemPicker.gender"
                        class="form-input"
                        @change="loadItemPicker(1)"
                    >
                        <option value="">Tất cả hệ</option>
                        <option value="0">Trái Đất</option>
                        <option value="1">Namek</option>
                        <option value="2">Xayda</option>
                        <option value="3">Chung/Tất cả</option>
                    </select>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        @click="loadItemPicker(1)"
                    >
                        <span class="mi">filter_alt</span>
                        Lọc
                    </button>
                </div>

                <div class="picker-list">
                    <div v-if="itemPicker.loading" class="picker-empty">
                        <span class="admin-loading-spinner"></span>
                    </div>
                    <div
                        v-else-if="!itemPicker.rows.length"
                        class="picker-empty"
                    >
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
                        <AdminIcon :icon-id="row.icon_id" />
                        <div class="picker-item-info">
                            <div class="picker-item-name">{{ row.name }}</div>
                            <div class="picker-item-meta">
                                ID: {{ row.id }} |
                                {{ itemTypeLabel(row.type) }} |
                                {{ itemGenderLabel(row.gender) }}
                            </div>
                        </div>
                        <span class="mi">add</span>
                    </button>
                </div>

                <div class="picker-foot">
                    <span
                        >{{
                            itemPicker.total.toLocaleString("vi-VN")
                        }}
                        item</span
                    >
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
                                v-for="page in itemPickerPaginationItems"
                                :key="'page-' + String(page)"
                            >
                                <span
                                    v-if="typeof page !== 'number'"
                                    class="picker-pagination-ellipsis"
                                    >...</span
                                >
                                <button
                                    v-else
                                    type="button"
                                    class="btn btn-outline btn-xs"
                                    :class="{
                                        active: page === itemPicker.page,
                                    }"
                                    @click="goToItemPickerPage(page)"
                                >
                                    {{ page }}
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
import { buildPaginationItems } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";
import {
    applyItemPickerResponse,
    itemGenderLabel,
    itemPickerTypes,
    itemTypeLabel,
    normalizePickerPage,
    resetItemPickerResults,
} from "../../shared/itemCatalog";
export default {
    data() {
        return {
            mail: {
                target: "",
                notify: true,
                items: [],
            },
            account: {
                target_type: "player_name",
                target: "",
                cash: 0,
                danap: 0,
                active: false,
                notify: true,
            },
            itemQuery: "",
            itemResults: [],
            showItemResults: false,
            itemPicker: {
                open: false,
                loading: false,
                rows: [],
                types: [],
                typeOptions: [],
                search: "",
                type: "",
                gender: "",
                page: 1,
                pageInput: "1",
                totalPages: 1,
                total: 0,
            },
            allOptions: [],
            suggestions: [
                {
                    icon: "diamond",
                    title: "Buff vàng/ngọc/ruby",
                    desc: "Cộng trực tiếp vào data_inventory nhân vật.",
                },
                {
                    icon: "redeem",
                    title: "Preset quà sự kiện",
                    desc: "Lưu bộ item + option để dùng lại nhiều lần.",
                },
                {
                    icon: "monitor_heart",
                    title: "Sức mạnh/tiềm năng",
                    desc: "Buff power, tiềm năng và giới hạn sức mạnh.",
                },
                {
                    icon: "groups",
                    title: "Gửi hàng loạt",
                    desc: "Gửi theo danh sách người chơi hoặc toàn server.",
                },
            ],
            error: "",
            success: "",
            mailSaving: false,
            accountSaving: false,
            itemSearchTimer: null,
            itemPickerSearchTimer: null,
            localId: 1,
            optionsCacheKey: "admin_item_options_v3",
            optionsCacheTtlMs: 1000 * 60 * 30,
        };
    },
    computed: {
        totalOptions() {
            return this.mail.items.reduce(
                (sum, item) =>
                    sum +
                    item.options.filter((option) =>
                        this.isOptionSelected(option),
                    ).length,
                0,
            );
        },
        totalQuantity() {
            return this.mail.items.reduce(
                (sum, item) => sum + (Number(item.quantity) || 0),
                0,
            );
        },
        itemPickerTypes() {
            return itemPickerTypes(this.itemPicker);
        },
        itemPickerPaginationItems() {
            return buildPaginationItems(
                this.itemPicker.page,
                this.itemPicker.totalPages,
            );
        },
    },
    async created() {
        this.loadDraft();
        await this.loadOptions();
        document.addEventListener("click", this.closeFloatingMenus);
    },
    unmounted() {
        document.removeEventListener("click", this.closeFloatingMenus);
        window.clearTimeout(this.itemSearchTimer);
        window.clearTimeout(this.itemPickerSearchTimer);
    },
    watch: {
        mail: {
            deep: true,
            handler() {
                this.saveDraft();
            },
        },
        account: {
            deep: true,
            handler() {
                this.saveDraft();
            },
        },
    },
    methods: {
        closeFloatingMenus(event) {
            if (!event.target.closest(".item-search-wrap")) {
                this.showItemResults = false;
            }
            if (!event.target.closest(".option-select-wrap")) {
                this.mail.items.forEach((item) =>
                    item.options.forEach((option) => (option.open = false)),
                );
            }
        },
        loadDraft() {
            try {
                const draft = JSON.parse(
                    localStorage.getItem("admin_runtime_buffs_draft_v2") ||
                        "null",
                );
                if (!draft) return;
                if (draft.mail && Array.isArray(draft.mail.items)) {
                    this.mail = {
                        target: draft.mail.target || "",
                        notify: draft.mail.notify !== false,
                        items: draft.mail.items.map((item) => ({
                            ...item,
                            local_id: this.localId++,
                            expanded: false,
                            options: (item.options || []).map((option) => ({
                                ...option,
                                local_id: this.localId++,
                                open: false,
                                confirmed:
                                    option.confirmed !== false &&
                                    this.hasOptionId(option),
                            })),
                        })),
                    };
                }
                if (draft.account) {
                    this.account = {
                        ...this.account,
                        ...draft.account,
                    };
                }
            } catch {
                //
            }
        },
        saveDraft() {
            try {
                localStorage.setItem(
                    "admin_runtime_buffs_draft_v2",
                    JSON.stringify({
                        mail: this.mail,
                        account: this.account,
                    }),
                );
            } catch {
                //
            }
        },
        resetMailForm() {
            this.mail = { target: "", notify: true, items: [] };
            this.success = "";
            this.error = "";
        },
        readCachedOptions() {
            try {
                const cached = JSON.parse(
                    localStorage.getItem(this.optionsCacheKey) || "null",
                );
                if (
                    cached?.expires_at > Date.now() &&
                    Array.isArray(cached.data)
                ) {
                    return cached.data;
                }
            } catch {
                //
            }
            return null;
        },
        writeCachedOptions(data) {
            try {
                localStorage.setItem(
                    this.optionsCacheKey,
                    JSON.stringify({
                        expires_at: Date.now() + this.optionsCacheTtlMs,
                        data,
                    }),
                );
            } catch {
                //
            }
        },
        async loadOptions() {
            const cached = this.readCachedOptions();
            if (cached) {
                this.allOptions = cached;
                return;
            }

            const res = await fetch("/admin/api/options", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await readJsonResponse(
                res,
                "Không thể tải option",
            );
            this.allOptions = Array.isArray(data)
                ? data
                : data?.data || data?.options || [];
            if (this.allOptions.length) {
                this.writeCachedOptions(this.allOptions);
            }
        },
        searchItems() {
            window.clearTimeout(this.itemSearchTimer);
            const query = this.itemQuery.trim();
            if (!query) {
                this.itemResults = [];
                this.showItemResults = false;
                return;
            }
            this.itemSearchTimer = window.setTimeout(async () => {
                try {
                    const params = new URLSearchParams({
                        lite: "1",
                        per_page: "30",
                        search: query,
                    });
                    const res = await fetch(`/admin/api/items?${params}`, {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    });
                    const data = await readJsonResponse(
                        res,
                        "Không thể tìm item",
                    );
                    this.itemResults = data?.data || [];
                    this.showItemResults = true;
                } catch (error) {
                    this.error = error?.message || "Không thể tìm item.";
                    this.itemResults = [];
                }
            }, 250);
        },
        addItem(item) {
            this.mail.items.push({
                local_id: this.localId++,
                temp_id: Number(item.id),
                icon_id: item.icon_id,
                name: item.name || `Item #${item.id}`,
                type: item.type ?? null,
                gender: item.gender ?? null,
                quantity: 1,
                expanded: true,
                options: [],
            });
            this.itemQuery = "";
            this.itemResults = [];
            this.showItemResults = false;
        },
        duplicateItem(item) {
            this.mail.items.push({
                ...item,
                local_id: this.localId++,
                expanded: true,
                options: item.options.map((option) => ({
                    ...option,
                    local_id: this.localId++,
                    open: false,
                    confirmed:
                        option.confirmed !== false && this.hasOptionId(option),
                })),
            });
        },
        removeItem(index) {
            this.mail.items.splice(index, 1);
        },
        toggleOptions(item) {
            item.expanded = !item.expanded;
        },
        addOption(item) {
            item.expanded = true;
            item.options.push({
                local_id: this.localId++,
                id: null,
                param: 0,
                search: "",
                open: true,
                confirmed: false,
            });
        },
        filteredOptions(search) {
            const options = Array.isArray(this.allOptions)
                ? this.allOptions
                : [];
            const query = String(search || "")
                .trim()
                .toLowerCase();
            if (!query) return options;
            return options.filter(
                (option) =>
                    String(option.id).includes(query) ||
                    String(option.name || "")
                        .toLowerCase()
                        .includes(query),
            );
        },
        selectOption(option, matched) {
            option.id = Number(matched.id);
            option.search = `${matched.name} (ID: ${matched.id})`;
            option.open = false;
            option.confirmed = false;
        },
        optionDisplay(option) {
            const matched = this.allOptions.find(
                (row) => Number(row.id) === Number(option.id),
            );
            const name = matched?.name || `Option ${option.id}`;
            return `${name}: ${Number(option.param) || 0}`;
        },
        isOptionSelected(option) {
            return option.confirmed === true && this.hasOptionId(option);
        },
        hasOptionId(option) {
            if (
                !option ||
                option.id === null ||
                option.id === undefined ||
                option.id === ""
            ) {
                return false;
            }
            return !Number.isNaN(Number(option.id));
        },
        handleOptionSearchInput(option) {
            option.id = null;
            option.confirmed = false;
            option.open = true;
        },
        resolveOptionCandidate(search) {
            const query = String(search || "").trim();
            if (!query) return null;

            const explicitId = query.match(/\(id:\s*(\d+)\)|^id:\s*(\d+)$/i);
            const idOnly = query.match(/^\d+$/);
            const idValue = explicitId?.[1] || explicitId?.[2] || idOnly?.[0];
            if (idValue !== undefined) {
                const byId = this.allOptions.find(
                    (row) => Number(row.id) === Number(idValue),
                );
                if (byId) return byId;
            }

            return this.filteredOptions(query)[0] || null;
        },
        confirmOption(option) {
            const matched =
                (this.hasOptionId(option)
                    ? this.allOptions.find(
                          (row) => Number(row.id) === Number(option.id),
                      )
                    : null) || this.resolveOptionCandidate(option.search);
            if (!matched) {
                option.open = true;
                return;
            }
            option.id = Number(matched.id);
            option.search = `${matched.name} (ID: ${matched.id})`;
            option.open = false;
            option.confirmed = true;
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
        debouncedLoadItemPicker() {
            window.clearTimeout(this.itemPickerSearchTimer);
            this.itemPickerSearchTimer = window.setTimeout(
                () => this.loadItemPicker(1),
                250,
            );
        },
        async loadItemPicker(page = 1) {
            this.itemPicker.loading = true;
            try {
                this.itemPicker.page = this.normalizePickerPage(page);
                this.itemPicker.pageInput = String(this.itemPicker.page);
                const params = new URLSearchParams({
                    page: String(this.itemPicker.page),
                    per_page: "30",
                    lite: "1",
                });
                if (this.itemPicker.search.trim())
                    params.set("search", this.itemPicker.search.trim());
                if (this.itemPicker.type !== "")
                    params.set("type", this.itemPicker.type);
                if (this.itemPicker.gender !== "")
                    params.set("gender", this.itemPicker.gender);

                const res = await fetch(`/admin/api/items?${params}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(
                    res,
                    "Không thể lọc item",
                );
                applyItemPickerResponse(this.itemPicker, data);
            } catch (error) {
                this.error = error?.message || "Không thể lọc item";
                resetItemPickerResults(this.itemPicker);
            } finally {
                this.itemPicker.loading = false;
            }
        },
        pickItemFromPicker(item) {
            this.addItem(item);
        },
        normalizePickerPage(page) {
            return normalizePickerPage(page, this.itemPicker.totalPages);
        },
        goToItemPickerPage(page) {
            const target = this.normalizePickerPage(page);
            if (target === this.itemPicker.page && this.itemPicker.rows.length)
                return;
            this.loadItemPicker(target);
        },
        jumpItemPickerPage() {
            this.goToItemPickerPage(this.itemPicker.pageInput);
        },
        itemTypeLabel(typeValue) {
            return itemTypeLabel(typeValue, this.itemPickerTypes);
        },
        itemGenderLabel(genderValue) {
            return itemGenderLabel(genderValue);
        },
        buildMailPayload() {
            return {
                target: this.mail.target,
                notify: this.mail.notify,
                items: this.mail.items.map((item) => ({
                    temp_id: Number(item.temp_id),
                    quantity: Number(item.quantity) || 1,
                    options: item.options
                        .filter((option) => this.isOptionSelected(option))
                        .map((option) => ({
                            id: Number(option.id),
                            param: Number(option.param) || 0,
                        })),
                })),
            };
        },
        async submitMailBuff() {
            this.error = "";
            this.success = "";
            this.mailSaving = true;
            try {
                const result = await this.postJson(
                    "/admin/api/runtime/buffs/mail",
                    this.buildMailPayload(),
                );
                this.success = result.message || "Đã buff hòm thư.";
            } catch (error) {
                this.error = error?.message || "Buff hòm thư thất bại.";
            } finally {
                this.mailSaving = false;
            }
        },
        async submitAccountBuff() {
            this.error = "";
            this.success = "";
            this.accountSaving = true;
            try {
                const payload = {
                    ...this.account,
                    cash: Math.max(0, Number(this.account.cash) || 0),
                    danap: Math.max(0, Number(this.account.danap) || 0),
                };
                const result = await this.postJson(
                    "/admin/api/runtime/buffs/account",
                    payload,
                );
                this.success = result.message || "Đã buff account.";
            } catch (error) {
                this.error = error?.message || "Buff account thất bại.";
            } finally {
                this.accountSaving = false;
            }
        },
        async postJson(url, payload) {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": token || "",
                },
                body: JSON.stringify(payload),
            });
            const data = await readJsonResponse(
                res,
                "Runtime command thất bại.",
            );
            if (!res.ok || data.ok === false) {
                throw new Error(data.message || "Runtime command thất bại.");
            }
            return data;
        },
    },
};
</script>

<style scoped>
.runtime-buffs-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    width: 100%;
    min-width: 0;
    overflow: visible;
}
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 6px;
    padding-top: 4px;
}
.page-title {
    display: block;
    margin: 0 0 4px;
    font-size: 22px;
    line-height: 1.25;
    color: var(--ds-text-emphasis);
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 20px;
}
.page-top-actions {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 2px;
}
.buff-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(320px, 360px);
    gap: 20px;
    align-items: start;
    min-width: 0;
}
.buff-main,
.buff-sidebar,
.account-form,
.suggestion-list {
    display: grid;
    gap: 14px;
    min-width: 0;
}
.buff-main {
    position: relative;
    z-index: 2;
}
.buff-sidebar {
    min-width: 0;
}
.runtime-buffs-page .card {
    margin-bottom: 0;
    padding: 18px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    box-shadow: none;
    background: var(--ds-surface);
    min-width: 0;
}
.runtime-buffs-page select.form-input,
.runtime-buffs-page input.form-input {
    background-color: #0f1418;
}
.runtime-buffs-page select.form-input option {
    background: #17212b;
    color: var(--ds-text-emphasis);
}
.runtime-buffs-page .card-header {
    margin-bottom: 16px;
}
.command-card {
    overflow: visible;
    position: relative;
    z-index: 5;
}
.command-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    min-width: 0;
}
.panel-kicker {
    color: var(--ds-primary);
    font-size: 11px;
    font-weight: 800;
    margin-bottom: 5px;
    letter-spacing: 0;
}
.summary-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(86px, 1fr));
    gap: 8px;
    flex-shrink: 0;
}
.summary-strip > div {
    border: 1px solid var(--ds-border);
    background: var(--ds-body-bg);
    border-radius: 8px;
    padding: 9px 10px;
    min-width: 0;
}
.summary-strip strong {
    display: block;
    color: var(--ds-text-emphasis);
    font-size: 18px;
    line-height: 1.1;
}
.summary-strip span {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.mail-target-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    margin-bottom: 14px;
    min-width: 0;
}
.toggle-field {
    display: flex;
    align-items: center;
    gap: 12px;
    height: 40px;
    padding: 0 12px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-body-bg);
    min-width: 0;
}
.toggle-field strong {
    display: block;
    color: var(--ds-text);
    font-size: 13px;
}
.toggle-field span:not(.toggle-slider) {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.toggle {
    position: relative;
    display: inline-block;
    width: 42px;
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
    inset: 0;
    background: var(--ds-gray-200);
    border-radius: 999px;
    transition: 0.2s ease;
}
.toggle-slider::before {
    content: "";
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    top: 3px;
    border-radius: 50%;
    background: var(--ds-gray-400);
    transition: 0.2s ease;
}
.toggle input:checked + .toggle-slider {
    background: var(--ds-primary);
}
.toggle input:checked + .toggle-slider::before {
    transform: translateX(18px);
    background: #fff;
}
.item-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    align-items: start;
    margin-bottom: 14px;
    min-width: 0;
}
.item-search-wrap,
.option-select-wrap {
    position: relative;
    min-width: 0;
    z-index: 20;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
}
.item-search-results,
.option-dropdown {
    position: absolute;
    z-index: 5000;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    max-height: 320px;
    overflow: auto;
    background: #17212b;
    border: 1px solid rgba(143, 211, 196, 0.28);
    border-radius: 8px;
    box-shadow:
        0 18px 42px rgba(0, 0, 0, 0.42),
        0 0 0 1px rgba(0, 0, 0, 0.22);
    backdrop-filter: none;
}
.option-dropdown {
    min-width: 260px;
    max-height: 260px;
}
.item-result,
.option-dropdown-item {
    width: 100%;
    border: 0;
    background: transparent;
    color: var(--ds-text);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    text-align: left;
    cursor: pointer;
}
.item-result:hover,
.option-dropdown-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.16);
}
.item-result-info {
    display: grid;
    gap: 2px;
    flex: 1;
    min-width: 0;
}
.item-result-info small,
.item-meta,
.td-muted {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.empty-panel {
    min-height: 260px;
    border: 1px dashed var(--ds-border);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--ds-text-muted);
    text-align: center;
    padding: 24px;
}
.empty-panel .mi {
    font-size: 34px;
    color: var(--ds-primary);
}
.empty-panel strong {
    color: var(--ds-text);
}
.empty-panel p {
    margin: 0;
    max-width: 420px;
}
.buff-items-table-wrap {
    overflow: visible;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    position: relative;
    z-index: 1;
}
.buff-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    table-layout: fixed;
}
.buff-items-table th {
    background: var(--ds-surface-2);
    padding: 9px 10px;
    text-align: left;
    font-size: 11px;
    font-weight: 800;
    color: var(--ds-text-muted);
    text-transform: uppercase;
    letter-spacing: 0;
    border-bottom: 1px solid var(--ds-border);
}
.buff-items-table td {
    padding: 10px;
    border-bottom: 1px solid var(--ds-border);
    vertical-align: middle;
}
.buff-items-table tr:last-child td {
    border-bottom: 0;
}
.buff-items-table tr:hover td {
    background: rgba(var(--ds-primary-rgb), 0.035);
}
.buff-items-table col.col-index {
    width: 38px;
}
.buff-items-table col.col-item {
    width: 36%;
}
.buff-items-table col.col-qty {
    width: 110px;
}
.buff-items-table col.col-options {
    width: auto;
}
.buff-items-table col.col-actions {
    width: 42px;
}
.item-index {
    color: var(--ds-text-muted);
    text-align: center;
}
.table-item {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.table-item-info {
    display: grid;
    gap: 2px;
    min-width: 0;
}
.table-item-info strong {
    color: var(--ds-text-emphasis);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.table-item-info span {
    color: var(--ds-primary-lighter);
    font-size: 12px;
}
.input-sm {
    min-height: 34px;
    padding: 7px 10px !important;
    font-size: 13px !important;
}
.qty-input {
    width: 80px;
}
.table-options {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
    position: relative;
    z-index: 2;
}
.option-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 24px;
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-primary-lighter);
    padding: 3px 8px;
    font-size: 12px;
    max-width: 260px;
}
.option-pill button {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    padding: 0 1px;
    font-size: 14px;
}
.option-editor-pill {
    display: inline-grid;
    grid-template-columns: minmax(240px, 1fr) 72px 28px 28px;
    align-items: center;
    gap: 6px;
    min-width: min(420px, 100%);
    flex: 1 1 420px;
}
.option-search-input,
.option-param-input {
    min-height: 30px;
    padding: 5px 8px !important;
    font-size: 12px !important;
}
.option-plus-btn {
    width: 28px;
    height: 22px;
    border-radius: 999px;
    border: 1px dashed rgba(var(--ds-primary-rgb), 0.45);
    color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.06);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.option-plus-btn .mi {
    font-size: 16px;
}
.option-plus-btn:hover {
    background: rgba(var(--ds-primary-rgb), 0.12);
}
.option-cancel-btn,
.option-confirm-btn,
.row-delete-btn {
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--ds-text-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.option-cancel-btn .mi,
.option-confirm-btn .mi,
.row-delete-btn .mi {
    font-size: 18px;
}
.option-confirm-btn {
    color: var(--ds-primary);
}
.option-confirm-btn:hover {
    color: #fff;
    background: var(--ds-primary);
}
.option-cancel-btn:hover,
.row-delete-btn:hover {
    color: var(--ds-danger);
    background: rgba(var(--ds-danger-rgb), 0.12);
}
.opt-id {
    min-width: 32px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.option-dropdown-empty {
    color: var(--ds-text-muted);
    text-align: center;
    padding: 12px;
    font-size: 12px;
}
.money-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.checkbox-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--ds-border);
    background: var(--ds-body-bg);
    border-radius: 8px;
    padding: 11px 12px;
    cursor: pointer;
}
.checkbox-card input {
    position: absolute;
    opacity: 0;
}
.checkbox-card .box {
    width: 18px;
    height: 18px;
    border-radius: 5px;
    border: 1px solid var(--ds-border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.checkbox-card input:checked + .box {
    background: var(--ds-primary);
    border-color: var(--ds-primary);
}
.checkbox-card input:checked + .box::before {
    content: "check";
    font-family: "Material Icons Round";
    color: #fff;
    font-size: 15px;
}
.suggestion-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 10px 0;
    border-top: 1px solid var(--ds-border);
}
.suggestion-item:first-child {
    border-top: 0;
}
.suggestion-item .mi {
    color: var(--ds-primary);
}
.suggestion-item strong,
.suggestion-item small {
    display: block;
}
.suggestion-item small {
    color: var(--ds-text-muted);
    margin-top: 2px;
    line-height: 1.4;
}
.picker-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    background: rgba(0, 0, 0, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.picker-panel {
    width: min(980px, 100%);
    height: min(820px, calc(100vh - 48px));
    max-height: calc(100vh - 48px);
    display: flex;
    flex-direction: column;
    background: #151d25;
    border: 1px solid rgba(143, 211, 196, 0.24);
    border-radius: 8px;
    box-shadow: var(--ds-shadow-xl);
    overflow: hidden;
}
.picker-head,
.picker-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--ds-border);
}
.picker-head h3,
.picker-head p {
    margin: 0;
}
.picker-head p {
    color: var(--ds-text-muted);
    margin-top: 3px;
}
.picker-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.picker-close:hover {
    background: rgba(var(--ds-primary-rgb), 0.1);
}
.picker-tools {
    display: grid;
    grid-template-columns:
        minmax(0, 1.4fr) minmax(160px, 0.8fr) minmax(150px, 0.7fr)
        auto;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--ds-border);
}
.picker-list {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 10px;
}
.picker-item {
    width: 100%;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 28px;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--ds-text);
    text-align: left;
    cursor: pointer;
}
.picker-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.16);
}
.picker-item-name {
    font-weight: 700;
}
.picker-item-meta {
    color: var(--ds-text-muted);
    font-size: 12px;
    margin-top: 2px;
}
.picker-empty {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ds-text-muted);
}
.picker-foot {
    border-top: 1px solid var(--ds-border);
    border-bottom: 0;
    flex-wrap: wrap;
}
.picker-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.picker-page-list {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.picker-pagination-ellipsis,
.picker-pagination-summary {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.picker-page-input {
    width: 72px;
    min-height: 30px;
    padding: 5px 8px;
}
.btn-xs {
    min-height: 30px;
    padding: 5px 9px;
    font-size: 12px;
}
.btn-xs.active {
    background: var(--ds-primary);
    color: #fff;
    border-color: var(--ds-primary);
}
@media (max-width: 1200px) {
    .buff-layout {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 820px) {
    .command-head,
    .mail-target-grid,
    .item-toolbar,
    .picker-tools,
    .money-grid {
        grid-template-columns: 1fr;
    }
    .command-head {
        display: grid;
    }
    .summary-strip {
        grid-template-columns: repeat(3, 1fr);
    }
    .picker-overlay {
        padding: 10px;
    }
    .buff-items-table-wrap {
        overflow-x: auto;
        overflow-y: visible;
    }
    .buff-items-table {
        min-width: 860px;
    }
    .picker-panel {
        height: calc(100vh - 20px);
        max-height: calc(100vh - 20px);
    }
}
</style>


