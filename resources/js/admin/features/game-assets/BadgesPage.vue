<template>
    <div class="title-items-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Danh hiệu item_template</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">
                        Trang chủ
                    </router-link>
                    <span>/</span>
                    <span class="current">Danh hiệu</span>
                </nav>
            </div>
            <button class="btn btn-primary" @click="openEditor()">
                <span class="mi" style="font-size: 16px">add</span>
                Thêm danh hiệu
            </button>
        </div>

        <div class="filter-bar">
            <div class="search-input-wrap">
                <span class="mi search-icon">search</span>
                <input
                    v-model="search"
                    class="form-input search-input"
                    placeholder="Tìm ID, tên, icon hoặc effect..."
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
                            <th>Type</th>
                            <th>Part</th>
                            <th>Effect</th>
                            <th>Asset</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in rows" :key="item.id">
                            <td>#{{ item.id }}</td>
                            <td>
                                <img
                                    v-if="item.icon_url"
                                    :src="item.icon_url"
                                    loading="lazy"
                                    decoding="async"
                                    class="title-icon"
                                    :alt="item.name"
                                />
                                <AdminIcon
                                    v-else
                                    :icon-id="item.icon_id"
                                    class="title-icon"
                                />
                            </td>
                            <td>
                                <div class="item-name">{{ item.name }}</div>
                                <div class="item-meta">
                                    {{ item.description || "Danh hiệu" }}
                                </div>
                            </td>
                            <td>
                                {{ item.type }} · {{ genderLabel(item.gender) }}
                            </td>
                            <td>{{ item.part }}</td>
                            <td>
                                <span v-if="item.id_effect !== null">
                                    #{{ item.id_effect }}
                                </span>
                                <span v-else class="muted">Chưa map</span>
                            </td>
                            <td>
                                <div class="asset-tags">
                                    <span :class="{ ok: item.has_icon_x4 }">
                                        icon x4
                                    </span>
                                    <span :class="{ ok: item.has_effect_data }">
                                        effdata
                                    </span>
                                </div>
                            </td>
                            <td class="action-cell">
                                <button
                                    class="btn btn-primary btn-sm"
                                    @click="openEditor(item)"
                                >
                                    Sửa
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="8">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!rows.length && !loading">
                            <td colspan="8" class="empty-cell">
                                Chưa có danh hiệu phù hợp.
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
                    <div class="modal-title-block">
                        <h3>
                            {{
                                editor.form.id
                                    ? "Sửa danh hiệu"
                                    : "Thêm danh hiệu mới"
                            }}
                        </h3>
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
                                    >image</span
                                >
                            </div>
                            <div>
                                <strong>{{
                                    editor.form.name || "Danh hiệu mới"
                                }}</strong>
                                <small>
                                    Item #{{ editor.form.id || "mới" }} · icon
                                    {{ editor.form.icon_id || "auto" }} · part
                                    {{ editor.form.part || "auto" }}
                                </small>
                            </div>
                        </div>
                    </aside>

                    <div class="editor-main">
                        <div class="section-head compact">
                            <div>
                                <h4>Thông tin danh hiệu</h4>
                            </div>
                        </div>
                        <div class="form-grid">
                            <label>
                                <span class="form-label">Tên danh hiệu</span>
                                <input
                                    v-model.trim="editor.form.name"
                                    class="form-input"
                                    placeholder="Ví dụ: Chiến thần server"
                                />
                            </label>
                            <label>
                                <span class="form-label">Mô tả</span>
                                <input
                                    v-model.trim="editor.form.description"
                                    class="form-input"
                                />
                            </label>
                            <label>
                                <span class="form-label">Type item</span>
                                <input
                                    v-model.number="editor.form.type"
                                    type="number"
                                    class="form-input"
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
                        </div>

                        <div class="section-head compact">
                            <div>
                                <h4>Asset game</h4>
                                <span>
                                    Kéo thả file hoặc folder. Chỉ cần x4 cho
                                    icon/effect.
                                </span>
                            </div>
                        </div>
                        <div class="upload-grid asset-upload-grid">
                            <div
                                v-for="input in primaryIconInputs"
                                :key="input.field"
                                class="file-box"
                                @dragover.prevent="dragField = input.field"
                                @dragleave.prevent="dragField = ''"
                                @drop.prevent="dropFiles(input.field, $event)"
                            >
                                <input
                                    :id="`upload-${input.field}`"
                                    class="file-input-hidden"
                                    type="file"
                                    accept="image/png"
                                    multiple
                                    webkitdirectory
                                    directory
                                    @change="
                                        setFiles(
                                            input.field,
                                            $event.target.files,
                                        )
                                    "
                                />
                                <label
                                    class="drop-box"
                                    :class="{
                                        dragging: dragField === input.field,
                                    }"
                                    :for="`upload-${input.field}`"
                                >
                                    <span class="mi">upload_file</span>
                                    <strong>{{ input.label }}</strong>
                                    <small>{{
                                        fileSummary(input.field) ||
                                        "PNG hoặc folder icon"
                                    }}</small>
                                </label>
                            </div>
                            <div
                                v-for="input in primaryEffectInputs"
                                :key="input.field"
                                class="file-box"
                                @dragover.prevent="dragField = input.field"
                                @dragleave.prevent="dragField = ''"
                                @drop.prevent="dropFiles(input.field, $event)"
                            >
                                <input
                                    :id="`upload-${input.field}`"
                                    class="file-input-hidden"
                                    type="file"
                                    accept="image/png"
                                    multiple
                                    webkitdirectory
                                    directory
                                    @change="
                                        setFiles(
                                            input.field,
                                            $event.target.files,
                                        )
                                    "
                                />
                                <label
                                    class="drop-box"
                                    :class="{
                                        dragging: dragField === input.field,
                                    }"
                                    :for="`upload-${input.field}`"
                                >
                                    <span class="mi">animation</span>
                                    <strong>{{ input.label }}</strong>
                                    <small>{{
                                        fileSummary(input.field) ||
                                        "PNG hoặc folder effect"
                                    }}</small>
                                </label>
                            </div>
                            <div
                                class="file-box"
                                @dragover.prevent="
                                    dragField = 'effect_data_file'
                                "
                                @dragleave.prevent="dragField = ''"
                                @drop.prevent="dropEffectDataFiles"
                            >
                                <input
                                    id="upload-effect-data"
                                    class="file-input-hidden"
                                    type="file"
                                    multiple
                                    webkitdirectory
                                    directory
                                    @change="onEffectDataFile"
                                />
                                <label
                                    class="drop-box"
                                    :class="{
                                        dragging:
                                            dragField === 'effect_data_file',
                                    }"
                                    for="upload-effect-data"
                                >
                                    <span class="mi">dataset</span>
                                    <strong>Effect data</strong>
                                    <small>{{
                                        effectDataSummary ||
                                        "File/folder DataEffect"
                                    }}</small>
                                </label>
                            </div>
                        </div>

                        <label class="full-row">
                            <span class="form-label"> Effect (text) </span>
                            <textarea
                                v-model="editor.form.effect_data_text"
                                class="form-input"
                                rows="5"
                            ></textarea>
                        </label>
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
                        @click="saveTitleItem"
                    >
                        <span class="mi" style="font-size: 16px">save</span>
                        {{
                            editor.saving
                                ? "Đang lưu..."
                                : editor.form.id
                                  ? "Lưu thay đổi"
                                  : "Tạo danh hiệu"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { csrfToken } from "../../shared/format";
import { gameAssetGenderLabel } from "../../shared/gameAssets";
export default {
    data() {
        return {
            rows: [],
            search: "",
            page: 1,
            totalPages: 1,
            loading: false,
            error: "",
            success: "",
            timer: null,
            paths: {},
            next: {},
            previewIcon: "",
            dragField: "",
            iconInputs: [
                { field: "icon_x1", label: "Icon x1" },
                { field: "icon_x2", label: "Icon x2" },
                { field: "icon_x3", label: "Icon x3" },
                { field: "icon_x4", label: "Icon x4" },
            ],
            effectInputs: [
                { field: "effect_x1", label: "Effect x1" },
                { field: "effect_x2", label: "Effect x2" },
                { field: "effect_x3", label: "Effect x3" },
                { field: "effect_x4", label: "Effect x4" },
            ],
            editor: {
                open: false,
                saving: false,
                error: "",
                files: {},
                effectDataFiles: [],
                form: this.blankForm(),
            },
        };
    },
    created() {
        this.load(1);
    },
    computed: {
        primaryIconInputs() {
            return this.iconInputs.filter((input) => input.field === "icon_x4");
        },
        primaryEffectInputs() {
            return this.effectInputs.filter(
                (input) => input.field === "effect_x4",
            );
        },
        effectDataSummary() {
            const files = this.editor.effectDataFiles || [];
            if (!files.length) return "";
            if (files.length === 1) return files[0].name;
            return `${files.length} file`;
        },
    },
    unmounted() {
        window.clearTimeout(this.timer);
        this.revokePreview();
    },
    methods: {
        blankForm() {
            return {
                id: null,
                name: "",
                description: "Danh hiệu",
                type: 99,
                gender: 3,
                effect_data_text: "",
            };
        },
        draftKey() {
            return "admin_title_item_create_draft_v1";
        },
        loadDraftForm() {
            try {
                const raw = localStorage.getItem(this.draftKey());
                if (!raw) return this.blankForm();
                return { ...this.blankForm(), ...(JSON.parse(raw) || {}) };
            } catch {
                return this.blankForm();
            }
        },
        saveDraft() {
            if (!this.editor.open || this.editor.form?.id) return;
            try {
                localStorage.setItem(
                    this.draftKey(),
                    JSON.stringify(this.editor.form),
                );
                this.editor.error = "";
                this.success = "Đã lưu bộ danh hiệu đang nhập.";
            } catch {
                this.editor.error = "Không lưu được bộ đang nhập trên trình duyệt.";
            }
        },
        clearDraft() {
            try {
                localStorage.removeItem(this.draftKey());
            } catch {
                // ignore storage errors
            }
        },
        applyDraft() {
            if (this.editor.form?.id) return;
            this.editor.form = { ...this.loadDraftForm(), id: null };
            this.editor.error = "";
            this.success = "Đã nạp bộ danh hiệu đã lưu. File ảnh/data cần chọn lại.";
        },
        debouncedLoad() {
            window.clearTimeout(this.timer);
            this.timer = window.setTimeout(() => this.load(1), 300);
        },
        async load(page = 1) {
            this.loading = true;
            this.error = "";
            this.page = page;
            try {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: "30",
                });
                if (this.search) params.set("search", this.search);
                const res = await fetch(`/admin/api/title-items?${params}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được danh hiệu");
                }
                this.rows = data.data || [];
                this.paths = data.paths || {};
                this.next = data.next || {};
                this.totalPages = data.total_pages || 1;
            } catch (error) {
                this.error = error?.message || "Không tải được danh hiệu";
            } finally {
                this.loading = false;
            }
        },
        openEditor(item = null) {
            const isEdit = item && Number.isFinite(Number(item.id));
            this.revokePreview();
            this.editor = {
                open: true,
                saving: false,
                error: "",
                files: {},
                effectDataFiles: [],
                form: isEdit
                    ? {
                          id: item.id,
                          name: item.name || "",
                          description: item.description || "Danh hiệu",
                          type: Number(item.type || 99),
                          gender: Number(item.gender ?? 3),
                          effect_data_text: "",
                      }
                    : this.blankForm(),
            };
            this.previewIcon = isEdit ? item?.icon_url || "" : "";
        },
        closeEditor() {
            if (this.editor.saving) return;
            this.revokePreview();
            this.editor.open = false;
        },
        setFiles(field, fileList) {
            const files = Array.from(fileList || []);
            this.editor.files[field] = files;
            this.dragField = "";
            if (
                field === "icon_x4" ||
                (!this.previewIcon && field.startsWith("icon_"))
            ) {
                this.revokePreview();
                this.previewIcon = files[0]
                    ? URL.createObjectURL(files[0])
                    : "";
            }
        },
        fileSummary(field) {
            const files = this.editor.files[field] || [];
            if (!files.length) return "";
            if (files.length === 1) return files[0].name;
            return `${files.length} file`;
        },
        onEffectDataFile(event) {
            this.editor.effectDataFiles = Array.from(event.target.files || []);
            this.dragField = "";
        },
        dropFiles(field, event) {
            this.setFiles(field, event.dataTransfer?.files || []);
        },
        dropEffectDataFiles(event) {
            this.editor.effectDataFiles = Array.from(
                event.dataTransfer?.files || [],
            );
            this.dragField = "";
        },
        revokePreview() {
            if (this.previewIcon?.startsWith("blob:")) {
                URL.revokeObjectURL(this.previewIcon);
            }
        },
        genderLabel(gender) {
            return gameAssetGenderLabel(gender, "");
        },
        async saveTitleItem() {
            if (!this.editor.form.name) {
                this.editor.error = "Tên danh hiệu không được để trống.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            const formData = new FormData();
            Object.entries(this.editor.form).forEach(([key, value]) => {
                if (typeof value === "boolean") {
                    formData.set(key, value ? "1" : "0");
                } else if (
                    value !== "" &&
                    value !== null &&
                    value !== undefined
                ) {
                    formData.set(key, String(value));
                }
            });
            Object.entries(this.editor.files).forEach(([field, files]) => {
                (files || []).forEach((file) =>
                    formData.append(`${field}[]`, file),
                );
            });
            this.editor.effectDataFiles.forEach((file) =>
                formData.append("effect_data_file[]", file),
            );

            try {
                const isEdit = Boolean(this.editor.form.id);
                const url = isEdit
                    ? `/admin/api/title-items/${this.editor.form.id}`
                    : "/admin/api/title-items";
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrfToken(),
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không lưu được danh hiệu");
                }
                this.success = data.message || "Đã lưu danh hiệu";
                if (!isEdit) {
                    this.clearDraft();
                }
                this.closeEditor();
                await this.load(isEdit ? this.page : 1);
            } catch (error) {
                this.editor.error =
                    error?.message || "Không lưu được danh hiệu";
            } finally {
                this.editor.saving = false;
            }
        },
    },
};
</script>

<style scoped>
.title-items-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.page-top,
.filter-bar,
.modal-head,
.modal-actions,
.section-head {
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
.path-card {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    padding: 12px 14px;
}
.path-card strong,
.path-card small {
    display: block;
}
.path-card small,
.item-meta,
.muted,
.capability-note span,
.drop-box small,
.section-head span {
    color: var(--ds-text-muted);
    font-size: 12px;
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
    width: min(380px, calc(100vw - 48px));
    padding-left: 40px !important;
}
.title-icon,
.preview-image {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    object-fit: contain;
}
.item-name {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}
.asset-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.asset-tags span {
    border: 1px solid var(--ds-border);
    border-radius: 999px;
    padding: 3px 8px;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.asset-tags span.ok {
    border-color: rgba(34, 197, 94, 0.45);
    color: #22c55e;
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
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
    padding: 0;
    overflow: hidden;
}
.modal-head {
    align-items: flex-start;
    margin-bottom: 0;
    padding: 18px 20px;
    border-bottom: 1px solid var(--ds-border);
    background: linear-gradient(
        180deg,
        rgba(var(--ds-primary-rgb), 0.08),
        transparent
    );
}
.modal-title-block {
    min-width: 0;
}
.modal-head h3,
.section-head h4 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.modal-head p {
    margin-top: 4px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.modal-panel > .alert {
    margin: 16px 20px 0;
}
.editor-layout {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 0;
    align-items: stretch;
}
.editor-main {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px 20px;
    min-width: 0;
}
.editor-side {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px;
    border-right: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
}
.editor-card {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    padding: 14px;
}
.form-grid,
.upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.upload-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.asset-upload-grid {
    margin-top: 12px;
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
    font-family: inherit;
    line-height: 1;
    transition:
        background-color 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease,
        transform 0.16s ease,
        box-shadow 0.16s ease;
}
.icon-action .mi {
    font-size: 20px;
}
.icon-action:hover {
    background: var(--ds-gray-100);
    color: var(--ds-text-emphasis);
    transform: translateY(-1px);
}
.icon-action:active {
    transform: translateY(0);
}
.icon-action:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.18);
}
.icon-action.danger:hover {
    border-color: rgba(var(--ds-danger-rgb), 0.55);
    color: var(--ds-danger);
}
.full-row {
    grid-column: 1 / -1;
}
.create-item-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--ds-text);
}
.file-box {
    min-width: 0;
}
.asset-rule {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border: 1px solid rgba(45, 212, 191, 0.24);
    border-radius: 8px;
    background: rgba(45, 212, 191, 0.07);
    color: var(--ds-text);
    padding: 12px;
    font-size: 13px;
    line-height: 1.5;
}
.asset-rule .mi {
    color: var(--ds-primary);
    font-size: 18px;
}
.asset-rule strong {
    color: var(--ds-text-emphasis);
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
    background: rgba(45, 212, 191, 0.08);
    transform: translateY(-1px);
}
.drop-box .mi {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: rgba(45, 212, 191, 0.12);
    color: var(--ds-primary);
    font-size: 22px;
}
.drop-box strong {
    color: var(--ds-text-emphasis);
}
.drop-box small {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.drop-box-wide {
    min-height: 116px;
}
.preview-card,
.capability-note {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    padding: 14px;
}
.preview-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    text-align: center;
}
.preview-card small {
    display: block;
    margin-top: 4px;
    color: var(--ds-text-muted);
    font-size: 12px;
    line-height: 1.45;
}
.preview-icon-wrap {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
}
.preview-empty {
    color: var(--ds-text-muted);
}
.preview-card .preview-image {
    width: 64px;
    height: 64px;
}
.capability-note {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}
.modal-actions {
    margin-top: 0;
    padding: 14px 20px;
    border-top: 1px solid var(--ds-border);
    justify-content: flex-end;
    background: var(--ds-surface-2);
}
@media (max-width: 900px) {
    .editor-layout {
        grid-template-columns: 1fr;
    }
    .editor-side {
        border-right: 0;
        border-bottom: 1px solid var(--ds-border);
    }
    .form-grid,
    .upload-grid {
        grid-template-columns: 1fr;
    }
}
</style>



