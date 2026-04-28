<template>
    <div class="costumes-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Cải trang</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">
                        Trang chủ
                    </router-link>
                    <span>/</span>
                    <span class="current">Cải trang</span>
                </nav>
            </div>
            <button class="btn btn-primary" @click="openEditor()">
                <span class="mi" style="font-size: 16px">add</span>
                Thêm cải trang
            </button>
        </div>

        <div class="filter-bar">
            <div class="search-input-wrap">
                <span class="mi search-icon">search</span>
                <input
                    v-model="search"
                    class="form-input search-input"
                    placeholder="Tìm ID, tên, icon, head/body/leg..."
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
                            <th>Gender</th>
                            <th>Head</th>
                            <th>Body</th>
                            <th>Leg</th>
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
                                    class="costume-icon"
                                    :alt="item.name"
                                />
                                <AdminIcon
                                    v-else
                                    :icon-id="item.icon_id"
                                    class="costume-icon"
                                />
                            </td>
                            <td>
                                <div class="item-name">{{ item.name }}</div>
                                <div class="item-meta">
                                    icon {{ item.icon_id }} · part
                                    {{ item.part }}
                                </div>
                            </td>
                            <td>{{ genderLabel(item.gender) }}</td>
                            <td>#{{ item.head }}</td>
                            <td>#{{ item.body }}</td>
                            <td>#{{ item.leg }}</td>
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
                                    @click="deleteCostume(item)"
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
                            <td colspan="8">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!rows.length && !loading">
                            <td colspan="8" class="empty-cell">
                                Chưa có cải trang phù hợp.
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
                                    ? "Sửa cải trang"
                                    : "Thêm cải trang"
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
                                    class="preview-image"
                                    alt="Avatar preview"
                                />
                                <span v-else class="mi preview-empty"
                                    >face</span
                                >
                            </div>
                            <strong>{{
                                editor.form.name || "Cải trang mới"
                            }}</strong>
                        </div>

                        <label class="small-toggle">
                            <input
                                v-model="editor.hasExtraHeads"
                                type="checkbox"
                            />
                            <span>Thêm head phụ</span>
                        </label>
                    </aside>

                    <div class="editor-main">
                        <section class="editor-card">
                            <div class="form-grid">
                                <label>
                                    <span class="form-label">
                                        Item ID muốn chèn
                                    </span>
                                    <input
                                        v-model.number="editor.form.item_id"
                                        class="form-input"
                                        type="number"
                                        min="1"
                                        :disabled="!!editor.form.id"
                                        placeholder="Trống = tự lấy ID mới"
                                    />
                                </label>
                                <label>
                                    <span class="form-label"
                                        >Tên cải trang</span
                                    >
                                    <input
                                        v-model.trim="editor.form.name"
                                        class="form-input"
                                        placeholder="Ví dụ: Gogeta Hắc Hóa"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Mô tả</span>
                                    <input
                                        v-model.trim="editor.form.description"
                                        class="form-input"
                                        placeholder="Cải trang ..."
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
                                        id="costume-icons"
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
                                        for="costume-icons"
                                    >
                                        <span class="mi">image</span>
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
                                        id="costume-item-icon"
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
                                        for="costume-item-icon"
                                    >
                                        <span class="mi">inventory_2</span>
                                        <strong>Icon item</strong>
                                        <small>{{
                                            fileSummary("item_icon_x4") ||
                                            "Ảnh item_template.icon_id"
                                        }}</small>
                                    </label>
                                </div>

                                <div
                                    class="file-box"
                                    @dragover.prevent="dragField = 'avatar_x4'"
                                    @dragleave.prevent="dragField = ''"
                                    @drop.prevent="
                                        dropFiles('avatar_x4', $event)
                                    "
                                >
                                    <input
                                        id="costume-avatar"
                                        class="file-input-hidden"
                                        type="file"
                                        accept="image/png"
                                        @change="
                                            setFiles(
                                                'avatar_x4',
                                                $event.target.files,
                                            )
                                        "
                                    />
                                    <label
                                        class="drop-box"
                                        :class="{
                                            dragging: dragField === 'avatar_x4',
                                        }"
                                        for="costume-avatar"
                                    >
                                        <span class="mi">face</span>
                                        <strong>Avatar head</strong>
                                        <small>{{
                                            fileSummary("avatar_x4") ||
                                            "Ảnh head_avatar/avatar_id"
                                        }}</small>
                                    </label>
                                </div>
                            </div>
                        </section>

                        <section class="editor-card">
                            <div class="part-grid">
                                <label>
                                    <span class="form-label">Head DATA</span>
                                    <textarea
                                        v-model="editor.form.head_data"
                                        class="form-input part-input"
                                        rows="5"
                                    ></textarea>
                                </label>
                                <label>
                                    <span class="form-label">Body DATA</span>
                                    <textarea
                                        v-model="editor.form.body_data"
                                        class="form-input part-input"
                                        rows="5"
                                    ></textarea>
                                </label>
                                <label>
                                    <span class="form-label">Leg DATA</span>
                                    <textarea
                                        v-model="editor.form.leg_data"
                                        class="form-input part-input"
                                        rows="5"
                                    ></textarea>
                                </label>
                                <label
                                    v-if="editor.hasExtraHeads"
                                    class="full-row"
                                >
                                    <span class="form-label">
                                        Head phụ DATA
                                    </span>
                                    <textarea
                                        v-model="editor.form.extra_head_data"
                                        class="form-input part-input"
                                        rows="5"
                                        placeholder="Mỗi head phụ cách nhau bằng một dòng trống"
                                    ></textarea>
                                </label>
                            </div>
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
                        @click="saveCostume"
                    >
                        <span class="mi" style="font-size: 16px">save</span>
                        {{
                            editor.saving
                                ? "Đang lưu..."
                                : editor.form.id
                                  ? "Lưu thay đổi"
                                  : "Tạo cải trang"
                        }}
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
                hasExtraHeads: false,
                files: {
                    icon_x4: [],
                    item_icon_x4: [],
                    avatar_x4: [],
                },
                form: {
                    id: null,
                    item_id: "",
                    name: "",
                    description: "",
                    gender: 3,
                    head_data: "",
                    body_data: "",
                    leg_data: "",
                    extra_head_data: "",
                },
            };
        },
        draftKey() {
            return "admin_costume_create_draft_v1";
        },
        loadDraftEditor() {
            try {
                const raw = localStorage.getItem(this.draftKey());
                if (!raw) return this.blankEditor();
                const draft = JSON.parse(raw);
                const editor = this.blankEditor();
                editor.hasExtraHeads = !!draft.hasExtraHeads;
                editor.form = { ...editor.form, ...(draft.form || {}) };
                return editor;
            } catch {
                return this.blankEditor();
            }
        },
        saveDraft() {
            if (!this.editor.open || this.editor.form?.id) return;
            try {
                localStorage.setItem(
                    this.draftKey(),
                    JSON.stringify({
                        hasExtraHeads: this.editor.hasExtraHeads,
                        form: this.editor.form,
                    }),
                );
                this.editor.error = "";
                this.success = "Đã lưu bộ cải trang đang nhập.";
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
            const draft = this.loadDraftEditor();
            this.editor.hasExtraHeads = draft.hasExtraHeads;
            this.editor.form = { ...this.editor.form, ...draft.form, id: null };
            this.editor.error = "";
            this.success = "Đã nạp bộ cải trang đã lưu. File ảnh cần chọn lại.";
        },
        token() {
            return document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
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
                const res = await fetch(`/admin/api/costumes?${params}`, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                const data = await this.readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được cải trang");
                }
                this.rows = data.data || [];
                this.page = data.page || page;
                this.totalPages = data.total_pages || 1;
            } catch (error) {
                this.error = error?.message || "Không tải được cải trang";
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
            this.previewIcon = isEdit ? item?.icon_url || "" : "";
            if (!isEdit) return;

            this.editor.saving = true;
            try {
                const res = await fetch(`/admin/api/costumes/${item.id}`, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                const data = await this.readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được cải trang");
                }
                const detail = data.data || {};
                this.editor.form = {
                    ...this.editor.form,
                    id: detail.id,
                    item_id: detail.id,
                    name: detail.name || "",
                    description: detail.description || "",
                    gender: Number(detail.gender ?? 3),
                    head_data: detail.head_data || "",
                    body_data: detail.body_data || "",
                    leg_data: detail.leg_data || "",
                    extra_head_data: detail.extra_head_data || "",
                };
                this.editor.hasExtraHeads = Boolean(detail.extra_head_data);
                this.previewIcon = detail.icon_url || item.icon_url || "";
            } catch (error) {
                this.editor.error = error?.message || "Không tải được cải trang";
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
            const files = Array.from(fileList || []);
            this.editor.files[field] =
                field === "avatar_x4" || field === "item_icon_x4"
                    ? files.slice(0, 1)
                    : files;
            this.dragField = "";
            if (
                field === "item_icon_x4" ||
                field === "avatar_x4" ||
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
        revokePreview() {
            if (this.previewIcon?.startsWith("blob:")) {
                URL.revokeObjectURL(this.previewIcon);
            }
        },
        genderLabel(gender) {
            return (
                {
                    0: "Trái Đất",
                    1: "Namek",
                    2: "Xayda",
                    3: "Chung",
                }[Number(gender)] || `Gender ${gender}`
            );
        },
        numericIdFromFilename(name) {
            const base = String(name || "").replace(/\\/g, "/").split("/").pop() || "";
            const stem = base.replace(/\.[^.]+$/, "");
            const match = stem.match(/(\d+)(?!.*\d)/);
            if (!match) return null;
            const id = Number(match[1]);
            return id > 0 && id <= 32767 ? id : null;
        },
        referencedPartIconIds() {
            const raw = [
                this.editor.form.head_data,
                this.editor.form.body_data,
                this.editor.form.leg_data,
                this.editor.form.extra_head_data,
            ].join("\n");
            const ids = new Set();
            const pattern = /\[\s*(-?\d+)\s*,\s*-?\d+\s*,\s*-?\d+\s*\]/g;
            let match;
            while ((match = pattern.exec(raw))) {
                const id = Number(match[1]);
                if (id > 0 && id <= 32767) ids.add(id);
            }
            return ids;
        },
        filteredSpriteFiles(files) {
            const referenced = this.referencedPartIconIds();
            const byNumericName = (left, right) =>
                (this.numericIdFromFilename(left.name) ?? Number.MAX_SAFE_INTEGER) -
                    (this.numericIdFromFilename(right.name) ?? Number.MAX_SAFE_INTEGER) ||
                String(left.name).localeCompare(String(right.name));
            if (!referenced.size) return [...files].sort(byNumericName);
            const filtered = files.filter((file) => {
                const id = this.numericIdFromFilename(file.name);
                return id !== null && referenced.has(id);
            });
            return (filtered.length ? filtered : files).slice().sort(byNumericName);
        },
        async saveCostume() {
            if (!this.editor.form.name) {
                this.editor.error = "Tên cải trang không được để trống.";
                return;
            }
            if (
                !this.editor.form.head_data ||
                !this.editor.form.body_data ||
                !this.editor.form.leg_data
            ) {
                this.editor.error = "Cần nhập đủ Head/Body/Leg DATA.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            const formData = new FormData();
            Object.entries(this.editor.form).forEach(([key, value]) => {
                formData.set(
                    key,
                    typeof value === "boolean" ? (value ? "1" : "0") : (value ?? ""),
                );
            });

            try {
                const spriteFiles = this.editor.files.icon_x4 || [];
                const filteredSprites = this.filteredSpriteFiles(spriteFiles);
                if (filteredSprites.length) {
                    formData.set(
                        "icon_x4_payload",
                        JSON.stringify(await this.filesToPayload(filteredSprites)),
                    );
                }

                ["item_icon_x4", "avatar_x4"].forEach((field) => {
                    (this.editor.files[field] || []).forEach((file) =>
                        formData.append(`${field}[]`, file),
                    );
                });

                const isEdit = Boolean(this.editor.form.id);
                const res = await fetch(
                    isEdit
                        ? `/admin/api/costumes/${this.editor.form.id}`
                        : "/admin/api/costumes",
                    {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.token(),
                    },
                    body: formData,
                    },
                );
                const data = await this.readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không lưu được cải trang");
                }
                this.success = data.message || "Đã lưu cải trang";
                if (!isEdit) {
                    this.clearDraft();
                }
                this.closeEditor();
                await this.load(isEdit ? this.page : 1);
            } catch (error) {
                this.editor.error =
                    error?.message || "Không lưu được cải trang";
            } finally {
                this.editor.saving = false;
            }
        },
        async deleteCostume(item) {
            const ok = window.confirm(
                `Xóa cải trang "${item.name}"?\n\nHệ thống sẽ xóa item_template, part head/body/leg, head_avatar và dồn lại ID part.`,
            );
            if (!ok) return;

            this.deletingId = item.id;
            this.error = "";
            this.success = "";
            try {
                const res = await fetch(`/admin/api/costumes/${item.id}`, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.token(),
                    },
                });
                const data = await this.readJsonResponse(res);
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không xóa được cải trang");
                }
                this.success = data.message || "Đã xóa cải trang";
                await this.load(this.page);
            } catch (error) {
                this.error = error?.message || "Không xóa được cải trang";
            } finally {
                this.deletingId = null;
            }
        },
        filesToPayload(files) {
            return Promise.all(
                files.map(
                    (file) =>
                        new Promise((resolve, reject) => {
                            const reader = new FileReader();
                            reader.onload = () => {
                                const dataUrl = String(reader.result || "");
                                const comma = dataUrl.indexOf(",");
                                resolve({
                                    name: file.name,
                                    data:
                                        comma >= 0
                                            ? dataUrl.slice(comma + 1)
                                            : dataUrl,
                                });
                            };
                            reader.onerror = () =>
                                reject(
                                    new Error(
                                        `Không đọc được file ${file.name}`,
                                    ),
                                );
                            reader.readAsDataURL(file);
                        }),
                ),
            );
        },
        async readJsonResponse(res) {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch {
                const plain = text
                    .replace(/<br\s*\/?>/gi, "\n")
                    .replace(/<[^>]*>/g, " ")
                    .replace(/\s+/g, " ")
                    .trim();
                throw new Error(
                    plain ||
                        `API trả về dữ liệu không phải JSON (${res.status})`,
                );
            }
        },
    },
};
</script>

<style scoped>
.costumes-page {
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
.info-strip {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    border-radius: 8px;
    background: rgba(var(--ds-primary-rgb), 0.07);
    color: var(--ds-text);
    padding: 12px 14px;
    font-size: 13px;
    line-height: 1.5;
}
.info-strip .mi {
    color: var(--ds-primary);
    font-size: 18px;
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
.costume-icon,
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
.item-meta,
.section-head span,
.drop-box small,
.preview-card small {
    color: var(--ds-text-muted);
    font-size: 12px;
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
    width: min(1080px, calc(100vw - 36px));
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
.modal-head h3,
.section-head h4 {
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
    display: flex;
    flex-direction: column;
    gap: 12px;
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
.small-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--ds-text);
}
.form-grid,
.upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.part-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.full-row {
    grid-column: 1 / -1;
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
.part-input {
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    font-size: 12px;
    line-height: 1.45;
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
@media (max-width: 980px) {
    .editor-layout,
    .form-grid,
    .upload-grid,
    .part-grid {
        grid-template-columns: 1fr;
    }
    .editor-side {
        border-right: 0;
        border-bottom: 1px solid var(--ds-border);
    }
}
</style>
