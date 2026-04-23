<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? "Sửa tài khoản" : "Tạo tài khoản mới" }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <router-link :to="{ name: 'admin.accounts' }"
                        >Tài khoản</router-link
                    >
                    <span>/</span>
                    <span class="current">{{
                        isEdit ? form.username || "..." : "Tạo mới"
                    }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.accounts' }"
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
                <!-- LEFT: Main -->
                <div class="form-main">
                    <div class="card">
                        <div class="card-header">
                            <h3>Thông tin đăng nhập</h3>
                        </div>
                        <div class="form-group">
                            <label class="form-label"
                                >Username <span class="required">*</span></label
                            >
                            <input
                                v-model="form.username"
                                class="form-input"
                                required
                                :disabled="isEdit"
                                :style="
                                    isEdit
                                        ? 'opacity:.6;cursor:not-allowed'
                                        : ''
                                "
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Password
                                <small
                                    v-if="isEdit"
                                    style="color: var(--ds-text-muted)"
                                    >(để trống nếu không đổi)</small
                                >
                            </label>
                            <input
                                v-model="form.password"
                                class="form-input"
                                type="text"
                                :required="!isEdit"
                                placeholder="Nhập mật khẩu..."
                            />
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h3>Tài nguyên</h3></div>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label">Cash</label>
                                <input
                                    v-model.number="form.cash"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Danap</label>
                                <input
                                    v-model.number="form.danap"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Coin</label>
                                <input
                                    v-model.number="form.coin"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">Điểm đã nhận</label>
                                <input
                                    v-model.number="form.diem_da_nhan"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Điểm danh</label>
                                <input
                                    v-model.number="form.diem_danh"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="card" v-if="isEdit">
                        <div class="card-header">
                            <h3>Dữ liệu nhân vật (player)</h3>
                        </div>

                        <div v-if="!playerInfo" class="muted-line">
                            Tài khoản này chưa có nhân vật.
                        </div>

                        <div v-else>
                            <div class="player-summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">ID</span>
                                    <span class="summary-value">{{
                                        playerInfo.id
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Tên</span>
                                    <span class="summary-value">{{
                                        playerInfo.name || "—"
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Giới tính</span>
                                    <span class="summary-value">{{
                                        genderLabel(playerInfo.gender)
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Sức mạnh</span>
                                    <span class="summary-value">{{
                                        fmt(playerInfo.power)
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"
                                        >Nhiệm vụ hiện tại</span
                                    >
                                    <span class="summary-value">{{
                                        taskPreviewText(playerInfo.task)
                                    }}</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-outline btn-sm"
                                @click="togglePlayerFull"
                                :disabled="playerFullLoading"
                            >
                                <span class="mi" style="font-size: 14px">{{
                                    showPlayerFull ? "visibility_off" : "list"
                                }}</span>
                                {{
                                    showPlayerFull
                                        ? "Ẩn dữ liệu player"
                                        : "Hiển thị dữ liệu player"
                                }}
                            </button>
                        </div>

                        <div v-if="showPlayerFull" class="player-full-wrap">
                            <div v-if="playerFullLoading" class="muted-line">
                                Đang tải dữ liệu player...
                            </div>
                            <div
                                v-else-if="playerFullError"
                                class="alert alert-error"
                            >
                                {{ playerFullError }}
                            </div>
                            <div
                                v-else-if="playerFull === null"
                                class="muted-line"
                            >
                                Tài khoản chưa có nhân vật.
                            </div>
                            <template v-else-if="playerFull && playerFull.raw">
                                <div class="task-banner">
                                    <strong>Nhiệm vụ chính:</strong>
                                    {{
                                        taskPreviewText(playerFull.summary.task)
                                    }}
                                </div>

                                <div
                                    v-if="parsedSections.length"
                                    class="parsed-wrap"
                                >
                                    <div
                                        class="parsed-box"
                                        v-for="section in parsedSections"
                                        :key="section.key"
                                    >
                                        <div class="parsed-title">
                                            {{ section.title }}
                                        </div>
                                        <div
                                            class="parsed-row"
                                            v-for="item in section.items"
                                            :key="`${section.key}-${item.index}`"
                                        >
                                            <span class="parsed-label"
                                                >[{{ item.index }}]
                                                {{ item.label }}</span
                                            >
                                            <span class="parsed-value">{{
                                                formatParsedValue(item.value)
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="player-rows">
                                    <div
                                        class="player-row"
                                        v-for="field in playerFields"
                                        :key="field.name"
                                    >
                                        <div class="player-row-key">
                                            <div class="player-row-title">
                                                {{ field.label || field.name }}
                                            </div>
                                            <div class="player-row-name">
                                                {{ field.name }}
                                            </div>
                                        </div>
                                        <div class="player-row-value">
                                            <pre>{{
                                                getFieldDisplay(
                                                    field,
                                                    !isFieldExpanded(
                                                        field.name,
                                                    ),
                                                )
                                            }}</pre>
                                            <button
                                                v-if="
                                                    shouldCollapseField(field)
                                                "
                                                type="button"
                                                class="btn btn-outline btn-xs"
                                                @click="
                                                    toggleFieldExpanded(
                                                        field.name,
                                                    )
                                                "
                                            >
                                                {{
                                                    isFieldExpanded(field.name)
                                                        ? "Thu gọn"
                                                        : "Mở rộng"
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Sidebar -->
                <div class="form-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h3>Quyền & Trạng thái</h3>
                        </div>
                        <div class="toggle-field">
                            <label class="toggle-row-label">Admin</label>
                            <label class="toggle">
                                <input
                                    type="checkbox"
                                    v-model="form.is_admin"
                                    true-value="1"
                                    false-value="0"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-field">
                            <label class="toggle-row-label">Active</label>
                            <label class="toggle">
                                <input
                                    type="checkbox"
                                    v-model="form.active"
                                    true-value="1"
                                    false-value="0"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-field">
                            <label
                                class="toggle-row-label"
                                style="color: var(--ds-danger)"
                                >Ban</label
                            >
                            <label class="toggle toggle-danger">
                                <input
                                    type="checkbox"
                                    v-model="form.ban"
                                    true-value="1"
                                    false-value="0"
                                />
                                <span class="toggle-slider"></span>
                            </label>
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
                                      : "Tạo tài khoản"
                            }}
                        </button>
                    </div>

                    <div class="card danger-card" v-if="isEdit">
                        <div class="card-header">
                            <h3 style="color: var(--ds-danger)">
                                Vùng nguy hiểm
                            </h3>
                        </div>
                        <p class="danger-text">
                            Xoá tài khoản sẽ không thể hoàn tác.
                        </p>
                        <button
                            type="button"
                            class="btn btn-danger btn-block btn-sm"
                            @click="del"
                        >
                            <span class="mi" style="font-size: 14px"
                                >delete</span
                            >
                            Xoá tài khoản
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
                username: "",
                password: "",
                cash: 0,
                danap: 0,
                coin: 0,
                diem_da_nhan: 0,
                diem_danh: 0,
                is_admin: "0",
                active: "1",
                ban: "0",
            },
            error: "",
            success: "",
            saving: false,
            playerInfo: null,
            playerFull: null,
            playerFullLoading: false,
            playerFullError: "",
            showPlayerFull: false,
            expandedFields: {},
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
        },
        playerFields() {
            const order = [
                "data_point",
                "data_inventory",
                "data_location",
                "data_task",
                "items_body",
                "items_bag",
                "items_box",
                "item_mails_box",
                "items_daban",
                "data_item_time",
                "pet",
                "giftcode",
                "event_point_boss",
            ];
            const fields = this.playerFull?.fields || [];
            const fieldMap = new Map(
                fields.map((field) => [field.name, field]),
            );
            return order
                .filter((name) => fieldMap.has(name))
                .map((name) => fieldMap.get(name));
        },
        parsedSections() {
            const parsed = this.playerFull?.parsed || {};
            const titleMap = {
                data_inventory: "Túi đồ",
                data_location: "Vị trí",
                data_point: "Chỉ số",
                data_task: "Nhiệm vụ",
            };
            return [
                "data_inventory",
                "data_location",
                "data_point",
                "data_task",
            ]
                .filter((k) => parsed[k] && Array.isArray(parsed[k].items))
                .map((k) => ({
                    key: k,
                    title: titleMap[k] || k,
                    items: parsed[k].items,
                }));
        },
    },
    created() {
        if (this.isEdit) {
            this.loadAccount();
        }
    },
    methods: {
        fmt(n) {
            return Number(n || 0).toLocaleString("vi-VN");
        },
        genderLabel(gender) {
            if (gender === 0 || gender === "0") return "Trái Đất";
            if (gender === 1 || gender === "1") return "Namek";
            if (gender === 2 || gender === "2") return "Xayda";
            return "Không rõ";
        },
        taskPreviewText(task) {
            if (!task || task.id === null || task.id === undefined) {
                return "Chưa có nhiệm vụ";
            }
            const taskName = task.name ? ` - ${task.name}` : "";
            const taskIndex =
                task.index !== null && task.index !== undefined
                    ? ` | bước: ${task.index}`
                    : "";
            const taskCount =
                task.count !== null && task.count !== undefined
                    ? ` | tiến độ: ${task.count}`
                    : "";
            return `#${task.id}${taskName}${taskIndex}${taskCount}`;
        },
        normalizeValue(v) {
            if (v === null || v === undefined) return "(trống)";
            if (typeof v === "string") return v;
            try {
                return JSON.stringify(v);
            } catch {
                return String(v);
            }
        },
        shouldCollapseField(field) {
            const value = this.normalizeValue(
                this.playerFull?.raw?.[field.name],
            );
            return !!field.is_long || value.length > 260;
        },
        isFieldExpanded(name) {
            return !!this.expandedFields[name];
        },
        toggleFieldExpanded(name) {
            this.expandedFields = {
                ...this.expandedFields,
                [name]: !this.expandedFields[name],
            };
        },
        getFieldDisplay(field, shortMode = true) {
            const text = this.normalizeValue(
                this.playerFull?.raw?.[field.name],
            );
            if (
                shortMode &&
                this.shouldCollapseField(field) &&
                text.length > 260
            ) {
                return `${text.slice(0, 260)} ...`;
            }
            return text;
        },
        formatParsedValue(v) {
            const text = this.normalizeValue(v);
            if (text.length > 80) {
                return `${text.slice(0, 80)} ...`;
            }
            return text;
        },
        async togglePlayerFull() {
            this.showPlayerFull = !this.showPlayerFull;
            if (
                this.showPlayerFull &&
                !this.playerFull &&
                !this.playerFullLoading
            ) {
                await this.loadPlayerFull();
            }
        },
        async loadPlayerFull() {
            this.playerFullError = "";
            this.playerFullLoading = true;
            try {
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}/player-full`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                if (data.ok) {
                    this.playerFull = data.data;
                } else {
                    this.playerFullError =
                        data.message || "Không tải được dữ liệu player";
                }
            } catch {
                this.playerFullError = "Không tải được dữ liệu player";
            } finally {
                this.playerFullLoading = false;
            }
        },
        async loadAccount() {
            try {
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                if (data.ok) {
                    const a = data.data;
                    this.form.username = a.username;
                    this.form.cash = a.cash || 0;
                    this.form.danap = a.danap || 0;
                    this.form.coin = a.coin || 0;
                    this.form.diem_da_nhan = a.diem_da_nhan || 0;
                    this.form.diem_danh = a.diem_danh || 0;
                    this.form.is_admin = a.is_admin ? "1" : "0";
                    this.form.active = a.active ? "1" : "0";
                    this.form.ban = a.ban ? "1" : "0";
                    this.playerInfo = a.player || null;
                    if (this.playerInfo) {
                        this.showPlayerFull = true;
                        await this.loadPlayerFull();
                    } else {
                        this.showPlayerFull = false;
                        this.playerFull = null;
                    }
                }
            } catch {
                this.error = "Không thể tải dữ liệu";
            }
        },
        async save() {
            this.error = "";
            this.success = "";
            this.saving = true;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const body = { ...this.form };
                body.is_admin = parseInt(body.is_admin);
                body.active = parseInt(body.active);
                body.ban = parseInt(body.ban);
                body.cash = parseInt(body.cash || 0);
                body.danap = parseInt(body.danap || 0);
                body.coin = parseInt(body.coin || 0);
                body.diem_da_nhan = parseInt(body.diem_da_nhan || 0);
                body.diem_danh = parseInt(body.diem_danh || 0);
                if (this.isEdit && !body.password) {
                    delete body.password;
                }

                const url = this.isEdit
                    ? `/admin/api/accounts/${this.$route.params.id}`
                    : "/admin/api/accounts";
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
                        this.$router.push({ name: "admin.accounts" });
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
            if (!confirm("Bạn chắc chắn muốn xoá tài khoản này?")) return;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                await fetch(`/admin/api/accounts/${this.$route.params.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                });
                this.$router.push({ name: "admin.accounts" });
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
.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 16px;
}
@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    .form-sidebar {
        position: static;
    }
}
@media (max-width: 740px) {
    .form-row-3,
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
.toggle-field {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed var(--ds-border);
}
.toggle-field:last-of-type {
    border-bottom: none;
}
.toggle-row-label {
    font-size: 14px;
    color: var(--ds-text);
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
.toggle-danger input:checked + .toggle-slider {
    background: var(--ds-danger);
}
.danger-card {
    border: 1px solid rgba(var(--ds-danger-rgb), 0.2) !important;
}
.danger-text {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-bottom: 12px;
}
.muted-line {
    font-size: 13px;
    color: var(--ds-text-muted);
}
.player-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
    margin-bottom: 12px;
}
.summary-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.summary-label {
    font-size: 11px;
    color: var(--ds-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.summary-value {
    font-size: 13px;
    color: var(--ds-text);
}
.player-full-wrap {
    margin-top: 12px;
    border-top: 1px dashed var(--ds-border);
    padding-top: 12px;
}
.task-banner {
    font-size: 13px;
    color: var(--ds-text);
    background: rgba(var(--ds-primary-rgb), 0.08);
    border: 1px solid rgba(var(--ds-primary-rgb), 0.25);
    border-radius: 8px;
    padding: 8px 10px;
    margin-bottom: 12px;
}
.parsed-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}
.parsed-box {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 8px 10px;
    background: var(--ds-surface-1);
}
.parsed-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 6px;
}
.parsed-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    align-items: center;
    font-size: 12px;
    padding: 2px 0;
}
.parsed-label {
    color: var(--ds-text-muted);
}
.parsed-value {
    color: var(--ds-text);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    text-align: right;
}
.player-rows {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 620px;
    overflow: auto;
    padding-right: 4px;
}
.player-row {
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 12px;
    align-items: start;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 10px;
    background: var(--ds-surface-1);
}
.player-row-title {
    color: var(--ds-text-emphasis);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.4;
}
.player-row-name {
    font-size: 11px;
    color: var(--ds-text-muted);
    margin-top: 3px;
}
.player-row-value pre {
    margin: 0;
    font-size: 12px;
    line-height: 1.45;
    color: var(--ds-text);
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.btn-xs {
    margin-top: 8px;
    padding: 4px 8px;
    font-size: 11px;
    min-height: 26px;
}
@media (max-width: 900px) {
    .player-summary-grid {
        grid-template-columns: 1fr;
    }
    .parsed-wrap {
        grid-template-columns: 1fr;
    }
    .player-row {
        grid-template-columns: 1fr;
    }
}
</style>
