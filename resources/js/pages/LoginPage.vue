<template>
    <div class="page-container">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Đăng nhập</span>
        </div>
        <div class="login-box">
            <h2>ĐĂNG NHẬP</h2>
            <div v-if="error" class="alert alert-error">{{ error }}</div>
            <div v-if="success" class="alert alert-success">
                {{ success }}
            </div>
            <form @submit.prevent="handleLogin">
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
                <button
                    type="submit"
                    :disabled="loading"
                    style="margin-top: 10px"
                >
                    {{ loading ? "Đang xử lý..." : "ĐĂNG NHẬP" }}
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
                Bạn chưa có tài khoản?
                <router-link
                    to="/register"
                    style="font-weight: bold; color: #007bff"
                    >Đăng ký ngay</router-link
                >
            </p>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "LoginPage",
    data() {
        return {
            username: "",
            password: "",
            error: "",
            success: "",
            loading: false,
        };
    },
    methods: {
        async handleLogin() {
            this.error = "";
            this.loading = true;
            try {
                const { data } = await axios.post("/api/auth/login", {
                    username: this.username,
                    password: this.password,
                });
                if (data.status === "success") {
                    localStorage.setItem("token", data.token);
                    localStorage.setItem("user", JSON.stringify(data.user));
                    this.$router.push("/");
                } else {
                    this.error = data.message || "Đăng nhập thất bại";
                }
            } catch (err) {
                this.error =
                    err.response?.data?.message || "Đăng nhập thất bại";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
