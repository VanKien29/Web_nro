<template>
    <div class="page-container">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Thông tin cá nhân</span>
        </div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>

        <div
            v-else-if="profile"
            class="profile-container"
            style="max-width: 600px; margin: 30px auto"
        >
            <!-- Flash message -->
            <div
                v-if="message"
                class="alert"
                :class="
                    messageType === 'success' ? 'alert-success' : 'alert-error'
                "
            >
                {{ message }}
            </div>

            <!-- ACCOUNT INFO -->
            <div
                class="account-info-section"
                style="
                    background: #d597fa;
                    border: 3px solid #222;
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    gap: 20px;
                "
            >
                <img
                    :src="
                        profile.player?.avatar_url ||
                        '/assets/frontend/home/v1/images/favicon.png'
                    "
                    style="
                        width: 80px;
                        height: 80px;
                        border-radius: 50%;
                        border: 3px solid #222;
                    "
                />
                <div>
                    <div
                        style="
                            font-family: &quot;Bangers&quot;;
                            font-size: 1.5em;
                            color: #111;
                        "
                    >
                        👤 {{ profile.user.username }}
                    </div>
                    <div style="color: #111">
                        Số tiền:
                        {{ Number(profile.user.cash || 0).toLocaleString() }}
                        VNĐ
                    </div>
                    <div style="color: #111">
                        Số tiền đã nạp:
                        {{ Number(profile.user.danap || 0).toLocaleString() }}
                        VNĐ
                    </div>
                </div>
            </div>

            <!-- PLAYER INFO -->
            <div
                v-if="profile.player && profile.player.has_character"
                style="
                    background: #90cdf4;
                    border: 3px solid #222;
                    border-radius: 12px;
                    padding: 20px;
                    margin-top: 20px;
                "
            >
                <h2
                    style="
                        font-family: &quot;Bangers&quot;;
                        text-align: center;
                        font-size: 22px;
                        color: #111;
                    "
                >
                    THÔNG TIN NHÂN VẬT
                </h2>
                <div style="color: #111">Tên: {{ profile.player.name }}</div>
                <div style="color: #111">
                    Sức mạnh:
                    {{ Number(profile.player.power || 0).toLocaleString() }}
                </div>
                <div style="color: #111">
                    Nhiệm vụ: {{ profile.player.task_name }}
                </div>
                <div style="color: #111">
                    Hành tinh: {{ profile.player.gender_text }}
                </div>
                <div style="color: #111">
                    Trạng thái:
                    <span
                        class="status-badge"
                        :class="
                            profile.user.active
                                ? 'status-active'
                                : 'status-pending'
                        "
                    >
                        {{
                            profile.user.active
                                ? "Đã kích hoạt"
                                : "Chưa kích hoạt"
                        }}
                    </span>
                </div>
            </div>
            <div
                v-else
                style="
                    background: #90cdf4;
                    border: 3px solid #222;
                    border-radius: 12px;
                    padding: 20px;
                    margin-top: 20px;
                    text-align: center;
                    color: #111;
                "
            >
                Chưa tạo nhân vật trong game.
            </div>

            <!-- BUTTONS -->
            <div
                style="
                    background: #42e4f5;
                    border: 3px solid #222;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    margin-top: 20px;
                "
            >
                <div
                    v-if="!profile.user.active"
                    class="button-grid-2x2"
                    style="
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 15px;
                    "
                >
                    <button
                        class="action-btn"
                        @click="showPasswordModal = true"
                    >
                        Đổi mật khẩu
                    </button>
                    <button
                        class="action-btn"
                        @click="showActivateModal = true"
                    >
                        ⚡Kích hoạt
                    </button>
                    <a
                        href="https://zalo.me/g/tkdeeb069"
                        class="action-btn"
                        style="text-decoration: none"
                        >Nhóm Zalo</a
                    >
                    <button class="action-btn" @click="logout">
                        Đăng xuất
                    </button>
                </div>
                <div
                    v-else
                    class="button-grid-1x3"
                    style="
                        display: grid;
                        grid-template-columns: 1fr 1fr 1fr;
                        gap: 15px;
                    "
                >
                    <button
                        class="action-btn"
                        @click="showPasswordModal = true"
                    >
                        Đổi mật khẩu
                    </button>
                    <a
                        href="https://zalo.me/g/tkdeeb069"
                        class="action-btn"
                        style="text-decoration: none"
                        >Nhóm Zalo</a
                    >
                    <button class="action-btn" @click="logout">
                        Đăng xuất
                    </button>
                </div>
            </div>

            <!-- PASSWORD MODAL -->
            <div class="modal-overlay" :class="{ active: showPasswordModal }">
                <div class="modal">
                    <h3>ĐỔI MẬT KHẨU</h3>
                    <form @submit.prevent="changePassword">
                        <input
                            v-model="newPassword"
                            type="password"
                            placeholder="Mật khẩu mới"
                            required
                            style="margin-bottom: 12px"
                        />
                        <input
                            v-model="confirmNewPassword"
                            type="password"
                            placeholder="Xác nhận mật khẩu"
                            required
                        />
                        <div class="btn-row">
                            <button type="submit" class="btn-success">
                                ✔ CẬP NHẬT
                            </button>
                            <button
                                type="button"
                                class="btn-danger"
                                @click="showPasswordModal = false"
                            >
                                ✖ HỦY
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ACTIVATE MODAL -->
            <div class="modal-overlay" :class="{ active: showActivateModal }">
                <div class="modal">
                    <h3>KÍCH HOẠT TÀI KHOẢN</h3>
                    <p
                        style="
                            text-align: center;
                            color: #111;
                            margin-bottom: 20px;
                        "
                    >
                        Kích hoạt sẽ tốn 10.000 VNĐ từ tài khoản game. Bạn có
                        chắc chắn không?
                    </p>
                    <div style="display: flex; gap: 10px">
                        <button
                            class="btn-success"
                            style="flex: 1"
                            @click="activateAccount"
                        >
                            ✔ Xác nhận
                        </button>
                        <button
                            class="btn-danger"
                            style="flex: 1"
                            @click="showActivateModal = false"
                        >
                            ✖ Hủy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ProfilePage",
    data() {
        return {
            profile: null,
            loading: true,
            message: "",
            messageType: "",
            showPasswordModal: false,
            showActivateModal: false,
            newPassword: "",
            confirmNewPassword: "",
        };
    },
    methods: {
        getAuthHeaders() {
            return {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
            };
        },
        async changePassword() {
            if (this.newPassword !== this.confirmNewPassword) {
                this.message = "Mật khẩu không khớp";
                this.messageType = "error";
                return;
            }
            try {
                const { data } = await axios.post(
                    "/api/change-password",
                    { new_password: this.newPassword },
                    this.getAuthHeaders(),
                );
                this.message = data.message || "Đổi mật khẩu thành công";
                this.messageType = data.ok ? "success" : "error";
                if (data.ok) this.showPasswordModal = false;
            } catch (err) {
                this.message = err.response?.data?.message || "Lỗi";
                this.messageType = "error";
            }
        },
        async activateAccount() {
            try {
                const { data } = await axios.post(
                    "/api/activate",
                    {},
                    this.getAuthHeaders(),
                );
                this.message = data.message || "Kích hoạt thành công";
                this.messageType = data.ok ? "success" : "error";
                if (data.ok) {
                    this.showActivateModal = false;
                    this.profile.user.active = 1;
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Lỗi";
                this.messageType = "error";
            }
        },
        logout() {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            this.$router.push("/");
            window.location.reload();
        },
    },
    async mounted() {
        const token = localStorage.getItem("token");
        if (!token) {
            this.$router.push("/login");
            return;
        }
        try {
            const { data } = await axios.get(
                "/api/profile",
                this.getAuthHeaders(),
            );
            if (data.ok) this.profile = data.data;
        } catch (err) {
            if (err.response?.status === 401) {
                localStorage.removeItem("token");
                localStorage.removeItem("user");
                this.$router.push("/login");
            }
        } finally {
            this.loading = false;
        }
    },
};
</script>
