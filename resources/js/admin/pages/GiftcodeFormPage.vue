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
            <div class="page-top-actions">
                <button
                    v-if="isEdit"
                    type="button"
                    class="btn btn-outline"
                    @click="cloneCurrentGiftcode"
                >
                    <span class="mi" style="font-size: 16px">content_copy</span>
                    Clone
                </button>
                <router-link
                    :to="{ name: 'admin.giftcodes' }"
                    class="btn btn-outline"
                >
                    <span class="mi" style="font-size: 16px">arrow_back</span> Quay
                    lại
                </router-link>
            </div>
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
                            <div class="card-header-actions">
                                <span class="item-count"
                                    >{{ items.length }} vật phẩm</span
                                >
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
                        <div v-if="quickItems.length" class="quick-picks">
                            <span class="quick-picks-label">Item hay dùng</span>
                            <button
                                v-for="it in quickItems"
                                :key="'quick-item-' + it.id"
                                type="button"
                                class="quick-pill"
                                @click="addItem(it)"
                            >
                                <AdminIcon :icon-id="it.icon_id" />
                                <span>{{ it.name }}</span>
                            </button>
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
                                        <th class="th-qty">Số lượng</th>
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
                                            <AdminIcon :icon-id="item.icon_id" />
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
                                                v-model.number="item.quantity"
                                                type="number"
                                                class="form-input input-sm t-input"
                                                min="1"
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
                                                    @click="editOption(item, opt)"
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
                                                    @click="addPendingOption(item)"
                                                    title="Thêm option"
                                                >
                                                    <span
                                                        class="mi"
                                                        style="font-size: 14px"
                                                        >add</span
                                                    >
                                                </button>
                                            </div>
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
                                                    @click="cancelPendingOption(item)"
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
                                    :class="form.active == 0 ? 'active-on' : ''"
                                >
                                    {{
                                        form.active == 0
                                            ? "Đang hiển thị"
                                            : "Đang ẩn"
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="sidebar-field">
                            <label class="form-label">Ngày hết hạn</label>
                            <div class="expire-controls">
                                <input
                                    v-model="form.expired"
                                    class="form-input"
                                    type="datetime-local"
                                />
                                <div class="expire-actions">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        @click="setExpireAfterOneYear"
                                    >
                                        +1 năm
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        @click="clearExpire"
                                    >
                                        Xoá hạn
                                    </button>
                                </div>
                            </div>
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
                                ID: {{ row.id }} | {{ itemTypeLabel(row.type) }}
                            </div>
                        </div>
                        <span class="mi" style="font-size: 18px">add</span>
                    </button>
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
                            :disabled="
                                itemPicker.page >= itemPicker.totalPages
                            "
                            @click="goToItemPickerPage(itemPicker.page + 1)"
                        >
                            Sau
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline btn-xs"
                            :disabled="
                                itemPicker.page >= itemPicker.totalPages
                            "
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
export default {
    data() {
        return {
            form: {
                code: "",
                count_left: 100,
                mtv: "0",
                expired: "",
                active: "0",
            },
            giftcode: {},
            viewMode: "table",
            items: [],
            quickItems: [],
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
            quickItemsKey: "admin_quick_items",
            optionsCacheKey: "admin_item_options_v1",
            optionsCacheTtlMs: 1000 * 60 * 30,
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
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
        "$route.params.id"() {
            this.items = [];
            if (this.isEdit) {
                this.loadGiftcode();
            }
        },
    },
    created() {
        this.loadQuickItems();
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
        fixJson(str) {
            if (typeof str !== "string") return str;
            let s = str.trim();
            s = s.replace(/,\s*([\]\}])/g, "$1");
            s = s.replace(/([\[\{])\s*,/g, "$1");
            s = s.replace(/,\s*,/g, ",");
            return s;
        },
        toDateTimeLocal(value) {
            if (!value) return "";
            const str = String(value).trim();
            if (!str) return "";
            const normalized = str.replace(" ", "T");
            const d = new Date(normalized);
            if (Number.isNaN(d.getTime())) {
                return normalized.slice(0, 16);
            }
            const pad = (n) => String(n).padStart(2, "0");
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },
        toApiDateTime(value) {
            if (!value) return null;
            return `${value.replace("T", " ")}:00`;
        },
        setExpireAfterOneYear() {
            const d = new Date();
            d.setSeconds(0, 0);
            d.setFullYear(d.getFullYear() + 1);
            const pad = (n) => String(n).padStart(2, "0");
            this.form.expired = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },
        clearExpire() {
            this.form.expired = "";
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
        loadQuickItems() {
            try {
                const raw = localStorage.getItem(this.quickItemsKey);
                const parsed = raw ? JSON.parse(raw) : [];
                this.quickItems = Array.isArray(parsed) ? parsed.slice(0, 10) : [];
            } catch {
                this.quickItems = [];
            }
        },
        rememberQuickItem(item) {
            if (!item || !item.id) return;
            const normalized = {
                id: Number(item.id),
                name: item.name || `Item #${item.id}`,
                icon_id:
                    item.icon_id === null ||
                    item.icon_id === undefined ||
                    item.icon_id === ""
                        ? null
                        : Number(item.icon_id),
            };
            const next = [
                normalized,
                ...this.quickItems.filter((it) => Number(it.id) !== normalized.id),
            ].slice(0, 10);
            this.quickItems = next;
            try {
                localStorage.setItem(this.quickItemsKey, JSON.stringify(next));
            } catch {
                // ignore storage error
            }
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
                    lite: "1",
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
            if (found) {
                return `TYPE: ${found.id} - ${found.name}`;
            }
            return `TYPE: ${typeValue}`;
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
                (o) => String(o.name || "").trim().toLowerCase() === normalized,
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
            } else if (opt.id && (!opt.search || !opt.search.trim())) {
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
            if (cached?.length) {
                this.allOptions = cached;
                return;
            }
            try {
                const res = await fetch("/admin/api/options", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                this.allOptions = data || [];
                if (Array.isArray(this.allOptions) && this.allOptions.length) {
                    this.writeCachedOptions(this.allOptions);
                }
            } catch {
                //
            }
        },
        async loadGiftcode() {
            try {
                this.items = [];
                this.error = "";
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
                    this.form.expired = this.toDateTimeLocal(gc.expired);
                    this.form.active = gc.active ? "1" : "0";

                    let detail = [];
                    try {
                        const raw =
                            typeof gc.detail === "string"
                                ? gc.detail
                                : JSON.stringify(gc.detail);
                        detail = JSON.parse(this.fixJson(raw));
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
                            icon_id: itemInfo?.icon_id ?? null,
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
            this.rememberQuickItem(item);
            this.itemQuery = "";
            this.searchResults = [];
            this.showResults = false;
        },
        buildDetail() {
            return JSON.stringify(
                this.items.map((item) => ({
                    temp_id: item.temp_id,
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
                    code: this.form.code,
                    count_left: this.form.count_left,
                    mtv: parseInt(this.form.mtv),
                    expired: this.toApiDateTime(this.form.expired),
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
                    if (!this.isEdit) {
                        this.$router.push({ name: "admin.giftcodes" });
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
        async cloneCurrentGiftcode() {
            if (!this.isEdit) return;
            if (!confirm(`Clone giftcode "${this.form.code}"?`)) return;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch(
                    `/admin/api/giftcodes/${this.$route.params.id}/clone`,
                    {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": token,
                        },
                    },
                );
                const data = await res.json();
                if (data.ok && data.id) {
                    this.$router.push({
                        name: "admin.giftcodes.edit",
                        params: { id: data.id },
                    });
                }
            } catch {
                this.error = "Không thể clone giftcode";
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
.page-top-actions {
    display: inline-flex;
    align-items: center;
    gap: 10px;
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
.sidebar-field {
    margin-bottom: 16px;
}
.expire-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.expire-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
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
.quick-picks {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}
.quick-picks-label {
    font-size: 12px;
    color: var(--ds-text-muted);
}
.quick-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-1);
    color: var(--ds-text);
    padding: 4px 8px;
    border-radius: 999px;
    cursor: pointer;
    font-size: 12px;
}
.quick-pill:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.45);
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.quick-pill img {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    background: var(--ds-gray-100);
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
}
</style>
