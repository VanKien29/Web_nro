<template>
    <div class="login-wrap">
        <div class="login-box">
            <div class="login-brand">
                <div class="login-brand-icon">N</div>
                <div class="login-brand-text">NRO HDPE</div>
            </div>
            <p class="login-sub">Đăng nhập quản trị</p>

            <div v-if="error" class="alert alert-error">{{ error }}</div>

            <form @submit.prevent="submit">
                <div class="form-group">
                    <label class="form-label">Tên tài khoản</label>
                    <input
                        v-model="form.username"
                        class="form-input"
                        type="text"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
                <div class="form-group">
                    <label class="form-label">Mật khẩu</label>
                    <input
                        v-model="form.password"
                        class="form-input"
                        type="password"
                        required
                        autocomplete="current-password"
                    />
                </div>
                <button class="btn btn-primary btn-block" :disabled="loading">
                    <span v-if="loading">Đang đăng nhập…</span>
                    <span v-else>Đăng nhập</span>
                </button>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            form: { username: "", password: "" },
            error: "",
            loading: false,
        };
    },
    methods: {
        async submit() {
            this.error = "";
            this.loading = true;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch("/admin/api/login", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (res.ok && data.ok) {
                    this.$router.push({ name: "admin.dashboard" });
                } else {
                    this.error = data.message || "Sai tài khoản hoặc mật khẩu";
                }
            } catch {
                this.error = "Lỗi kết nối";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.login-wrap {
    width: 100%;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--ds-body-bg);
}
.login-box {
    width: 100%;
    max-width: 420px;
    background: var(--ds-surface);
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    padding: 44px 36px;
}
.login-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 8px;
}
.login-brand-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(
        135deg,
        var(--ds-primary),
        var(--ds-primary-soft, #59a08f)
    );
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
    color: #fff;
}
.login-brand-text {
    font-size: 22px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}
.login-sub {
    text-align: center;
    font-size: 14px;
    color: var(--ds-text-muted);
    margin-bottom: 28px;
}
.btn-block {
    width: 100%;
    padding: 12px;
    font-size: 15px;
    font-weight: 600;
}
</style>
