<template>
    <div class="bxh-wrapper">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Nạp Thẻ Cào</span>
        </div>

        <div
            style="
                display: flex;
                gap: 10px;
                justify-content: center;
                margin-bottom: 20px;
            "
        >
            <router-link to="/nap-atm" class="btn-change2">Nạp ATM</router-link>
            <router-link to="/nap-card" class="btn-change1"
                >Nạp Thẻ Cào</router-link
            >
        </div>

        <div class="page-title">NẠP THẺ CÀO</div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>

        <div v-else class="topup-grid" style="display: block">
            <!-- Card Form -->
            <div class="topup-box" style="margin-bottom: 20px">
                <form @submit.prevent="submitCard">
                    <div class="form-group">
                        <label>Tên Nhân Vật:</label>
                        <input type="text" :value="username" readonly />
                    </div>
                    <div class="form-group">
                        <label>Loại Thẻ:</label>
                        <select v-model="cardType">
                            <option value="">-- Chọn loại thẻ --</option>
                            <option value="Viettel">Viettel</option>
                            <option value="Mobifone">Mobifone</option>
                            <option value="Vinaphone">Vinaphone</option>
                            <option value="Vietnamobile">Vietnamobile</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mệnh Giá:</label>
                        <select v-model="cardAmount">
                            <option value="">-- Chọn mệnh giá --</option>
                            <option value="10000">10.000 đ</option>
                            <option value="20000">20.000 đ</option>
                            <option value="50000">50.000 đ</option>
                            <option value="100000">100.000 đ</option>
                            <option value="200000">200.000 đ</option>
                            <option value="500000">500.000 đ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Số Serial:</label>
                        <input
                            v-model="serial"
                            type="text"
                            placeholder="Nhập số serial thẻ"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label>Mã thẻ:</label>
                        <input
                            v-model="pin"
                            type="text"
                            placeholder="Nhập mã thẻ"
                            required
                        />
                    </div>
                    <div
                        v-if="formMessage"
                        :class="
                            formMessageType === 'success'
                                ? 'msg-success'
                                : 'msg-error'
                        "
                        style="
                            padding: 12px;
                            border-radius: 6px;
                            margin-bottom: 15px;
                            text-align: center;
                            font-weight: bold;
                        "
                    >
                        {{ formMessage }}
                    </div>
                    <button
                        type="submit"
                        class="btn-change1"
                        :disabled="submitting"
                    >
                        {{ submitting ? "Đang gửi..." : "NẠP THẺ" }}
                    </button>
                </form>
            </div>

            <!-- Notes + History -->
            <div class="side-box">
                <div class="note">
                    <strong>LƯU Ý QUAN TRỌNG:</strong>
                    <p>Kiểm tra kỹ thông tin trước khi nạp</p>
                    <p>Nạp sai loại/mệnh giá → Admin KHÔNG hoàn tiền</p>
                    <p>Sau 5 phút chưa được cộng, liên hệ Admin</p>
                </div>

                <div class="history">
                    <h3>LỊCH SỬ NẠP</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Loại Thẻ</th>
                                <th>Seri</th>
                                <th>Mã Thẻ</th>
                                <th>Mệnh Giá</th>
                                <th>Thời Gian</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!cardHistory.length">
                                <td colspan="7" style="color: #111">
                                    Chưa có giao dịch nào
                                </td>
                            </tr>
                            <tr v-for="(tx, i) in cardHistory" :key="tx.id">
                                <td>{{ i + 1 }}</td>
                                <td>{{ tx.card_type }}</td>
                                <td>{{ tx.serial }}</td>
                                <td>{{ tx.pin }}</td>
                                <td>
                                    {{ Number(tx.amount).toLocaleString() }}đ
                                </td>
                                <td>{{ formatDate(tx.created_at) }}</td>
                                <td>
                                    {{
                                        tx.status === 1
                                            ? "✅ Thành công"
                                            : tx.status === 2
                                              ? "❌ Thất bại"
                                              : "⏳ Chờ"
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "TopupCardPage",
    data() {
        return {
            cardType: "",
            cardAmount: "",
            serial: "",
            pin: "",
            cardHistory: [],
            formMessage: "",
            formMessageType: "",
            submitting: false,
            loading: true,
        };
    },
    computed: {
        username() {
            try {
                return (
                    JSON.parse(localStorage.getItem("user") || "{}").username ||
                    ""
                );
            } catch {
                return "";
            }
        },
    },
    methods: {
        formatDate(d) {
            return d ? new Date(d).toLocaleString("vi-VN") : "";
        },
        getAuthHeaders() {
            return {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
            };
        },
        async submitCard() {
            this.formMessage = "";
            if (
                !this.cardType ||
                !this.cardAmount ||
                !this.serial ||
                !this.pin
            ) {
                this.formMessage = "Vui lòng điền đầy đủ thông tin";
                this.formMessageType = "error";
                return;
            }
            this.submitting = true;
            try {
                const { data } = await axios.post(
                    "/api/topup/card",
                    {
                        card_type: this.cardType,
                        amount: this.cardAmount,
                        serial: this.serial,
                        pin: this.pin,
                    },
                    this.getAuthHeaders(),
                );
                this.formMessage = data.message || "Gửi thẻ thành công";
                this.formMessageType = data.ok ? "success" : "error";
                if (data.ok) {
                    this.serial = "";
                    this.pin = "";
                    this.loadHistory();
                }
            } catch (err) {
                this.formMessage = err.response?.data?.message || "Lỗi gửi thẻ";
                this.formMessageType = "error";
            } finally {
                this.submitting = false;
            }
        },
        async loadHistory() {
            try {
                const { data } = await axios.get(
                    "/api/topup/card/history",
                    this.getAuthHeaders(),
                );
                if (data.ok) this.cardHistory = data.data || [];
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        const token = localStorage.getItem("token");
        if (!token) {
            this.$router.push("/login");
            return;
        }
        this.loadHistory();
    },
};
</script>
