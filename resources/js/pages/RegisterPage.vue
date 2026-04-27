<template>
    <div class="page-container">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Đăng ký</span>
        </div>
        <div class="auth-box">
            <h1>ĐĂNG KÝ</h1>
            <div v-if="error" class="alert alert-error">{{ error }}</div>
            <div v-if="success" class="alert alert-success">
                {{ success }}
            </div>
            <form @submit.prevent="handleRegister">
                <input
                    v-model="username"
                    type="text"
                    placeholder="Tên đăng nhập"
                    required
                />
                <input
                    v-model="password"
                    type="password"
                    placeholder="Mật khẩu"
                    required
                />
                <input
                    v-model="confirmPassword"
                    type="password"
                    placeholder="Nhập lại mật khẩu"
                    required
                />
                <button
                    type="submit"
                    :disabled="loading"
                    style="margin-top: 10px"
                >
                    <span v-if="loading" class="btn-loading-dot"></span>
                    {{ loading ? "Đang xử lý..." : "ĐĂNG KÝ" }}
                </button>
            </form>
            <p
                style="
                    text-align: center;
                    margin-top: 15px;
                    font-weight: bold;
                    color: #444;
                "
            >
                Đã có tài khoản?
                <router-link
                    to="/login"
                    style="font-weight: bold; color: #007bff"
                    >Đăng nhập</router-link
                >
            </p>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "RegisterPage",
    data() {
        return {
            username: "",
            password: "",
            confirmPassword: "",
            error: "",
            success: "",
            loading: false,
        };
    },
    methods: {
        async handleRegister() {
            this.error = "";
            this.success = "";
            if (this.password !== this.confirmPassword) {
                this.error = "Mật khẩu không khớp";
                return;
            }
            this.loading = true;
            try {
                const { data } = await axios.post("/api/auth/register", {
                    username: this.username,
                    password: this.password,
                });
                if (data.status === "success") {
                    this.success =
                        "Đăng ký thành công! Đang chuyển đến trang đăng nhập...";
                    setTimeout(() => this.$router.push("/login"), 2000);
                } else {
                    this.error = data.message || "Đăng ký thất bại";
                }
            } catch (err) {
                this.error = err.response?.data?.message || "Đăng ký thất bại";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
