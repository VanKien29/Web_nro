<template>
    <div class="bxh-wrapper">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Nạp ATM</span>
        </div>

        <div
            style="
                display: flex;
                gap: 10px;
                justify-content: center;
                margin-bottom: 20px;
            "
        >
            <router-link to="/nap-atm" class="btn-change1">Nạp ATM</router-link>
            <router-link to="/nap-card" class="btn-change2"
                >Nạp Thẻ Cào</router-link
            >
        </div>

        <div class="page-title">NẠP ATM</div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>

        <div v-else class="topup-grid">
            <!-- LEFT: QR + Bank info -->
            <div class="topup-box">
                <div class="qr-box">
                    <img :src="qrUrl" alt="QR" />
                    <div style="color: #111; margin-top: 8px">
                        Quét mã QR để thanh toán
                    </div>
                </div>

                <table class="table-info">
                    <tbody>
                        <tr>
                            <td>Ngân hàng:</td>
                            <td>
                                <b>{{ bankName }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td>Số TK:</td>
                            <td>
                                <b>{{ bankAccount }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td>Chủ TK:</td>
                            <td>{{ bankOwner }}</td>
                        </tr>
                        <tr>
                            <td>Số tiền nạp:</td>
                            <td>
                                <input
                                    v-model.number="amount"
                                    type="number"
                                    min="10000"
                                    placeholder="10000"
                                    @input="updateQR"
                                />
                            </td>
                        </tr>
                        <tr>
                            <td>Nội dung CK:</td>
                            <td>
                                <b>{{ transferContent }}</b>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p style="color: red; text-align: center; font-size: 14px">
                    Tối thiểu: 10.000đ
                </p>
            </div>

            <!-- RIGHT: Notes + History -->
            <div class="side-box">
                <div class="note">
                    <b>⚠️ LƯU Ý QUAN TRỌNG:</b>
                    <ul>
                        <li>Vui lòng nhập đúng nội dung chuyển khoản</li>
                        <li>
                            Sai nội dung hoặc sai số tiền không chịu trách
                            nhiệm.
                        </li>
                        <li>Hệ thống xử lý tự động trong 30s - 2 phút.</li>
                    </ul>
                </div>

                <div class="history">
                    <h3>📜 LỊCH SỬ NẠP</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!history.length">
                                <td colspan="3" style="color: #111">
                                    Chưa có giao dịch nào
                                </td>
                            </tr>
                            <tr v-for="tx in paginatedHistory" :key="tx.id">
                                <td>{{ formatDate(tx.created_at) }}</td>
                                <td>
                                    {{ Number(tx.amount).toLocaleString() }}đ
                                </td>
                                <td>
                                    {{
                                        tx.status === 1
                                            ? "✅ Thành công"
                                            : "⏳ Chờ xử lý"
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="totalPages > 1" class="pagination">
                        <button
                            class="page-btn"
                            :disabled="currentPage <= 1"
                            @click="currentPage--"
                        >
                            «
                        </button>
                        <button
                            v-for="p in totalPages"
                            :key="p"
                            class="page-btn"
                            :class="{ active: p === currentPage }"
                            @click="currentPage = p"
                        >
                            {{ p }}
                        </button>
                        <button
                            class="page-btn"
                            :disabled="currentPage >= totalPages"
                            @click="currentPage++"
                        >
                            »
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
    name: "TopupAtmPage",
    data() {
        return {
            amount: 10000,
            bankName: "",
            bankAccount: "",
            bankOwner: "",
            transferContent: "",
            qrUrl: "",
            history: [],
            currentPage: 1,
            perPage: 10,
            loading: true,
        };
    },
    computed: {
        totalPages() {
            return Math.ceil(this.history.length / this.perPage);
        },
        paginatedHistory() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.history.slice(start, start + this.perPage);
        },
    },
    methods: {
        formatDate(d) {
            return d ? new Date(d).toLocaleString("vi-VN") : "";
        },
        updateQR() {
            if (this.bankAccount && this.amount >= 10000) {
                this.qrUrl = `https://img.vietqr.io/image/${this.bankName}-${this.bankAccount}-print.png?amount=${this.amount}&addInfo=${encodeURIComponent(this.transferContent)}`;
            }
        },
        async loadData() {
            const token = localStorage.getItem("token");
            if (!token) {
                this.$router.push("/login");
                return;
            }
            try {
                const { data } = await axios.get("/api/topup/history", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                if (data.ok) this.history = data.data || [];
            } catch (err) {
                if (err.response?.status === 401) {
                    this.$router.push("/login");
                }
            }
            try {
                const { data } = await axios.get("/api/home");
                const s = data.settings || {};
                this.bankName = s.bank_name || "MB";
                this.bankAccount = s.bank_account || "";
                this.bankOwner = s.bank_owner || "";
                this.transferContent =
                    s.transfer_prefix ||
                    "NRO" +
                        (JSON.parse(localStorage.getItem("user") || "{}")
                            .username || "");
                this.updateQR();
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        this.loadData();
    },
};
</script>
