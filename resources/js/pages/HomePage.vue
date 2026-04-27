<template>
    <div>
        <!-- HERO -->
        <section class="__section game--brand__show __1">
            <div class="bg_video" style="display: none">
                <video
                    id="videoBgPC"
                    class="videobg hidden__mobile"
                    muted
                    loop
                    preload="none"
                >
                    <source
                        src="/assets/frontend/teaser/videos/g.mp4"
                        type="video/mp4"
                    />
                </video>
            </div>

            <div class="limit__game">
                <div class="main--game__show">
                    <div
                        class="text--brand t-center m-auto p-relative"
                        data-aos="fade-down"
                        data-aos-duration="700"
                        data-aos-delay="100"
                    >
                        <img
                            src="/assets/frontend/home/v1/images/textgame.png?v=4"
                            class="textgame__game"
                        />
                    </div>
                </div>

                <div class="box--download jCenter">
                    <div class="list-link-dl">
                        <a
                            :href="settings.ios_download_url || '#'"
                            class="item-link link-apple"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-dl.png"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-dl-hv.png"
                            />
                        </a>
                        <a
                            :href="settings.android_download_url || '#'"
                            class="item-link link-android"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-dl-android.png"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-dl-android-hv.png"
                            />
                        </a>
                        <a
                            :href="settings.apk_download_url || '#'"
                            class="item-link link-android"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-dl-apk.png?v=2"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-dl-apk-hv.png?v=2"
                            />
                        </a>
                        <router-link to="/nap-card" class="item-link link-card">
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-card.png"
                            />
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-card-hv.png"
                            />
                        </router-link>
                        <a
                            :href="settings.facebook_url || '#'"
                            class="item-link link-fb"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-fb.png"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/btn-dl/btn-fb-hv.png"
                            />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- CONTENT -->
        <div class="box--content">
            <section class="__section box__new __2 clearfix" id="su-kien">
                <div class="tit-frame tCenter">
                    <img
                        src="/assets/frontend/home/v1/images/ttsk.png"
                        style="width: 60%; max-width: 411px"
                    />
                </div>

                <div class="limit__game">
                    <div
                        class="main--box__new clearfix p-r"
                        data-aos="fade-up"
                        data-aos-duration="700"
                        data-aos-delay="400"
                    >
                        <!-- Slides -->
                        <div class="list-slide box-border p-r">
                            <div class="listSlide__new" ref="slickSlider">
                                <div
                                    v-if="loading"
                                    class="page-loading page-loading--compact"
                                >
                                    <div class="page-loading__spinner"></div>
                                </div>
                                <template v-else>
                                    <div
                                        v-for="slide in slides"
                                        :key="slide.id"
                                    >
                                        <img
                                            :src="slide.image"
                                            :alt="slide.title"
                                        />
                                    </div>
                                </template>
                            </div>
                            <div class="icon-rau rau-left-top"></div>
                            <div class="icon-rau rau-right-bottom"></div>
                        </div>

                        <!-- News Tabs -->
                        <div class="box-list-new box-border p-r">
                            <div class="tab-new clearfix f-utm_facebook">
                                <div
                                    class="tab-link custom-border"
                                    :class="{
                                        current: activeTab === 'tin-tuc',
                                    }"
                                    @click="activeTab = 'tin-tuc'"
                                >
                                    <span>Tin tức</span>
                                </div>
                                <div
                                    class="tab-link custom-border"
                                    :class="{
                                        current: activeTab === 'su-kien',
                                    }"
                                    @click="activeTab = 'su-kien'"
                                >
                                    <span>Sự kiện</span>
                                </div>
                                <div
                                    class="tab-link custom-border"
                                    :class="{
                                        current: activeTab === 'huong-dan',
                                    }"
                                    @click="activeTab = 'huong-dan'"
                                >
                                    <span>Hướng dẫn</span>
                                </div>
                            </div>

                            <div class="tab-content">
                                <div
                                    v-if="loading"
                                    class="page-loading page-loading--compact"
                                >
                                    <div class="page-loading__spinner"></div>
                                </div>

                                <template v-else>
                                    <div
                                        class="tab-detail"
                                        :class="{
                                            current: activeTab === 'tin-tuc',
                                        }"
                                    >
                                        <router-link
                                            v-for="post in tin_tuc"
                                            :key="post.id"
                                            :to="`/post/${post.slug}`"
                                            class="item-new-box f-Roboto-Regular"
                                        >
                                            <div class="cat-des">
                                                {{ post.title }}
                                            </div>
                                            <div class="date-open">
                                                {{
                                                    formatDate(post.created_at)
                                                }}
                                            </div>
                                        </router-link>
                                    </div>

                                    <div
                                        class="tab-detail"
                                        :class="{
                                            current: activeTab === 'su-kien',
                                        }"
                                    >
                                        <router-link
                                            v-for="post in su_kien"
                                            :key="post.id"
                                            :to="`/post/${post.slug}`"
                                            class="item-new-box f-Roboto-Regular"
                                        >
                                            <div class="cat-des">
                                                {{ post.title }}
                                            </div>
                                            <div class="date-open">
                                                {{
                                                    formatDate(post.created_at)
                                                }}
                                            </div>
                                        </router-link>
                                    </div>

                                    <div
                                        class="tab-detail"
                                        :class="{
                                            current: activeTab === 'huong-dan',
                                        }"
                                    >
                                        <router-link
                                            v-for="post in huong_dan"
                                            :key="post.id"
                                            :to="`/post/${post.slug}`"
                                            class="item-new-box f-Roboto-Regular"
                                        >
                                            <div class="cat-des">
                                                {{ post.title }}
                                            </div>
                                            <div class="date-open">
                                                {{
                                                    formatDate(post.created_at)
                                                }}
                                            </div>
                                        </router-link>
                                    </div>
                                </template>
                            </div>

                            <div class="icon-rau rau-left-bottom"></div>
                            <div class="icon-rau rau-right-top"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Box Links -->
            <div class="box-link">
                <div class="container">
                    <div class="main-box-link">
                        <a
                            href="https://zalo.me/g/tkdeeb069"
                            class="item-box-link"
                            data-aos="fade-up"
                            data-aos-delay="400"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/box-link/img-gr.png"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/box-link/img-gr-hv.png"
                            />
                        </a>
                        <a
                            :href="settings.facebook_url || '#'"
                            class="item-box-link hidden-mobile"
                            data-aos="fade-up"
                            data-aos-delay="600"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/box-link/img-fb.png"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/box-link/img-fb-hv.png"
                            />
                        </a>
                        <router-link
                            to="/giftcode"
                            class="item-box-link"
                            data-aos="fade-up"
                            data-aos-delay="800"
                        >
                            <img
                                class="img-ac"
                                src="/assets/frontend/home/v1/images/box-link/img-gc.png"
                            />
                            <img
                                class="img-hv"
                                src="/assets/frontend/home/v1/images/box-link/img-gc-hv.png"
                            />
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Section -->
        <section class="__section box_game ftg__sl __3">
            <div class="limit__game">
                <div class="tit-frame tCenter">
                    <img
                        src="/assets/frontend/teaser/images/ten_box_game/tit-tinhnang.png"
                        style="width: 60%; max-width: 411px"
                    />
                </div>
                <div class="bg__sl_ft p-r m__inline">
                    <img
                        src="/assets/frontend/teaser/images/ftgame/bg-tn.png"
                        style="width: 100%"
                    />
                    <div
                        class="slide__tinhnang slide__feature p-a slick-custom-dots"
                        ref="featureSlider"
                    >
                        <img
                            src="/assets/frontend/teaser/images/ftgame/teaser1.jpg?v=2"
                        />
                        <img
                            src="/assets/frontend/teaser/images/ftgame/teaser2.jpg?v=2"
                        />
                        <img
                            src="/assets/frontend/teaser/images/ftgame/teaser3.jpg?v=2"
                        />
                        <img
                            src="/assets/frontend/teaser/images/ftgame/teaser4.jpg?v=2"
                        />
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "HomePage",
    data() {
        return {
            slides: [],
            tin_tuc: [],
            su_kien: [],
            huong_dan: [],
            settings: {},
            activeTab: "tin-tuc",
            loading: true,
        };
    },
    async mounted() {
        await this.loadData();
        this.$nextTick(() => {
            this.initSliders();
            this.initAOS();
        });
    },
    methods: {
        async loadData() {
            try {
                const { data } = await axios.get("/api/home");
                this.slides = data.slides || [];
                this.tin_tuc = data.tin_tuc || [];
                this.su_kien = data.su_kien || [];
                this.huong_dan = data.huong_dan || [];
                this.settings = data.settings || {};
            } catch (err) {
                console.error("Failed to load home data:", err);
            } finally {
                this.loading = false;
            }
        },
        formatDate(dateStr) {
            if (!dateStr) return "";
            const d = new Date(dateStr);
            return d.toLocaleDateString("vi-VN");
        },
        initSliders() {
            if (window.$ && window.$.fn.slick) {
                window.$(this.$refs.slickSlider).slick({
                    autoplay: true,
                    autoplaySpeed: 3000,
                    dots: true,
                    arrows: false,
                    infinite: true,
                    speed: 500,
                    fade: true,
                });
                window.$(this.$refs.featureSlider).slick({
                    autoplay: true,
                    autoplaySpeed: 4000,
                    dots: true,
                    arrows: false,
                    infinite: true,
                    speed: 500,
                    fade: true,
                });
            }
        },
        initAOS() {
            if (window.AOS) {
                window.AOS.init();
            }
        },
    },
};
</script>
