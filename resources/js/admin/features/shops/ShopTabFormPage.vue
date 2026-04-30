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

        <div v-if="loading" class="admin-loading-block shop-tab-loading">
            <div class="admin-loading-spinner"></div>
        </div>

        <form v-else @submit.prevent="save">
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
                                <span
                                    v-if="itemsHydrating"
                                    class="admin-loading-inline"
                                >
                                    <span class="admin-loading-spinner"></span>
                                    Đang tải chi tiết item
                                </span>
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    @click="openItemPicker"
                                >
                                    <span class="mi" style="font-size: 15px"
                                        >list</span
                                    >
                                    Chọn item
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    :disabled="!selectedItemCount"
                                    @click="removeSelectedItems"
                                >
                                    <span class="mi" style="font-size: 15px"
                                        >delete</span
                                    >
                                    Xóa chọn
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    :disabled="!undoStack.length"
                                    @click="undoLastRemove"
                                >
                                    <span class="mi" style="font-size: 15px"
                                        >undo</span
                                    >
                                    Hoàn tác
                                </button>
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
                                    <AdminIcon :icon-id="item.icon_id" />
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
                                        <th class="th-select">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    allCurrentItemsSelected
                                                "
                                                :disabled="!items.length"
                                                @change="
                                                    toggleSelectAllItems(
                                                        $event.target.checked,
                                                    )
                                                "
                                            />
                                        </th>
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
                                        :key="'t' + item._local_id"
                                    >
                                        <td class="td-select">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    isItemSelected(item)
                                                "
                                                @change="
                                                    toggleItemSelection(item)
                                                "
                                            />
                                        </td>
                                        <td class="td-idx">{{ idx + 1 }}</td>
                                        <td class="td-icon">
                                            <AdminIcon
                                                :icon-id="item.icon_id"
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
                                                <option :value="2">
                                                    Hồng ngọc
                                                </option>
                                                <option :value="3">
                                                    Đặc biệt
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="spec-cell">
                                                <AdminIcon
                                                    v-if="item.item_spec"
                                                    class="spec-icon"
                                                    :icon-id="
                                                        specIconId(
                                                            item.item_spec,
                                                        )
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
                                                    @click="
                                                        editOption(item, opt)
                                                    "
                                                    title="Bấm để sửa option"
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
                                                        @click.stop="
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
                                                        addPendingOption(item)
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
                                                        @keyup.enter="
                                                            confirmOption(item)
                                                        "
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
                                                    @keyup.enter="
                                                        confirmOption(item)
                                                    "
                                                />
                                                <button
                                                    type="button"
                                                    class="t-opt-confirm"
                                                    @mousedown.prevent="
                                                        confirmOption(item)
                                                    "
                                                    @click="confirmOption(item)"
                                                    title="Xác nhận"
                                                >
                                                    <span
                                                        class="mi"
                                                        style="font-size: 16px"
                                                        >check</span
                                                    >
                                                </button>
                                                <button
                                                    type="button"
                                                    class="t-opt-cancel"
                                                    @click="
                                                        cancelPendingOption(
                                                            item,
                                                        )
                                                    "
                                                    title="Hủy"
                                                >
                                                    <span
                                                        class="mi"
                                                        style="font-size: 16px"
                                                        >close</span
                                                    >
                                                </button>
                                            </div>
                                        </td>
                                        <td class="td-act">
                                            <button
                                                type="button"
                                                class="item-remove-btn"
                                                @click="removeItem(idx)"
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
                                :key="item._local_id"
                                class="item-card"
                                :class="{ selected: isItemSelected(item) }"
                            >
                                <div class="item-card-top">
                                    <div class="item-card-head">
                                        <input
                                            type="checkbox"
                                            class="item-card-select"
                                            :checked="isItemSelected(item)"
                                            @change="
                                                toggleItemSelection(item)
                                            "
                                        />
                                        <AdminIcon
                                            class="item-card-icon"
                                            :icon-id="item.icon_id"
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
                                        @click="removeItem(idx)"
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
                                                <option :value="2">
                                                    Hồng ngọc
                                                </option>
                                                <option :value="3">
                                                    Đặc biệt
                                                </option>
                                            </select>
                                        </div>
                                        <div class="item-card-field">
                                            <label>Item Spec</label>
                                            <div class="spec-cell">
                                                <AdminIcon
                                                    v-if="item.item_spec"
                                                    class="spec-icon"
                                                    :icon-id="
                                                        specIconId(
                                                            item.item_spec,
                                                        )
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
                                                    id: null,
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
                                                @keyup.enter="
                                                    normalizeOptionInput(opt)
                                                "
                                                @focus="opt.showDrop = true"
                                                @input="opt.showDrop = true"
                                                @blur="
                                                    setTimeout(
                                                        () =>
                                                            normalizeOptionInput(
                                                                opt,
                                                            ),
                                                        120,
                                                    )
                                                "
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
                                            @keyup.enter="
                                                normalizeOptionInput(opt)
                                            "
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
                            <span
                                v-if="saving"
                                class="admin-loading-dot"
                            ></span>
                            <span class="mi" style="font-size: 16px">save</span>
                            {{ saving ? "Đang lưu..." : "Lưu thay đổi" }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline btn-block"
                            :disabled="runtimeLoading"
                            style="margin-top: 10px"
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
                <div class="picker-bulkbar">
                    <span>
                        Đã chọn {{ itemPickerSelectedCount }} item để thêm
                    </span>
                    <div class="picker-bulk-actions">
                        <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            :disabled="!itemPickerSelectedCount"
                            @click="addSelectedPickerItems"
                        >
                            <span class="mi" style="font-size: 15px">add</span>
                            Thêm đã chọn
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            :disabled="!itemPickerSelectedCount"
                            @click="clearPickerSelection"
                        >
                            Bỏ chọn
                        </button>
                    </div>
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
                    <div
                        v-else
                        v-for="row in itemPicker.rows"
                        :key="'picker-item-' + row.id"
                        class="picker-item"
                        :class="{ selected: isPickerItemSelected(row) }"
                        role="button"
                        tabindex="0"
                        @click="togglePickerItem(row)"
                        @keydown.enter.prevent="togglePickerItem(row)"
                        @keydown.space.prevent="togglePickerItem(row)"
                    >
                        <input
                            type="checkbox"
                            class="picker-select-checkbox"
                            :checked="isPickerItemSelected(row)"
                            @click.stop
                            @change="togglePickerItem(row)"
                        />
                        <AdminIcon :icon-id="row.icon_id" />
                        <div class="picker-item-info">
                            <div class="picker-item-name">{{ row.name }}</div>
                            <div class="picker-item-meta">
                                ID: {{ row.id }} | {{ itemTypeLabel(row.type) }}
                            </div>
                        </div>
                        <button
                            type="button"
                            class="picker-add-one"
                            title="Thêm ngay item này"
                            @click.stop="pickItemFromPicker(row)"
                        >
                            <span class="mi" style="font-size: 18px">add</span>
                        </button>
                    </div>
                </div>

                <div class="picker-foot">
                    <span>
                        {{ itemPicker.total.toLocaleString("vi-VN") }} item
                    </span>
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
import { buildPaginationItems, fixJson } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";
import {
    applyItemPickerResponse,
    itemPickerTypes,
    itemTypeLabel,
    normalizePickerPage,
    resetItemPickerResults,
} from "../../shared/itemCatalog";
export default {
    data() {
        return {
            form: { tab_name: "", tab_index: 0 },
            shopName: "",
            viewMode: "table",
            items: [],
            allOptions: [],
            specIconMap: {},
            itemPicker: {
                open: false,
                loading: false,
                rows: [],
                selected: {},
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
            selectedItemKeys: {},
            undoStack: [],
            localIdSeq: 1,
            loading: false,
            itemsHydrating: false,
            loadSeq: 0,
            error: "",
            success: "",
            saving: false,
            runtimeLoading: false,
            searchTimeout: null,
            itemPickerSearchTimer: null,
            optionsCacheKey: "admin_item_options_v3",
            optionsCacheTtlMs: 1000 * 60 * 30,
        };
    },
    computed: {
        tabId() {
            return this.$route.params.tabId;
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
        selectedItemCount() {
            return Object.keys(this.selectedItemKeys || {}).length;
        },
        allCurrentItemsSelected() {
            return (
                this.items.length > 0 &&
                this.items.every((item) => this.isItemSelected(item))
            );
        },
        itemPickerSelectedCount() {
            return Object.keys(this.itemPicker.selected || {}).length;
        },
    },
    watch: {
        tabId() {
            this.loadTab();
        },
    },
    async created() {
        await this.loadOptions();
        this.loadTab();
        document.addEventListener("click", this.closeResults);
    },
    unmounted() {
        this.loadSeq += 1;
        document.removeEventListener("click", this.closeResults);
        window.clearTimeout(this.searchTimeout);
        window.clearTimeout(this.itemPickerSearchTimer);
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
        nextLocalId() {
            const id = `shop-item-${Date.now()}-${this.localIdSeq}`;
            this.localIdSeq += 1;
            return id;
        },
        ensureLocalId(item) {
            if (!item._local_id) {
                item._local_id = this.nextLocalId();
            }
            return item._local_id;
        },
        cloneShopItem(item) {
            return JSON.parse(JSON.stringify(item));
        },
        makeShopItem(item, overrides = {}) {
            return {
                _local_id: this.nextLocalId(),
                temp_id: Number(item.id ?? item.temp_id ?? 0),
                name: item.name || `Item #${item.id ?? item.temp_id ?? 0}`,
                icon_id: item.icon_id ?? null,
                cost: Number(overrides.cost ?? item.cost ?? 0),
                type_sell: Number(overrides.type_sell ?? item.type_sell ?? 0),
                is_new: !!(overrides.is_new ?? item.is_new ?? false),
                is_sell: (overrides.is_sell ?? item.is_sell) !== false,
                item_spec: Number(
                    overrides.item_spec ?? item.item_spec ?? 0,
                ),
                options: Array.isArray(overrides.options)
                    ? overrides.options
                    : Array.isArray(item.options)
                      ? item.options
                      : [],
            };
        },
        itemKey(item) {
            return this.ensureLocalId(item);
        },
        isItemSelected(item) {
            return !!this.selectedItemKeys[this.itemKey(item)];
        },
        toggleItemSelection(item, force = null) {
            const key = this.itemKey(item);
            const shouldSelect =
                force === null ? !this.selectedItemKeys[key] : !!force;
            const next = { ...this.selectedItemKeys };
            if (shouldSelect) {
                next[key] = true;
            } else {
                delete next[key];
            }
            this.selectedItemKeys = next;
        },
        toggleSelectAllItems(checked) {
            if (!checked) {
                this.selectedItemKeys = {};
                return;
            }
            const next = {};
            this.items.forEach((item) => {
                next[this.itemKey(item)] = true;
            });
            this.selectedItemKeys = next;
        },
        pushUndoRemove(entries) {
            if (!entries.length) return;
            this.undoStack = [
                ...this.undoStack,
                { type: "remove", entries },
            ].slice(-20);
        },
        removeItem(index) {
            const item = this.items[index];
            if (!item) return;
            const key = this.itemKey(item);
            this.pushUndoRemove([
                {
                    index,
                    item: this.cloneShopItem(item),
                },
            ]);
            this.items.splice(index, 1);
            const next = { ...this.selectedItemKeys };
            delete next[key];
            this.selectedItemKeys = next;
        },
        removeSelectedItems() {
            const selected = this.selectedItemKeys || {};
            const entries = [];
            const keep = [];
            this.items.forEach((item, index) => {
                if (selected[this.itemKey(item)]) {
                    entries.push({
                        index,
                        item: this.cloneShopItem(item),
                    });
                } else {
                    keep.push(item);
                }
            });
            if (!entries.length) return;
            this.pushUndoRemove(entries);
            this.items = keep;
            this.selectedItemKeys = {};
        },
        undoLastRemove() {
            const action = this.undoStack[this.undoStack.length - 1];
            if (!action || action.type !== "remove") return;
            const restored = [...this.items];
            action.entries
                .slice()
                .sort((a, b) => a.index - b.index)
                .forEach((entry) => {
                    const item = this.cloneShopItem(entry.item);
                    const exists = restored.some(
                        (row) => row._local_id === item._local_id,
                    );
                    if (exists) item._local_id = this.nextLocalId();
                    restored.splice(
                        Math.min(entry.index, restored.length),
                        0,
                        item,
                    );
                });
            this.items = restored;
            this.selectedItemKeys = {};
            this.undoStack = this.undoStack.slice(0, -1);
        },
        async fetchItemsBatch(ids) {
            const uniqueIds = [
                ...new Set(
                    ids
                        .map((id) => Number(id))
                        .filter((id) => Number.isFinite(id) && id >= 0),
                ),
            ];
            const chunks = [];
            for (let i = 0; i < uniqueIds.length; i += 150) {
                chunks.push(uniqueIds.slice(i, i + 150));
            }

            const itemMap = {};
            for (const chunk of chunks) {
                const res = await fetch(
                    `/admin/api/items/batch?ids=${chunk.join(",")}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                Object.assign(
                    itemMap,
                    await readJsonResponse(res, "Không thể tải item"),
                );
            }
            return itemMap;
        },
        buildShopItems(detail, itemMap = {}) {
            return detail.map((d) => {
                const itemInfo = itemMap[d.temp_id] || null;
                return {
                    _local_id: this.nextLocalId(),
                    temp_id: d.temp_id,
                    name: itemInfo?.name || "Item #" + d.temp_id,
                    icon_id: itemInfo?.icon_id ?? null,
                    cost: d.cost || 0,
                    type_sell: d.type_sell ?? 0,
                    is_new: !!d.is_new,
                    is_sell: d.is_sell !== false,
                    item_spec: d.item_spec || 0,
                    options: (d.options || []).map((o) => {
                        const matched = this.allOptions.find(
                            (ao) => Number(ao.id) === Number(o.id),
                        );
                        return {
                            id:
                                o.id === null || o.id === undefined
                                    ? 0
                                    : Number(o.id),
                            param: Number(o.param || 0),
                            search: matched
                                ? `${matched.name} (ID: ${matched.id})`
                                : o.id
                                  ? `ID: ${o.id}`
                                  : "",
                            showDrop: false,
                        };
                    }),
                };
            });
        },
        async hydrateShopItems(detail, allIds, seq) {
            if (!allIds.length) return;
            this.itemsHydrating = true;
            try {
                const itemMap = await this.fetchItemsBatch(allIds);
                if (seq !== this.loadSeq) return;

                const specIds = detail
                    .map((d) => d.item_spec)
                    .filter((id) => id && id > 0);
                const specIconMap = {};
                for (const sid of specIds) {
                    if (itemMap[sid]) {
                        specIconMap[sid] = itemMap[sid].icon_id;
                    }
                }

                this.specIconMap = specIconMap;
                this.items = this.buildShopItems(detail, itemMap);
                this.selectedItemKeys = {};
            } catch (e) {
                if (seq === this.loadSeq) {
                    this.error =
                        e?.message ||
                        "Đã tải tab, nhưng chưa tải được chi tiết item.";
                }
            } finally {
                if (seq === this.loadSeq) {
                    this.itemsHydrating = false;
                }
            }
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
        readCachedOptions() {
            try {
                const raw = localStorage.getItem(this.optionsCacheKey);
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                if (
                    !parsed ||
                    !Array.isArray(parsed.data) ||
                    !parsed.expires_at ||
                    Date.now() > Number(parsed.expires_at)
                ) {
                    localStorage.removeItem(this.optionsCacheKey);
                    return null;
                }
                return parsed.data;
            } catch {
                return null;
            }
        },
        writeCachedOptions(data) {
            try {
                localStorage.setItem(
                    this.optionsCacheKey,
                    JSON.stringify({
                        data,
                        expires_at: Date.now() + this.optionsCacheTtlMs,
                    }),
                );
            } catch {
                // ignore storage error
            }
        },
        normalizePickerPage(page) {
            return normalizePickerPage(page, this.itemPicker.totalPages);
        },
        goToItemPickerPage(page) {
            const target = this.normalizePickerPage(page);
            if (
                target === this.itemPicker.page &&
                this.itemPicker.rows.length
            ) {
                this.itemPicker.pageInput = String(target);
                return;
            }
            this.loadItemPicker(target);
        },
        jumpItemPickerPage() {
            this.goToItemPickerPage(this.itemPicker.pageInput);
        },
        debouncedLoadItemPicker() {
            window.clearTimeout(this.itemPickerSearchTimer);
            this.itemPickerSearchTimer = window.setTimeout(() => {
                this.loadItemPicker(1);
            }, 300);
        },
        async loadItemPicker(page = 1) {
            try {
                this.itemPicker.loading = true;
                this.itemPicker.page = this.normalizePickerPage(page);
                this.itemPicker.pageInput = String(this.itemPicker.page);
                const params = new URLSearchParams({
                    page: String(this.itemPicker.page),
                    per_page: "30",
                    lite: "1",
                });
                if (this.itemPicker.search && this.itemPicker.search.trim()) {
                    params.set("search", this.itemPicker.search.trim());
                }
                if (this.itemPicker.type !== "") {
                    params.set("type", this.itemPicker.type);
                }
                const res = await fetch(
                    `/admin/api/items?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể lọc item",
                );
                applyItemPickerResponse(this.itemPicker, data);
            } catch (e) {
                this.error = e?.message || "Không thể lọc item";
                resetItemPickerResults(this.itemPicker);
            } finally {
                this.itemPicker.loading = false;
            }
        },
        pickerItemKey(item) {
            return String(item?.id ?? "");
        },
        isPickerItemSelected(item) {
            return !!this.itemPicker.selected[this.pickerItemKey(item)];
        },
        togglePickerItem(item) {
            const key = this.pickerItemKey(item);
            if (!key) return;
            const next = { ...this.itemPicker.selected };
            if (next[key]) {
                delete next[key];
            } else {
                next[key] = {
                    id: Number(item.id),
                    name: item.name,
                    type: item.type,
                    icon_id: item.icon_id ?? null,
                };
            }
            this.itemPicker.selected = next;
        },
        clearPickerSelection() {
            this.itemPicker.selected = {};
        },
        addSelectedPickerItems() {
            const selected = Object.values(this.itemPicker.selected || {});
            if (!selected.length) return;
            selected.forEach((item) => {
                this.items.push(this.makeShopItem(item));
            });
            this.success = `Đã thêm ${selected.length} vật phẩm vào tab.`;
            this.error = "";
            this.clearPickerSelection();
        },
        pickItemFromPicker(item) {
            this.addItem(item);
        },
        itemTypeLabel(typeValue) {
            return itemTypeLabel(typeValue, this.itemPickerTypes);
        },
        optionName(id) {
            const o = this.allOptions.find((a) => Number(a.id) === Number(id));
            return o ? o.name : null;
        },
        optionLabel(id, param) {
            const o = this.allOptions.find((a) => Number(a.id) === Number(id));
            if (!o) return String(param);
            const name = String(o.name || "");
            return name.includes("#")
                ? name.replace("#", param)
                : name + ": " + param;
        },
        specIconId(specId) {
            if (!specId || specId <= 0) return specId;
            if (this.specIconMap[specId] !== undefined)
                return this.specIconMap[specId];
            this.specIconMap = {
                ...this.specIconMap,
                [specId]: null,
            };
            fetch(`/admin/api/items/batch?ids=${specId}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((r) => readJsonResponse(r, "Không thể tải item spec"))
                .then((data) => {
                    if (data[specId]) {
                        this.specIconMap = {
                            ...this.specIconMap,
                            [specId]: data[specId].icon_id,
                        };
                    }
                })
                .catch(() => {});
            return null;
        },
        filteredOptions(search) {
            const options = Array.isArray(this.allOptions) ? this.allOptions : [];
            if (!search || !search.trim()) return options;
            const q = search.trim().toLowerCase();
            return options
                .filter(
                    (o) =>
                        String(o.name || "").toLowerCase().includes(q) ||
                        String(o.id).includes(q),
                );
        },
        resolveOptionCandidate(search) {
            const keyword = String(search || "").trim();
            if (!keyword) return null;

            const idMatch = keyword.match(/\bID:\s*(\d+)\b/i);
            if (idMatch) {
                const foundByLabelId = this.allOptions.find(
                    (o) => Number(o.id) === Number(idMatch[1]),
                );
                if (foundByLabelId) return foundByLabelId;
            }

            if (/^\d+$/.test(keyword)) {
                const numeric = Number(keyword);
                const foundById = this.allOptions.find(
                    (o) => Number(o.id) === numeric,
                );
                if (foundById) return foundById;
            }

            const normalized = keyword.toLowerCase();
            const foundByExactName = this.allOptions.find(
                (o) =>
                    String(o.name || "")
                        .trim()
                        .toLowerCase() === normalized,
            );
            if (foundByExactName) return foundByExactName;

            const matches = this.filteredOptions(keyword);
            if (matches.length === 1) {
                return matches[0];
            }

            return null;
        },
        selectOption(opt, o) {
            opt.id = o.id;
            opt.search = `${o.name} (ID: ${o.id})`;
            opt.showDrop = false;
        },
        normalizeOptionInput(opt) {
            if (!opt) return;
            const resolved = this.resolveOptionCandidate(opt.search);
            if (resolved) {
                opt.id = resolved.id;
                opt.search = `${resolved.name} (ID: ${resolved.id})`;
            } else if (!resolved && /^\d+$/.test(opt.search)) {
                const numeric = Number(opt.search);
                const found = this.allOptions.find(
                    (o) => Number(o.id) === numeric,
                );
                if (found) {
                    opt.id = found.id;
                    opt.search = `${found.name} (ID: ${found.id})`;
                }
            } else if (
                this.hasOptionId(opt) &&
                (!opt.search || !opt.search.trim())
            ) {
                const name = this.optionName(opt.id);
                opt.search = name ? `${name} (ID: ${opt.id})` : `ID: ${opt.id}`;
            }
            opt.showDrop = false;
        },
        hasOptionId(opt) {
            if (!opt) return false;
            if (opt.id === null || opt.id === undefined || opt.id === "") {
                return false;
            }
            return !Number.isNaN(Number(opt.id));
        },
        addPendingOption(item) {
            if (item.options.some((o) => o._pending)) return;
            item.options.push({
                id: null,
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
            this.normalizeOptionInput(opt);
            if (!this.hasOptionId(opt)) return;
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
            if (idx >= 0) {
                item.options.splice(idx, 1);
            }
        },
        pendingOpt(item) {
            return item.options.find((o) => o._pending) || null;
        },
        async loadOptions() {
            const cached = this.readCachedOptions();
            if (Array.isArray(cached) && cached.length) {
                this.allOptions = cached;
                return;
            }
            try {
                const res = await fetch("/admin/api/options", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                this.allOptions = Array.isArray(data)
                    ? data
                    : Array.isArray(data?.data)
                      ? data.data
                      : Array.isArray(data?.options)
                        ? data.options
                        : [];
                if (Array.isArray(this.allOptions) && this.allOptions.length) {
                    this.writeCachedOptions(this.allOptions);
                }
            } catch {
                //
            }
        },
        async loadTab() {
            const seq = ++this.loadSeq;
            this.loading = true;
            this.itemsHydrating = false;
            this.selectedItemKeys = {};
            this.undoStack = [];
            try {
                this.items = [];
                this.specIconMap = {};
                this.error = "";
                const res = await fetch(`/admin/api/shops/tab/${this.tabId}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(
                    res,
                    "Không thể tải tab shop",
                );
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
                        detail = JSON.parse(fixJson(raw));
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
                    this.items = this.buildShopItems(detail);
                    this.loading = false;
                    this.hydrateShopItems(detail, allIds, seq);
                } else {
                    throw new Error(data.message || "Không thể tải tab shop");
                }
            } catch (e) {
                this.error = e?.message || "Không thể tải dữ liệu";
            } finally {
                if (seq === this.loadSeq) {
                    this.loading = false;
                }
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
                        `/admin/api/items?lite=1&per_page=30&search=${encodeURIComponent(q)}`,
                        {
                            headers: { "X-Requested-With": "XMLHttpRequest" },
                        },
                    );
                    const data = await readJsonResponse(
                        res,
                        "Không thể tìm item",
                    );
                    this.searchResults = data?.data || [];
                    this.showResults = true;
                } catch (e) {
                    this.error = e?.message || "Không thể tìm item";
                    this.searchResults = [];
                } finally {
                    this.searching = false;
                }
            }, 300);
        },
        addItem(item) {
            this.items.push(this.makeShopItem(item));
            this.itemQuery = "";
            this.searchResults = [];
            this.showResults = false;
            this.error = "";
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
                        .map((o) => {
                            if (o._pending) this.normalizeOptionInput(o);
                            return {
                                id: parseInt(o.id) || 0,
                                param: parseInt(o.param) || 0,
                            };
                        })
                        .filter((o) => o.id > 0),
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
        async reloadRuntimeShop() {
            this.error = "";
            this.success = "";
            this.runtimeLoading = true;
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
                this.success = data.message || "Đã cập nhật shop trong game.";
            } catch (e) {
                this.error = e?.message || "Không gọi được game runtime API.";
            } finally {
                this.runtimeLoading = false;
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
.item-card.selected {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.06);
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
.item-card-select {
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex: 0 0 auto;
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
    flex-wrap: wrap;
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
.items-table input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
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
.th-select {
    width: 36px;
    text-align: center !important;
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
.td-select {
    text-align: center;
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
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
}
.t-opt-pill:hover {
    background: rgba(var(--ds-primary-rgb), 0.17);
    border-color: rgba(var(--ds-primary-rgb), 0.35);
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
.t-opt-cancel:hover {
    background: rgba(var(--ds-danger-rgb), 0.2);
}
.td-opts {
    min-width: 160px;
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
.picker-close:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.5);
    color: var(--ds-text);
}
.picker-tools {
    display: grid;
    grid-template-columns: 1fr 180px auto;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border);
}
.picker-bulkbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    border-bottom: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    font-size: 12px;
}
.picker-bulk-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
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
.picker-item.selected {
    background: rgba(var(--ds-primary-rgb), 0.12);
    border-color: rgba(var(--ds-primary-rgb), 0.55);
}
.picker-select-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex: 0 0 auto;
}
.picker-item img {
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
.picker-add-one {
    width: 30px;
    height: 30px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    color: var(--ds-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex: 0 0 auto;
}
.picker-add-one:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.65);
    background: rgba(var(--ds-primary-rgb), 0.12);
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
    .picker-overlay {
        padding: 12px;
    }
    .picker-tools {
        grid-template-columns: 1fr;
    }
    .picker-bulkbar,
    .picker-foot {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>


