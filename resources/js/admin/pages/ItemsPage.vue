<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Items</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">
                        Trang chủ
                    </router-link>
                    <span class="sep">/</span>
                    <span class="current">Items</span>
                </nav>
            </div>
            <span v-if="total" class="total-count">
                {{ total.toLocaleString("vi-VN") }} items
            </span>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm ID hoặc tên..."
                        @input="debouncedLoadPage"
                    />
                </div>
                <select
                    v-model="typeFilter"
                    class="form-input"
                    style="width: 180px"
                    @change="loadPage(1)"
                >
                    <option value="">Tất cả type</option>
                    <option
                        v-for="t in displayTypeOptions"
                        :key="'type-opt-' + t.id"
                        :value="String(t.id)"
                    >
                        {{ t.name }} ({{ t.id }})
                    </option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">
                    <span class="mi" style="font-size: 16px">filter_list</span>
                    Lọc
                </button>
            </form>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>

        <div class="card">
            <div class="table-wrap">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Tên</th>
                            <th>Type</th>
                            <th>Mô tả</th>
                            <th>Gộp chung</th>
                            <th>Head</th>
                            <th>Body</th>
                            <th>Leg</th>
                            <th>Part</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="item in items" :key="item.id">
                            <tr>
                                <td>{{ item.id }}</td>
                                <td>
                                    <AdminIcon
                                        :icon-id="item.icon_id"
                                        class="item-icon"
                                    />
                                </td>
                                <td>
                                    <div class="item-name">{{ item.name }}</div>
                                    <div class="item-meta">
                                        Icon {{ item.icon_id }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ itemTypeLabel(item.type) }}
                                    </span>
                                </td>
                                <td>
                                    <div
                                        class="description-cell"
                                        :title="item.description || ''"
                                    >
                                        {{ item.description || "—" }}
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="
                                            item.is_up_to_up
                                                ? 'badge-success'
                                                : 'badge-muted'
                                        "
                                    >
                                        {{ item.is_up_to_up ? "Có" : "Không" }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.head >= 0"
                                        class="part-chip"
                                        :title="partChipTitle(item.head)"
                                    >
                                        #{{ item.head }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.body >= 0"
                                        class="part-chip"
                                        :title="partChipTitle(item.body)"
                                    >
                                        #{{ item.body }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.leg >= 0"
                                        class="part-chip"
                                        :title="partChipTitle(item.leg)"
                                    >
                                        #{{ item.leg }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.part >= 0"
                                        class="part-chip part-chip-main"
                                        :title="partChipTitle(item.part)"
                                    >
                                        #{{ item.part }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td class="action-cell">
                                    <button
                                        class="btn btn-primary btn-sm"
                                        @click="openEditor(item)"
                                    >
                                        <span
                                            class="mi"
                                            style="font-size: 14px"
                                            >edit</span
                                        >
                                        Sửa
                                    </button>
                                    <button
                                        class="btn btn-outline btn-sm"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{
                                            expandedId === item.id
                                                ? "Ẩn"
                                                : "Xem"
                                        }}
                                    </button>
                                </td>
                            </tr>
                            <tr
                                v-if="expandedId === item.id"
                                class="detail-row"
                            >
                                <td colspan="11">
                                    <div class="detail-panel">
                                        <div class="detail-header">
                                            <div>
                                                <div class="detail-title">
                                                    {{ item.name }}
                                                </div>
                                                <div class="detail-subtitle">
                                                    `part.DATA` gồm các phần tử
                                                    `[icon_id, dx, dy]`
                                                </div>
                                            </div>
                                            <div class="detail-stats">
                                                <span class="detail-stat">
                                                    Head:
                                                    {{
                                                        item.head >= 0
                                                            ? item.head
                                                            : "—"
                                                    }}
                                                </span>
                                                <span class="detail-stat">
                                                    Body:
                                                    {{
                                                        item.body >= 0
                                                            ? item.body
                                                            : "—"
                                                    }}
                                                </span>
                                                <span class="detail-stat">
                                                    Leg:
                                                    {{
                                                        item.leg >= 0
                                                            ? item.leg
                                                            : "—"
                                                    }}
                                                </span>
                                                <span class="detail-stat">
                                                    Part:
                                                    {{
                                                        item.part >= 0
                                                            ? item.part
                                                            : "—"
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="part-grid">
                                            <div
                                                v-for="block in detailBlocks(
                                                    item,
                                                )"
                                                :key="item.id + '-' + block.key"
                                                class="part-card"
                                            >
                                                <div class="part-card-head">
                                                    <div
                                                        class="part-card-title"
                                                    >
                                                        {{ block.label }}
                                                    </div>
                                                    <div class="part-card-meta">
                                                        ID #{{ block.part.id }}
                                                        |
                                                        {{
                                                            partTypeLabel(
                                                                block.part,
                                                            )
                                                        }}
                                                    </div>
                                                </div>

                                                <div
                                                    v-if="
                                                        block.part.layers.length
                                                    "
                                                    class="layer-grid"
                                                >
                                                    <div
                                                        v-for="(
                                                            layer, layerIndex
                                                        ) in block.part.layers"
                                                        :key="
                                                            block.key +
                                                            '-' +
                                                            layerIndex
                                                        "
                                                        class="layer-card"
                                                    >
                                                        <AdminIcon
                                                            :icon-id="
                                                                layer.icon_id
                                                            "
                                                            class="layer-icon"
                                                        />
                                                        <div class="layer-info">
                                                            <div
                                                                class="layer-name"
                                                            >
                                                                Ảnh
                                                                {{
                                                                    layer.icon_id
                                                                }}
                                                            </div>
                                                            <div
                                                                class="layer-meta"
                                                            >
                                                                dx:
                                                                {{ layer.dx }}
                                                                | dy:
                                                                {{ layer.dy }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="layer-empty">
                                                    Không có dữ liệu layer.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="11">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!items.length && !loading">
                            <td colspan="11" class="empty-cell">
                                Không có item nào.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="goToPage(1)">Đầu</button>
                <button :disabled="page <= 1" @click="goToPage(page - 1)">
                    &laquo;
                </button>
                <template v-for="p in paginationItems" :key="String(p)">
                    <span
                        v-if="typeof p !== 'number'"
                        class="pagination-ellipsis"
                    >
                        ...
                    </span>
                    <button
                        v-else
                        :class="{ active: p === page }"
                        @click="goToPage(p)"
                    >
                        {{ p }}
                    </button>
                </template>
                <button
                    :disabled="page >= totalPages"
                    @click="goToPage(page + 1)"
                >
                    &raquo;
                </button>
                <button
                    :disabled="page >= totalPages"
                    @click="goToPage(totalPages)"
                >
                    Cuối
                </button>
                <div class="pagination-jump">
                    <span class="pagination-summary"
                        >Trang {{ page }} / {{ totalPages }}</span
                    >
                    <input
                        v-model="pageInput"
                        type="number"
                        min="1"
                        :max="totalPages"
                        class="form-input pagination-input"
                        @keyup.enter="jumpToPage"
                    />
                    <button @click="jumpToPage">Đi</button>
                </div>
            </div>
        </div>

        <div v-if="editor.open" class="editor-overlay" @click.self="closeEditor">
            <div class="editor-panel">
                <div class="editor-head">
                    <div>
                        <h3>Sửa item #{{ editor.form.id }}</h3>
                        <p>Chỉ cập nhật dòng item_template đang chọn.</p>
                    </div>
                    <button class="icon-action" type="button" @click="closeEditor">
                        <span class="mi">close</span>
                    </button>
                </div>

                <div v-if="editor.error" class="alert alert-error">
                    {{ editor.error }}
                </div>

                <div class="editor-body">
                    <div class="editor-preview">
                        <AdminIcon
                            :icon-id="editorNumber('icon_id')"
                            class="editor-icon"
                        />
                        <div>
                            <strong>{{ editor.form.name || "Chưa đặt tên" }}</strong>
                            <small>
                                Type {{ editor.form.type }} ·
                                {{ itemGenderLabel(editor.form.gender) }}
                            </small>
                        </div>
                    </div>

                    <div class="editor-grid">
                        <label>
                            <span class="form-label">Tên item</span>
                            <input v-model.trim="editor.form.name" class="form-input" />
                        </label>
                        <label>
                            <span class="form-label">Icon ID</span>
                            <input
                                v-model.number="editor.form.icon_id"
                                class="form-input"
                                type="number"
                                min="0"
                            />
                        </label>
                        <label>
                            <span class="form-label">Type</span>
                            <input
                                v-model.number="editor.form.type"
                                class="form-input"
                                type="number"
                            />
                        </label>
                        <label>
                            <span class="form-label">Hệ/Gender</span>
                            <select v-model.number="editor.form.gender" class="form-input">
                                <option :value="0">Trái Đất (0)</option>
                                <option :value="1">Namek (1)</option>
                                <option :value="2">Xayda (2)</option>
                                <option :value="3">Chung/Tất cả (3)</option>
                            </select>
                        </label>
                        <label>
                            <span class="form-label">Part chính</span>
                            <input v-model.number="editor.form.part" class="form-input" type="number" min="-1" />
                        </label>
                        <label>
                            <span class="form-label">Head</span>
                            <input v-model.number="editor.form.head" class="form-input" type="number" min="-1" />
                        </label>
                        <label>
                            <span class="form-label">Body</span>
                            <input v-model.number="editor.form.body" class="form-input" type="number" min="-1" />
                        </label>
                        <label>
                            <span class="form-label">Leg</span>
                            <input v-model.number="editor.form.leg" class="form-input" type="number" min="-1" />
                        </label>
                    </div>

                    <label class="editor-description">
                        <span class="form-label">Mô tả</span>
                        <textarea
                            v-model="editor.form.description"
                            class="form-input"
                            rows="4"
                        ></textarea>
                    </label>

                    <label class="editor-check">
                        <input v-model="editor.form.is_up_to_up" type="checkbox" />
                        <span>Cho phép gộp/nâng cấp chung</span>
                    </label>

                    <div class="editor-note">
                        Đặt Part/Head/Body/Leg là -1 nếu item không dùng phần
                        hiển thị đó.
                    </div>
                </div>

                <div class="editor-actions">
                    <button class="btn btn-outline" type="button" :disabled="editor.saving" @click="closeEditor">
                        Hủy
                    </button>
                    <button class="btn btn-primary" type="button" :disabled="editor.saving" @click="saveEditor">
                        <span class="mi" style="font-size: 16px">save</span>
                        {{ editor.saving ? "Đang lưu..." : "Lưu item" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            items: [],
            partMap: {},
            types: [],
            typeOptions: [],
            search: "",
            typeFilter: "",
            loading: false,
            page: 1,
            pageInput: "1",
            totalPages: 1,
            total: 0,
            expandedId: null,
            searchTimer: null,
            error: "",
            editor: {
                open: false,
                saving: false,
                error: "",
                form: {},
            },
        };
    },
    computed: {
        paginationItems() {
            return this.buildPaginationItems(this.page, this.totalPages);
        },
        displayTypeOptions() {
            if (Array.isArray(this.typeOptions) && this.typeOptions.length) {
                return this.typeOptions;
            }
            return (this.types || []).map((id) => ({
                id: Number(id),
                name: `Type ${id}`,
            }));
        },
    },
    created() {
        this.loadPage(1);
    },
    unmounted() {
        window.clearTimeout(this.searchTimer);
    },
    methods: {
        async readJsonResponse(res, fallbackMessage = "Không thể tải item") {
            const contentType = res.headers.get("content-type") || "";
            if (!res.ok) {
                throw new Error(`${fallbackMessage} (${res.status})`);
            }
            if (!contentType.includes("application/json")) {
                throw new Error("Phiên đăng nhập hết hạn hoặc API trả về sai định dạng");
            }
            return res.json();
        },
        debouncedLoadPage() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(() => {
                this.loadPage(1);
            }, 300);
        },
        buildPaginationItems(current, total) {
            if (total <= 7) {
                return Array.from({ length: total }, (_, index) => index + 1);
            }

            const pages = new Set([
                1,
                total,
                current - 1,
                current,
                current + 1,
            ]);
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
        normalizePage(page) {
            const value = Number(page);
            if (!Number.isFinite(value)) return 1;
            return Math.min(
                Math.max(1, Math.trunc(value)),
                this.totalPages || 1,
            );
        },
        goToPage(page) {
            const target = this.normalizePage(page);
            if (target === this.page && this.items.length) {
                this.pageInput = String(target);
                return;
            }
            this.loadPage(target);
        },
        jumpToPage() {
            this.goToPage(this.pageInput);
        },
        toggleExpanded(itemId) {
            this.expandedId = this.expandedId === itemId ? null : itemId;
        },
        itemTypeLabel(typeValue) {
            const key = String(typeValue ?? "").trim();
            const found = this.displayTypeOptions.find(
                (opt) => String(opt.id) === key,
            );
            if (found) return `${found.name} (${found.id})`;
            return `Type ${typeValue}`;
        },
        itemGenderLabel(genderValue) {
            const gender = Number(genderValue);
            if (gender === 0) return "Trái Đất";
            if (gender === 1) return "Namek";
            if (gender === 2) return "Xayda";
            if (gender === 3) return "Chung/Tất cả";
            return "Không rõ";
        },
        editorNumber(field) {
            const value = Number(this.editor.form?.[field] ?? 0);
            return Number.isFinite(value) ? value : 0;
        },
        token() {
            return document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
        },
        openEditor(item) {
            this.editor = {
                open: true,
                saving: false,
                error: "",
                form: {
                    id: item.id,
                    name: item.name || "",
                    type: Number(item.type ?? 0),
                    gender: Number(item.gender ?? 3),
                    icon_id: Number(item.icon_id ?? 0),
                    part: Number(item.part ?? -1),
                    head: Number(item.head ?? -1),
                    body: Number(item.body ?? -1),
                    leg: Number(item.leg ?? -1),
                    description: item.description || "",
                    is_up_to_up: !!item.is_up_to_up,
                },
            };
        },
        closeEditor() {
            if (this.editor.saving) return;
            this.editor.open = false;
            this.editor.error = "";
        },
        async saveEditor() {
            if (!this.editor.form.name) {
                this.editor.error = "Tên item không được để trống.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            try {
                const res = await fetch(
                    `/admin/api/items/${this.editor.form.id}`,
                    {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": this.token(),
                        },
                        body: JSON.stringify(this.editor.form),
                    },
                );
                const data = await this.readJsonResponse(
                    res,
                    "Không thể lưu item",
                );
                if (!data.ok) {
                    throw new Error(data.message || "Không thể lưu item");
                }
                this.editor.saving = false;
                this.closeEditor();
                await this.loadPage(this.page);
            } catch (e) {
                this.editor.error = e?.message || "Không thể lưu item";
            } finally {
                this.editor.saving = false;
            }
        },
        partById(partId) {
            if (partId === null || partId === undefined || Number(partId) < 0) {
                return null;
            }
            return (
                this.partMap[String(partId)] ||
                this.partMap[Number(partId)] ||
                null
            );
        },
        partChipTitle(partId) {
            const part = this.partById(partId);
            if (!part) return `Part #${partId}`;
            return `Part #${part.id} | ${this.partTypeLabel(part)} | ${part.layer_count} ảnh`;
        },
        partTypeLabel(part) {
            if (!part) return "Không rõ";
            if (part.type_name) {
                return `${part.type_name} (${part.type})`;
            }
            if (part.type === 0) return "Đầu (0)";
            if (part.type === 1) return "Thân (1)";
            if (part.type === 2) return "Chân (2)";
            return `TYPE ${part.type}`;
        },
        detailBlocks(item) {
            const blocks = [
                {
                    key: "part",
                    label: "Part chính",
                    part: item.part_preview?.part || this.partById(item.part),
                },
                {
                    key: "head",
                    label: "Head",
                    part: item.part_preview?.head || this.partById(item.head),
                },
                {
                    key: "body",
                    label: "Body",
                    part: item.part_preview?.body || this.partById(item.body),
                },
                {
                    key: "leg",
                    label: "Leg",
                    part: item.part_preview?.leg || this.partById(item.leg),
                },
            ];
            return blocks.filter((block) => block.part);
        },
        async loadPage(p) {
            this.loading = true;
            this.error = "";
            this.page = this.normalizePage(p);
            this.pageInput = String(this.page);
            this.expandedId = null;

            try {
                const params = new URLSearchParams({ page: String(this.page) });
                if (this.search) params.set("search", this.search);
                if (this.typeFilter) params.set("type", this.typeFilter);

                const res = await fetch(
                    `/admin/api/items?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await this.readJsonResponse(
                    res,
                    "Không thể lọc item",
                );
                this.items = data.data || [];
                this.partMap = data.part_map || {};
                this.types = data.types || [];
                this.typeOptions = data.type_options || [];
                this.totalPages = data.total_pages || 1;
                this.total = data.total || 0;
                this.page = this.normalizePage(data.page || this.page);
                this.pageInput = String(this.page);
            } catch (e) {
                this.error = e?.message || "Không thể lọc item";
                this.items = [];
                this.total = 0;
                this.totalPages = 1;
                this.page = 1;
                this.pageInput = "1";
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
.items-table th {
    white-space: nowrap;
}
.item-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--ds-gray-100);
}
.item-name {
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.item-meta {
    margin-top: 2px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.description-cell {
    max-width: 260px;
    color: var(--ds-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.badge-muted {
    background: rgba(148, 163, 184, 0.14);
    color: var(--ds-text-muted);
    border: 1px solid rgba(148, 163, 184, 0.22);
}
.part-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.1);
    border: 1px solid rgba(var(--ds-primary-rgb), 0.26);
    color: var(--ds-text);
    font-size: 12px;
    font-weight: 600;
}
.part-chip-main {
    background: rgba(var(--ds-success-rgb), 0.12);
    border-color: rgba(var(--ds-success-rgb), 0.24);
}
.muted {
    color: var(--ds-text-muted);
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.action-cell .btn + .btn {
    margin-left: 6px;
}
.detail-row td {
    background: rgba(var(--ds-primary-rgb), 0.03);
    padding: 0 !important;
}
.detail-panel {
    padding: 16px;
}
.detail-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.detail-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}
.detail-subtitle {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-top: 4px;
}
.detail-stats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.detail-stat {
    padding: 4px 8px;
    border-radius: 999px;
    background: var(--ds-gray-100);
    color: var(--ds-text-muted);
    font-size: 12px;
}
.part-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
}
.part-card {
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-body-bg);
    padding: 12px;
}
.part-card-head {
    margin-bottom: 10px;
}
.part-card-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}
.part-card-meta {
    margin-top: 3px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.layer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
    gap: 8px;
}
.layer-card {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-1);
    padding: 8px;
}
.layer-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.layer-info {
    min-width: 0;
}
.layer-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--ds-text);
}
.layer-meta {
    font-size: 11px;
    color: var(--ds-text-muted);
    margin-top: 2px;
}
.layer-empty {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
}
.pagination {
    flex-wrap: wrap;
    gap: 8px;
}
.pagination-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.pagination-jump {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
    flex-wrap: wrap;
}
.pagination-summary {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.pagination-input {
    width: 72px;
    min-width: 72px;
    padding: 6px 8px !important;
}
.editor-overlay {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(4, 8, 12, 0.66);
    backdrop-filter: blur(4px);
}
.editor-panel {
    width: min(780px, calc(100vw - 32px));
    max-height: min(780px, calc(100vh - 48px));
    overflow: auto;
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-lg, 0 24px 70px rgba(0, 0, 0, 0.45));
    padding: 18px;
}
.editor-head,
.editor-actions,
.editor-preview,
.editor-check {
    display: flex;
    align-items: center;
}
.editor-head {
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
}
.editor-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.editor-head p,
.editor-preview small,
.editor-note {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.icon-action {
    width: 36px;
    height: 36px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.editor-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.editor-preview {
    gap: 12px;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface-2);
    padding: 12px;
}
.editor-preview strong,
.editor-preview small {
    display: block;
}
.editor-icon {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    background: var(--ds-gray-100);
}
.editor-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.editor-grid label,
.editor-description {
    min-width: 0;
}
.form-label {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
}
.editor-description textarea {
    height: auto;
    min-height: 96px;
    padding-top: 10px !important;
    resize: vertical;
}
.editor-check {
    gap: 8px;
    color: var(--ds-text);
    font-size: 13px;
}
.editor-actions {
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px;
}
@media (max-width: 720px) {
    .editor-grid {
        grid-template-columns: 1fr;
    }
}
</style>
