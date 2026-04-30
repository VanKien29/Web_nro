<template>
    <div class="back-accessories-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Đeo lưng</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Đeo lưng</span>
                </nav>
            </div>
            <button class="btn btn-primary" @click="openEditor()">
                <span class="mi" style="font-size: 16px">add</span>
                Thêm đeo lưng
            </button>
        </div>

        <div class="filter-bar">
            <div class="search-input-wrap">
                <span class="mi search-icon">search</span>
                <input
                    v-model="search"
                    class="form-input search-input"
                    placeholder="Tìm item, tên, flag_bag, icon..."
                    @input="debouncedLoad"
                />
            </div>
            <button
                class="btn btn-outline"
                :disabled="loading"
                @click="load(1)"
            >
                <span class="mi" style="font-size: 16px">refresh</span>
                Tải lại
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Icon</th>
                            <th>Tên</th>
                            <th>FlagBag</th>
                            <th>Icon data</th>
                            <th>Gold/Gem</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in rows" :key="item.id">
                            <td>
                                <div v-if="item.item_id">
                                    #{{ item.item_id }}
                                </div>
                                <div v-else class="item-meta">
                                    Chưa gắn item
                                </div>
                            </td>
                            <td>
                                <img
                                    v-if="item.icon_url"
                                    :src="item.icon_url"
                                    loading="lazy"
                                    decoding="async"
                                    class="item-icon"
                                    :alt="item.name"
                                />
                                <div v-else class="item-icon item-icon-empty">
                                    {{ item.icon_id || "?" }}
                                </div>
                            </td>
                            <td>
                                <div class="item-name">{{ item.name }}</div>
                                <div class="item-meta">
                                    icon {{ item.icon_id }} · type
                                    {{ item.type }}
                                </div>
                            </td>
                            <td>
                                <div>#{{ item.flag_id ?? item.part }}</div>
                                <div class="item-meta">
                                    part {{ item.part }}
                                </div>
                            </td>
                            <td class="icon-data-cell">
                                <div
                                    v-if="item.icon_data_summary?.count"
                                    class="icon-data-list"
                                    :title="item.icon_data"
                                >
                                    <span
                                        v-for="id in item.icon_data_summary
                                            .visible"
                                        :key="`${item.id}-${id}`"
                                        class="icon-data-chip"
                                    >
                                        {{ id }}
                                    </span>
                                    <span
                                        v-if="item.icon_data_summary.hidden"
                                        class="icon-data-more"
                                    >
                                        +{{ item.icon_data_summary.hidden }}
                                    </span>
                                </div>
                                <span v-else class="item-meta">Không có</span>
                            </td>
                            <td>{{ item.gold ?? 0 }} / {{ item.gem ?? -1 }}</td>
                            <td class="action-cell">
                                <button
                                    class="btn btn-primary btn-sm"
                                    :disabled="deletingId === item.id"
                                    @click="openEditor(item)"
                                >
                                    Sửa
                                </button>
                                <button
                                    class="btn btn-danger btn-sm"
                                    :disabled="deletingId === item.id"
                                    @click="deleteBackAccessory(item)"
                                >
                                    {{
                                        deletingId === item.id
                                            ? "Đang xóa..."
                                            : "Xóa"
                                    }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="7">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!rows.length && !loading">
                            <td colspan="7" class="empty-cell">
                                Chưa có đeo lưng phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="load(page - 1)">
                    &laquo;
                </button>
                <button
                    v-for="p in totalPages"
                    :key="p"
                    :class="{ active: p === page }"
                    @click="load(p)"
                >
                    {{ p }}
                </button>
                <button :disabled="page >= totalPages" @click="load(page + 1)">
                    &raquo;
                </button>
            </div>
        </div>

        <div v-if="editor.open" class="modal-overlay" @click.self="closeEditor">
            <div class="modal-panel">
                <div class="modal-head">
                    <div>
                        <h3>
                            {{
                                editor.form.id
                                    ? "Sửa đeo lưng"
                                    : "Thêm đeo lưng"
                            }}
                        </h3>
                        <p>
                            Ảnh sprite chỉ cần x4, hệ thống tự tạo x3/x2/x1 và
                            đổi ID nếu trùng.
                        </p>
                    </div>
                    <button class="icon-action danger" @click="closeEditor">
                        <span class="mi">close</span>
                    </button>
                </div>

                <div v-if="editor.error" class="alert alert-error">
                    {{ editor.error }}
                </div>

                <div class="editor-layout">
                    <aside class="editor-side">
                        <div class="preview-card">
                            <div class="preview-icon-wrap">
                                <img
                                    v-if="previewIcon"
                                    :src="previewIcon"
                                    decoding="async"
                                    class="preview-image"
                                    alt="Icon preview"
                                />
                                <span v-else class="mi preview-empty"
                                    >backpack</span
                                >
                            </div>
                            <strong>{{
                                editor.form.name || "Đeo lưng mới"
                            }}</strong>
                        </div>
                    </aside>

                    <div class="editor-main">
                        <section class="editor-card">
                            <div class="form-grid">
                                <label>
                                    <span class="form-label"
                                        >Item ID muốn chèn</span
                                    >
                                    <input
                                        v-model.number="editor.form.item_id"
                                        class="form-input"
                                        type="number"
                                        min="1"
                                        placeholder="Trống = tự lấy ID mới"
                                    />
                                </label>
                                <label>
                                    <span class="form-label"
                                        >FlagBag ID muốn chèn</span
                                    >
                                    <input
                                        v-model.number="editor.form.flag_id"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="255"
                                        :disabled="!!editor.form.id"
                                        placeholder="Trống = tự lấy ID mới"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Tên đeo lưng</span>
                                    <input
                                        v-model.trim="editor.form.name"
                                        class="form-input"
                                        placeholder="Ví dụ: Cánh thiên sứ"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Mô tả</span>
                                    <input
                                        v-model.trim="editor.form.description"
                                        class="form-input"
                                        placeholder="Đeo lưng ..."
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Gender</span>
                                    <select
                                        v-model.number="editor.form.gender"
                                        class="form-input"
                                    >
                                        <option :value="0">Trái Đất</option>
                                        <option :value="1">Namek</option>
                                        <option :value="2">Xayda</option>
                                        <option :value="3">Chung/Tất cả</option>
                                    </select>
                                </label>
                                <label>
                                    <span class="form-label"
                                        >Gold flag_bag</span
                                    >
                                    <input
                                        v-model.number="editor.form.gold"
                                        class="form-input"
                                        type="number"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Gem flag_bag</span>
                                    <input
                                        v-model.number="editor.form.gem"
                                        class="form-input"
                                        type="number"
                                    />
                                </label>
                            </div>
                        </section>

                        <section class="editor-card">
                            <div class="upload-grid">
                                <div
                                    class="file-box"
                                    @dragover.prevent="dragField = 'icon_x4'"
                                    @dragleave.prevent="dragField = ''"
                                    @drop.prevent="dropFiles('icon_x4', $event)"
                                >
                                    <input
                                        id="back-accessory-icons"
                                        class="file-input-hidden"
                                        type="file"
                                        accept="image/png"
                                        multiple
                                        webkitdirectory
                                        directory
                                        @change="
                                            setFiles(
                                                'icon_x4',
                                                $event.target.files,
                                            )
                                        "
                                    />
                                    <label
                                        class="drop-box"
                                        :class="{
                                            dragging: dragField === 'icon_x4',
                                        }"
                                        for="back-accessory-icons"
                                    >
                                        <span class="mi">collections</span>
                                        <strong>Icon/sprite x4</strong>
                                        <small>{{
                                            fileSummary("icon_x4") ||
                                            "Kéo thả nhiều ảnh/folder"
                                        }}</small>
                                    </label>
                                </div>

                                <div
                                    class="file-box"
                                    @dragover.prevent="
                                        dragField = 'item_icon_x4'
                                    "
                                    @dragleave.prevent="dragField = ''"
                                    @drop.prevent="
                                        dropFiles('item_icon_x4', $event)
                                    "
                                >
                                    <input
                                        id="back-accessory-item-icon"
                                        class="file-input-hidden"
                                        type="file"
                                        accept="image/png"
                                        @change="
                                            setFiles(
                                                'item_icon_x4',
                                                $event.target.files,
                                            )
                                        "
                                    />
                                    <label
                                        class="drop-box"
                                        :class="{
                                            dragging:
                                                dragField === 'item_icon_x4',
                                        }"
                                        for="back-accessory-item-icon"
                                    >
                                        <span class="mi">inventory_2</span>
                                        <strong>Icon item</strong>
                                        <small>{{
                                            fileSummary("item_icon_x4") ||
                                            "Ảnh item_template.icon_id"
                                        }}</small>
                                    </label>
                                </div>
                            </div>
                        </section>

                        <section
                            v-if="editor.form.icon_data"
                            class="editor-card"
                        >
                            <label>
                                <span class="form-label"
                                    >Icon data hiện tại</span
                                >
                                <textarea
                                    v-model="editor.form.icon_data"
                                    class="form-input icon-data-input"
                                    rows="3"
                                    readonly
                                ></textarea>
                            </label>
                        </section>
                    </div>
                </div>

                <div class="modal-actions">
                    <button
                        v-if="!editor.form.id"
                        class="btn btn-outline"
                        :disabled="editor.saving"
                        @click="applyDraft"
                    >
                        Nạp bộ đã lưu
                    </button>
                    <button
                        v-if="!editor.form.id"
                        class="btn btn-outline"
                        :disabled="editor.saving"
                        @click="saveDraft"
                    >
                        Lưu bộ đang nhập
                    </button>
                    <button
                        class="btn btn-outline"
                        :disabled="editor.saving"
                        @click="closeEditor"
                    >
                        Hủy
                    </button>
                    <button
                        class="btn btn-primary"
                        :disabled="editor.saving"
                        @click="saveBackAccessory"
                    >
                        <span class="mi" style="font-size: 16px">save</span>
                        {{
                            editor.saving
                                ? "Đang lưu..."
                                : editor.form.id
                                  ? "Lưu thay đổi"
                                  : "Tạo đeo lưng"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { csrfToken } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";
import { filesToPayload } from "../../shared/files";
import { summarizeIconData } from "../../shared/gameAssets";
export default {
    data() {
        return {
            rows: [],
            search: "",
            page: 1,
            perPage: 30,
            totalPages: 1,
            loading: false,
            error: "",
            success: "",
            deletingId: null,
            searchTimer: null,
            dragField: "",
            previewIcon: "",
            next: {},
            editor: this.blankEditor(),
        };
    },
    created() {
        this.load();
    },
    beforeUnmount() {
        window.clearTimeout(this.searchTimer);
        this.revokePreview();
    },
    methods: {
        blankEditor() {
            return {
                open: false,
                saving: false,
                error: "",
                files: {
                    icon_x4: [],
                    item_icon_x4: [],
                },
                form: {
                    id: null,
                    item_id: "",
                    flag_id: "",
                    name: "",
                    description: "",
                    gender: 3,
                    gold: 0,
                    gem: -1,
                    icon_data: "",
                },
            };
        },
        draftKey() {
            return "admin_back_accessory_create_draft_v1";
        },
        async load(page = this.page) {
            this.loading = true;
            this.error = "";
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: this.perPage,
                    search: this.search,
                });
                const res = await fetch(
                    `/admin/api/back-accessories?${params}`,
                    {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
                const data = await readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được đeo lưng");
                }
                this.rows = (data.data || []).map((item) => ({
                    ...item,
                    icon_data_summary: this.formatIconData(item.icon_data),
                }));
                this.page = data.page || page;
                this.totalPages = data.total_pages || 1;
                this.next = data.next || {};
            } catch (error) {
                this.error = error?.message || "Không tải được đeo lưng";
            } finally {
                this.loading = false;
            }
        },
        debouncedLoad() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(() => this.load(1), 260);
        },
        async openEditor(item = null) {
            this.revokePreview();
            this.editor = this.blankEditor();
            this.editor.open = true;
            const isEdit = item && Number.isFinite(Number(item.id));
            if (!isEdit) {
                this.editor.form.item_id = this.next.item_id || "";
                this.editor.form.flag_id = this.next.flag_id ?? "";
                return;
            }

            this.previewIcon = item?.icon_url || "";
            this.editor.saving = true;
            try {
                const res = await fetch(
                    `/admin/api/back-accessories/${item.id}`,
                    {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
                const data = await readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được đeo lưng");
                }
                const detail = data.data || {};
                this.editor.form = {
                    ...this.editor.form,
                    id: detail.id,
                    item_id: detail.id,
                    flag_id: detail.flag_id,
                    name: detail.name || "",
                    description: detail.description || "",
                    gender: Number(detail.gender ?? 3),
                    gold: Number(detail.gold ?? 0),
                    gem: Number(detail.gem ?? -1),
                    icon_data: detail.icon_data || "",
                };
                this.previewIcon = detail.icon_url || item.icon_url || "";
            } catch (error) {
                this.editor.error = error?.message || "Không tải được đeo lưng";
            } finally {
                this.editor.saving = false;
            }
        },
        closeEditor() {
            if (this.editor.saving) return;
            this.revokePreview();
            this.editor.open = false;
        },
        setFiles(field, fileList) {
            const files = Array.from(fileList || []).filter((file) =>
                String(file.name || "")
                    .toLowerCase()
                    .endsWith(".png"),
            );
            this.editor.files[field] =
                field === "item_icon_x4" ? files.slice(0, 1) : files;
            this.dragField = "";
            if (
                field === "item_icon_x4" ||
                (!this.previewIcon && field === "icon_x4")
            ) {
                this.revokePreview();
                this.previewIcon = files[0]
                    ? URL.createObjectURL(files[0])
                    : "";
            }
        },
        dropFiles(field, event) {
            this.setFiles(field, event.dataTransfer?.files || []);
        },
        fileSummary(field) {
            const files = this.editor.files[field] || [];
            if (!files.length) return "";
            if (files.length === 1) return files[0].name;
            return `${files.length} file`;
        },
        formatIconData(value) {
            return summarizeIconData(value);
        },
        revokePreview() {
            if (this.previewIcon?.startsWith("blob:")) {
                URL.revokeObjectURL(this.previewIcon);
            }
        },
        numericIdFromFilename(name) {
            const base =
                String(name || "")
                    .replace(/\\/g, "/")
                    .split("/")
                    .pop() || "";
            const stem = base.replace(/\.[^.]+$/, "");
            const match = stem.match(/(\d+)(?!.*\d)/);
            if (!match) return null;
            const id = Number(match[1]);
            return id > 0 && id <= 32767 ? id : null;
        },
        sortedSpriteFiles(files) {
            return [...files].sort(
                (left, right) =>
                    (this.numericIdFromFilename(left.name) ??
                        Number.MAX_SAFE_INTEGER) -
                        (this.numericIdFromFilename(right.name) ??
                            Number.MAX_SAFE_INTEGER) ||
                    String(left.name).localeCompare(String(right.name)),
            );
        },
        saveDraft() {
            if (!this.editor.open || this.editor.form?.id) return;
            try {
                localStorage.setItem(
                    this.draftKey(),
                    JSON.stringify({ form: this.editor.form }),
                );
                this.editor.error = "";
                this.success = "Đã lưu bộ đeo lưng đang nhập.";
            } catch {
                this.editor.error =
                    "Không lưu được bộ đang nhập trên trình duyệt.";
            }
        },
        applyDraft() {
            if (this.editor.form?.id) return;
            try {
                const raw = localStorage.getItem(this.draftKey());
                if (!raw) {
                    this.editor.error = "Chưa có bộ đeo lưng đã lưu.";
                    return;
                }
                const draft = JSON.parse(raw);
                this.editor.form = {
                    ...this.editor.form,
                    ...(draft.form || {}),
                    id: null,
                };
                this.editor.error = "";
                this.success =
                    "Đã nạp bộ đeo lưng đã lưu. File ảnh cần chọn lại.";
            } catch {
                this.editor.error = "Không nạp được bộ đã lưu.";
            }
        },
        clearDraft() {
            try {
                localStorage.removeItem(this.draftKey());
            } catch {
                // ignore storage errors
            }
        },
        async saveBackAccessory() {
            if (!this.editor.form.name) {
                this.editor.error = "Tên đeo lưng không được để trống.";
                return;
            }
            if (
                !this.editor.form.id &&
                !(this.editor.files.icon_x4 || []).length
            ) {
                this.editor.error = "Cần upload ít nhất một ảnh sprite x4.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            const formData = new FormData();
            Object.entries(this.editor.form).forEach(([key, value]) => {
                formData.set(key, value ?? "");
            });

            try {
                const spriteFiles = this.sortedSpriteFiles(
                    this.editor.files.icon_x4 || [],
                );
                if (spriteFiles.length) {
                    formData.set(
                        "icon_x4_payload",
                        JSON.stringify(await filesToPayload(spriteFiles)),
                    );
                }
                (this.editor.files.item_icon_x4 || []).forEach((file) =>
                    formData.append("item_icon_x4[]", file),
                );

                const isEdit = Boolean(this.editor.form.id);
                const res = await fetch(
                    isEdit
                        ? `/admin/api/back-accessories/${this.editor.form.id}`
                        : "/admin/api/back-accessories",
                    {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": csrfToken(),
                        },
                        body: formData,
                    },
                );
                const data = await readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không lưu được đeo lưng");
                }
                this.success = data.message || "Đã lưu đeo lưng";
                if (!isEdit) this.clearDraft();
                this.closeEditor();
                await this.load(isEdit ? this.page : 1);
            } catch (error) {
                this.editor.error = error?.message || "Không lưu được đeo lưng";
            } finally {
                this.editor.saving = false;
            }
        },
        async deleteBackAccessory(item) {
            const ok = window.confirm(
                `Xóa đeo lưng "${item.name}"?\n\nHệ thống sẽ xóa item_template, flag_bag và ảnh icon không còn được dùng.`,
            );
            if (!ok) return;

            this.deletingId = item.id;
            this.error = "";
            this.success = "";
            try {
                const res = await fetch(
                    `/admin/api/back-accessories/${item.id}`,
                    {
                        method: "DELETE",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": csrfToken(),
                        },
                    },
                );
                const data = await readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không xóa được đeo lưng");
                }
                const skipped = data.data?.skipped_icon_ids || [];
                this.success = skipped.length
                    ? `${data.message || "Đã xóa đeo lưng"}. Giữ lại ${skipped.length} icon còn được dữ liệu khác dùng: ${skipped.join(", ")}`
                    : data.message || "Đã xóa đeo lưng";
                await this.load(this.page);
            } catch (error) {
                this.error = error?.message || "Không xóa được đeo lưng";
            } finally {
                this.deletingId = null;
            }
        },

    },
};
</script>

<style scoped>
.back-accessories-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.page-top,
.filter-bar,
.modal-head,
.modal-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.page-title {
    margin: 0 0 4px;
    color: var(--ds-text-emphasis);
    font-size: 22px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--ds-text-muted);
    font-size: 13px;
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
}
.search-input {
    width: min(420px, calc(100vw - 48px));
    padding-left: 40px !important;
}
.item-icon,
.preview-image {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    object-fit: contain;
}
.item-icon-empty {
    display: grid;
    place-items: center;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.item-name {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}
.item-meta,
.drop-box small,
.preview-card small {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.icon-data-cell {
    width: 42%;
    min-width: 280px;
    max-width: 460px;
}
.icon-data-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    max-width: 100%;
    overflow: hidden;
}
.icon-data-chip,
.icon-data-more {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    max-width: 92px;
    padding: 2px 8px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-text);
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.icon-data-more {
    border-color: var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background: rgba(4, 8, 12, 0.66);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow: auto;
    padding: 18px;
}
.modal-panel {
    width: min(980px, calc(100vw - 36px));
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
    overflow: hidden;
}
.modal-head {
    align-items: flex-start;
    padding: 18px 20px;
    border-bottom: 1px solid var(--ds-border);
    background: linear-gradient(
        180deg,
        rgba(var(--ds-primary-rgb), 0.08),
        transparent
    );
}
.modal-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.modal-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.modal-panel > .alert {
    margin: 16px 20px 0;
}
.editor-layout {
    display: grid;
    grid-template-columns: 250px minmax(0, 1fr);
}
.editor-side {
    padding: 18px;
    border-right: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
}
.editor-main {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
    padding: 18px 20px;
}
.editor-card,
.preview-card {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    padding: 14px;
}
.preview-card {
    text-align: center;
}
.preview-icon-wrap {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    margin: 0 auto 10px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
}
.preview-card .preview-image {
    width: 64px;
    height: 64px;
}
.preview-empty {
    color: var(--ds-text-muted);
}
.form-grid,
.upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.file-input-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.drop-box {
    min-height: 118px;
    border: 1px dashed var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    text-align: center;
    cursor: pointer;
    transition:
        border-color 0.16s ease,
        background 0.16s ease,
        transform 0.16s ease;
}
.drop-box:hover,
.drop-box.dragging {
    border-color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.08);
    transform: translateY(-1px);
}
.drop-box .mi {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-primary);
}
.drop-box strong {
    color: var(--ds-text-emphasis);
}
.icon-data-input {
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    font-size: 12px;
}
.modal-actions {
    padding: 14px 20px;
    border-top: 1px solid var(--ds-border);
    justify-content: flex-end;
    background: var(--ds-surface-2);
}
.icon-action {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    display: inline-grid;
    place-items: center;
    cursor: pointer;
    transition:
        background-color 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease,
        transform 0.16s ease;
}
.icon-action:hover {
    background: var(--ds-gray-100);
    color: var(--ds-text-emphasis);
    transform: translateY(-1px);
}
.icon-action.danger:hover {
    border-color: rgba(var(--ds-danger-rgb), 0.55);
    color: var(--ds-danger);
}
@media (max-width: 900px) {
    .editor-layout,
    .form-grid,
    .upload-grid {
        grid-template-columns: 1fr;
    }
    .editor-side {
        border-right: 0;
        border-bottom: 1px solid var(--ds-border);
    }
}
</style>



