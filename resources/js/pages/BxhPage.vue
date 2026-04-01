<template>
    <div class="bxh-wrapper">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> > </span>
            <span>Bảng xếp hạng</span>
        </div>

        <div class="rankings-container">
            <div class="main-title" style="color: #1f2937">BẢNG XẾP HẠNG</div>

            <div class="tabs-container">
                <div class="tab-navigation">
                    <button
                        class="tab-btn tab-nap"
                        :class="{ active: activeTab === 'nap' }"
                        @click="activeTab = 'nap'"
                    >
                        NẠP TIỀN
                    </button>
                    <button
                        class="tab-btn tab-power"
                        :class="{ active: activeTab === 'power' }"
                        @click="activeTab = 'power'"
                    >
                        SỨC MẠNH
                    </button>
                    <button
                        class="tab-btn tab-task"
                        :class="{ active: activeTab === 'task' }"
                        @click="activeTab = 'task'"
                    >
                        NHIỆM VỤ
                    </button>
                </div>

                <!-- TAB NẠP TIỀN -->
                <div
                    class="tab-content"
                    :class="{ active: activeTab === 'nap' }"
                >
                    <table v-if="topNap.length" class="ranking-table">
                        <thead>
                            <tr>
                                <th width="80">Hạng</th>
                                <th>Nhân vật</th>
                                <th>Tổng nạp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in topNap" :key="i">
                                <td class="rank-number">
                                    <span
                                        v-if="i < 3"
                                        class="rank-cup"
                                        :class="['gold', 'silver', 'bronze'][i]"
                                        >{{ ["🥇", "🥈", "🥉"][i] }}</span
                                    >
                                    <span v-else>{{ i + 1 }}</span>
                                </td>
                                <td class="player-name">
                                    {{ item.player_name }}
                                </td>
                                <td class="money-value">
                                    {{ Number(item.danap).toLocaleString() }}
                                    VND
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="no-data">
                        Chưa có dữ liệu xếp hạng nạp tiền
                    </div>
                </div>

                <!-- TAB SỨC MẠNH -->
                <div
                    class="tab-content"
                    :class="{ active: activeTab === 'power' }"
                >
                    <table v-if="topPower.length" class="ranking-table">
                        <thead>
                            <tr>
                                <th width="80">Hạng</th>
                                <th>Nhân vật</th>
                                <th>Sức mạnh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in topPower" :key="i">
                                <td class="rank-number">
                                    <span
                                        v-if="i < 3"
                                        class="rank-cup"
                                        :class="['gold', 'silver', 'bronze'][i]"
                                        >{{ ["🥇", "🥈", "🥉"][i] }}</span
                                    >
                                    <span v-else>{{ i + 1 }}</span>
                                </td>
                                <td class="player-name">
                                    {{ item.player_name }}
                                </td>
                                <td class="power-value">
                                    {{ Number(item.power).toLocaleString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="no-data">
                        Chưa có dữ liệu xếp hạng sức mạnh
                    </div>
                </div>

                <!-- TAB NHIỆM VỤ -->
                <div
                    class="tab-content"
                    :class="{ active: activeTab === 'task' }"
                >
                    <table v-if="topTask.length" class="ranking-table">
                        <thead>
                            <tr>
                                <th width="80">Hạng</th>
                                <th>Nhân vật</th>
                                <th>ID nhiệm vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in topTask" :key="i">
                                <td class="rank-number">
                                    <span
                                        v-if="i < 3"
                                        class="rank-cup"
                                        :class="['gold', 'silver', 'bronze'][i]"
                                        >{{ ["🥇", "🥈", "🥉"][i] }}</span
                                    >
                                    <span v-else>{{ i + 1 }}</span>
                                </td>
                                <td class="player-name">
                                    {{ item.player_name }}
                                </td>
                                <td class="task-value">
                                    {{ item.task?.id || 0 }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="no-data">
                        Chưa có dữ liệu xếp hạng nhiệm vụ
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "BxhPage",
    data() {
        return {
            topNap: [],
            topPower: [],
            topTask: [],
            loading: true,
            activeTab: "nap",
        };
    },
    async mounted() {
        try {
            const { data } = await axios.get("/api/bxh");
            if (data.ok) {
                this.topNap = data.data.top_nap || [];
                this.topPower = data.data.top_power || [];
                this.topTask = data.data.top_task || [];
            }
        } catch (err) {
            console.error(err);
        } finally {
            this.loading = false;
        }
    },
};
</script>

<style scoped>
.tab-content {
    display: none;
    padding: 20px;
}
.tab-content.active {
    display: block;
}
</style>
