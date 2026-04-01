<template>
    <div class="giftcode-wrapper">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Giftcode</span>
        </div>

        <div class="giftcode-container">
            <div class="main-title" style="color: #1f2937">
                DANH SÁCH GIFTCODE
            </div>

            <p v-if="loading" style="text-align: center; color: #111">
                Đang tải...
            </p>

            <div v-else-if="giftcodes.length === 0" class="no-giftcodes">
                Hiện chưa có giftcode nào.
            </div>

            <div v-else class="giftcode-list">
                <div
                    v-for="(gc, i) in giftcodes"
                    :key="gc.id"
                    class="giftcode-item"
                    :class="{ expanded: expandedIndex === i }"
                >
                    <div class="giftcode-header" @click="toggleGiftcode(i)">
                        <div class="gift-icon"></div>
                        <div class="giftcode-info">
                            <div
                                class="giftcode-name copy-code"
                                @click.stop="copyCode(gc.code)"
                            >
                                {{ gc.code }}
                                <span class="copy-icon">📋</span>
                            </div>
                            <div class="giftcode-stats">
                                Còn lại: {{ gc.count_left }}
                            </div>
                        </div>
                        <div class="expand-icon"></div>
                    </div>
                    <div class="giftcode-details">
                        <div
                            v-if="gc.items && gc.items.length"
                            class="items-grid"
                        >
                            <div
                                v-for="item in gc.items"
                                :key="item.temp_id"
                                class="item-card"
                            >
                                <div class="item-icon-wrapper">
                                    <div
                                        class="item-icon"
                                        :style="{
                                            backgroundImage: `url('/assets/frontend/home/v1/images/x4/${item.icon_id}.png')`,
                                        }"
                                    ></div>
                                    <div
                                        v-if="
                                            item.options && item.options.length
                                        "
                                        class="item-tooltip"
                                    >
                                        <div
                                            v-for="opt in item.options"
                                            :key="opt.id"
                                            class="tooltip-line"
                                        >
                                            {{ opt.text }}
                                        </div>
                                    </div>
                                </div>
                                <div class="item-name">
                                    {{ item.name || "Không rõ" }}
                                </div>
                                <div class="item-quantity">
                                    x{{ item.quantity }}
                                </div>
                            </div>
                        </div>
                        <div v-else style="text-align: center; color: #666">
                            Không có thông tin phần thưởng
                        </div>
                        <br />
                        <p style="color: black; text-align: center">
                            Click vào ảnh vật phẩm để xem chỉ số.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "GiftcodePage",
    data() {
        return { giftcodes: [], loading: true, expandedIndex: null };
    },
    methods: {
        toggleGiftcode(index) {
            this.expandedIndex = this.expandedIndex === index ? null : index;
        },
        copyCode(code) {
            navigator.clipboard.writeText(code).catch(() => {});
        },
    },
    async mounted() {
        try {
            const { data } = await axios.get("/api/giftcodes");
            if (data.ok) {
                this.giftcodes = (data.data || []).filter(
                    (gc) => gc.count_left > 0,
                );
            }
        } catch (err) {
            console.error(err);
        } finally {
            this.loading = false;
        }
    },
};
</script>
