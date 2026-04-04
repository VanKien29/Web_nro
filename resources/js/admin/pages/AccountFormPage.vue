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
                is_admin: "0",
                active: "1",
                ban: "0",
            },
            error: "",
            success: "",
            saving: false,
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
        },
    },
    created() {
        if (this.isEdit) {
            this.loadAccount();
        }
    },
    methods: {
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
                    this.form.is_admin = a.is_admin ? "1" : "0";
                    this.form.active = a.active ? "1" : "0";
                    this.form.ban = a.ban ? "1" : "0";
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
                // Convert checkbox values to int
                body.is_admin = parseInt(body.is_admin);
                body.active = parseInt(body.active);
                body.ban = parseInt(body.ban);
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
@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    .form-sidebar {
        position: static;
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
</style>
