<template>
    <div>
        <!-- GAME HEADER -->
        <header
            class="game-header"
            :class="{ 'game-header--scrolled': scrolled }"
        >
            <div class="game-header__inner">
                <!-- Logo -->
                <router-link to="/" class="game-header__logo">
                    <img
                        src="/assets/frontend/home/v1/images/bannergame.png"
                        alt="HDPE"
                        class="game-header__icon"
                    />
                    <img
                        src="/assets/frontend/home/v1/images/textgame.png"
                        alt="Ngọc Rồng HDPE"
                        class="game-header__wordmark"
                    />
                </router-link>

                <!-- Main Nav -->
                <nav class="game-nav" :class="{ 'game-nav--open': menuOpen }">
                    <router-link
                        to="/"
                        exact
                        class="game-nav__link"
                        @click="menuOpen = false"
                    >
                        Trang chủ
                    </router-link>
                    <router-link
                        to="/bxh"
                        class="game-nav__link"
                        @click="menuOpen = false"
                    >
                        Đua Top
                    </router-link>
                    <router-link
                        to="/giftcode"
                        class="game-nav__link"
                        @click="menuOpen = false"
                    >
                        Giftcode
                    </router-link>
                    <router-link
                        to="/nap-atm"
                        class="game-nav__link"
                        @click="menuOpen = false"
                    >
                        Nạp Tiền
                    </router-link>

                    <template v-if="isLoggedIn">
                        <router-link
                            to="/profile"
                            class="game-nav__link game-nav__link--user"
                            @click="menuOpen = false"
                        >
                            {{ username }}
                        </router-link>
                        <a
                            href="#"
                            class="game-nav__link game-nav__link--logout"
                            @click.prevent="logout"
                        >
                            Đăng xuất
                        </a>
                    </template>
                    <template v-else>
                        <router-link
                            to="/login"
                            class="game-nav__btn game-nav__btn--login"
                            @click="menuOpen = false"
                        >
                            Đăng nhập
                        </router-link>
                        <router-link
                            to="/register"
                            class="game-nav__btn game-nav__btn--register"
                            @click="menuOpen = false"
                        >
                            Đăng ký
                        </router-link>
                    </template>
                </nav>

                <!-- Mobile toggle -->
                <button
                    class="game-header__toggle"
                    @click="menuOpen = !menuOpen"
                    aria-label="Menu"
                    style="z-index: 2100"
                >
                    <span :class="{ open: menuOpen }"></span>
                    <span :class="{ open: menuOpen }"></span>
                    <span :class="{ open: menuOpen }"></span>
                </button>
            </div>
            <!-- Overlay for mobile sidebar -->
            <div
                v-if="menuOpen"
                class="mobile-overlay"
                @click="menuOpen = false"
                style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 1900;
                "
            ></div>
        </header>

        <!-- PAGE CONTENT -->
        <main :class="{ 'inner-page': !isHome }">
            <router-view />
        </main>

        <!-- FOOTER -->
        <footer class="game-footer">
            <div class="game-footer__glow"></div>
            <div class="game-footer__inner">
                <div class="game-footer__left">
                    <img
                        src="/assets/frontend/home/v1/images/rtsc.png"
                        alt="Logo"
                        class="game-footer__logo"
                    />
                    <p class="game-footer__brand">NGỌC RỒNG HDPE</p>
                    <p class="game-footer__tagline">
                        Game Ngọc Rồng Private Server
                    </p>
                </div>
                <div class="game-footer__center">
                    <div class="game-footer__links">
                        <a href="/">Hỗ Trợ</a>
                        <a href="/">Cài Đặt</a>
                        <a href="/">Điều Khoản</a>
                    </div>
                    <div class="game-footer__nav">
                        <router-link to="/bxh">Đua Top</router-link>
                        <router-link to="/giftcode">Giftcode</router-link>
                        <router-link to="/nap-atm">Nạp Tiền</router-link>
                    </div>
                    <div class="game-footer__socials">
                        <a
                            href="https://zalo.me/g/tkdeeb069"
                            class="social-btn social-btn--zalo"
                            >Zalo</a
                        >
                        <a
                            href="https://www.facebook.com/groups/1444219976744071/"
                            class="social-btn social-btn--fb"
                            >Facebook</a
                        >
                    </div>
                </div>
                <div class="game-footer__right">
                    <p>HOTLINE: 036*****89</p>
                    <p>EMAIL: vkien29****@gmail.com</p>
                    <img
                        src="/assets/frontend/home/v1/images/18_new.png"
                        alt="18+"
                        class="game-footer__age"
                    />
                </div>
            </div>
            <div class="game-footer__bottom">
                <p>
                    © 2026 Ngọc Rồng HDPE — Website Phát Triển By
                    <strong>Vkien</strong>
                </p>
            </div>
            <img
                src="/assets/frontend/home/v1/images/goku.png"
                alt=""
                class="game-footer__char"
            />
        </footer>

        <!-- SIDEBAR RIGHT -->
        <div
            class="sidebar_right hidden__mobile"
            :class="{ mo: sidebarOpen }"
            style="top: 35%"
        >
            <div class="sidebar_right-content tCenter">
                <img
                    src="/assets/frontend/home/v1/images/sibarRight/qr.png"
                    alt=""
                    class="icon-right"
                />
                <div class="tCenter t-lineok">
                    <img
                        src="/assets/frontend/home/v1/images/sibarRight/line.png"
                        alt=""
                        class="line"
                    />
                </div>
                <div class="clickGet m__inline">
                    <a
                        href="https://zalo.me/g/tkdeeb069"
                        class="a100 f-tahomabold tCenter tUpper dFlex aCenter jCenter"
                        >Nhóm Zalo</a
                    >
                </div>
                <div class="clickGet m__inline">
                    <a
                        href="https://www.facebook.com/groups/1444219976744071/"
                        class="a100 f-tahomabold tCenter tUpper dFlex aCenter jCenter"
                        >Nhóm Facebook</a
                    >
                </div>
                <div class="clickGet m__inline">
                    <router-link
                        to="/nap-atm"
                        class="a100 f-tahomabold tCenter tUpper dFlex aCenter jCenter"
                        >Nạp Tiền</router-link
                    >
                </div>
                <div class="go-top" @click="scrollTop">
                    <img
                        src="/assets/frontend/home/v1/images/sibarRight/top.png"
                        alt=""
                    />
                </div>
            </div>
            <span
                class="ctFixRight dFlex aCenter jCenter"
                @click="toggleSidebar"
            >
                <img
                    src="/assets/frontend/home/v1/images/sibarRight/img-arrow.png"
                    class="imgCtr"
                />
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: "App",
    data() {
        return {
            menuOpen: false,
            sidebarOpen: false,
            scrolled: false,
            loggedIn: !!localStorage.getItem("token"),
        };
    },
    computed: {
        isLoggedIn() {
            return this.loggedIn;
        },
        username() {
            try {
                return (
                    JSON.parse(localStorage.getItem("user") || "{}").username ||
                    "Tài khoản"
                );
            } catch {
                return "Tài khoản";
            }
        },
        isHome() {
            return this.$route.path === "/";
        },
    },
    watch: {
        $route() {
            this.menuOpen = false;
        },
    },
    mounted() {
        this._onScroll = () => {
            this.scrolled = window.scrollY > 30;
        };
        window.addEventListener("scroll", this._onScroll, { passive: true });
        this._onScroll();

        this._onAuthChanged = () => {
            this.loggedIn = !!localStorage.getItem("token");
        };
        window.addEventListener("auth-changed", this._onAuthChanged);
    },
    beforeUnmount() {
        window.removeEventListener("scroll", this._onScroll);
        window.removeEventListener("auth-changed", this._onAuthChanged);
    },
    methods: {
        logout() {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            window.dispatchEvent(new Event("auth-changed"));
            this.$router.push("/");
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        scrollTop() {
            window.scrollTo({ top: 0, behavior: "smooth" });
        },
    },
};
</script>
