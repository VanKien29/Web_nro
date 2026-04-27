<template>
    <div class="page-container">
        <div class="breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span style="color: black"> › </span>
            <span v-if="post">{{ post.title }}</span>
            <span v-else>Đang tải...</span>
        </div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>
        <p v-else-if="!post" style="text-align: center; color: #111">
            Bài viết không tồn tại.
        </p>

        <template v-else>
            <div class="post-wrapper">
                <!-- Main Post -->
                <article class="post-detail">
                    <h1
                        style="
                            text-align: center;
                            font-family: &quot;Bangers&quot;, cursive;
                            font-size: 36px;
                        "
                    >
                        {{ post.title }}
                    </h1>

                    <div class="post-meta" style="text-align: center">
                        <span>👤 {{ post.author || "Admin" }}</span>
                        <span>🕐 {{ formatDate(post.created_at) }}</span>
                        <span>👁 {{ post.views || 0 }} lượt xem</span>
                    </div>

                    <div class="content-preview" v-if="!showFullContent">
                        <div
                            class="content-preview-text"
                            v-html="post.content"
                        ></div>
                        <div class="content-read-more">
                            <button
                                class="content-read-more-btn"
                                @click="showFullContent = true"
                            >
                                Xem thêm →
                            </button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="post-content"
                        v-html="post.content"
                    ></div>

                    <div class="post-actions">
                        <button class="post-action-btn">❤ Thích</button>
                        <button class="post-action-btn">💬 Bình luận</button>
                        <button class="post-action-btn">🔗 Chia sẻ</button>
                    </div>
                </article>

                <!-- Sidebar -->
                <aside class="post-sidebar">
                    <div class="sidebar-widget">
                        <h4>📰 Bài viết khác</h4>
                        <router-link to="/" class="widget-link"
                            >← Quay lại danh sách</router-link
                        >
                        <router-link to="/bxh" class="widget-link"
                            >Xếp hạng hôm nay</router-link
                        >
                        <router-link to="/giftcode" class="widget-link"
                            >Mã quà tặng</router-link
                        >
                    </div>
                    <div class="sidebar-widget">
                        <h4>ℹ️ Thông tin</h4>
                        <p style="color: #111">
                            Bài viết được đăng lúc
                            <strong>{{ formatDate(post.created_at) }}</strong>
                        </p>
                    </div>
                </aside>
            </div>
        </template>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "PostDetailPage",
    data() {
        return { post: null, loading: true, showFullContent: false };
    },
    methods: {
        formatDate(d) {
            return d ? new Date(d).toLocaleDateString("vi-VN") : "";
        },
    },
    async mounted() {
        const slug = this.$route.params.slug;
        try {
            const { data } = await axios.get(`/api/posts/${slug}`);
            if (data.ok) this.post = data.data;
        } catch (err) {
            console.error(err);
        } finally {
            this.loading = false;
        }
    },
};
</script>
