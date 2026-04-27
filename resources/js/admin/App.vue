<template>
    <div class="admin-app" :class="layoutClass">
        <!-- Login page — no layout -->
        <template v-if="$route.meta.guest">
            <router-view />
        </template>

        <!-- Authenticated layout -->
        <template v-else>
            <!-- Sidebar -->
            <aside id="miniSidebar">
                <div class="brand-logo">
                    <router-link to="/admin" class="brand-link">
                        <div class="brand-icon">N</div>
                        <span class="site-logo-text">NRO HDPE</span>
                    </router-link>
                </div>
                <nav class="navbar-nav">
                    <div class="nav-heading">Tổng quan</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.dashboard',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">dashboard</span>
                            <span class="text">Dashboard</span>
                        </router-link>
                    </div>

                    <div class="nav-heading">Quản lý</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin/accounts"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith(
                                    'admin.accounts',
                                ),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">people</span>
                            <span class="text">Tài khoản</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/giftcodes"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith(
                                    'admin.giftcodes',
                                ),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">card_giftcard</span>
                            <span class="text">Giftcode</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/items"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.items' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">inventory_2</span>
                            <span class="text">Items</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/shops"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith('admin.shops'),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">storefront</span>
                            <span class="text">Shop</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/milestones/moc_nap"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith(
                                    'admin.milestones',
                                ),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">emoji_events</span>
                            <span class="text">Mốc thưởng</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/bosses"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.bosses' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">sports_martial_arts</span>
                            <span class="text">Boss</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/map-mobs"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.map_mobs',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">terrain</span>
                            <span class="text">Map - Mob</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/admin-logs"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.logs' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">history</span>
                            <span class="text">Nhật ký admin</span>
                        </router-link>
                    </div>
                </nav>
            </aside>

            <!-- Main content -->
            <div id="content">
                <header class="navbar-glass">
                    <div class="topbar-inner">
                        <div class="topbar-left">
                            <button
                                class="topbar-btn"
                                @click="toggleSidebar"
                                title="Toggle sidebar"
                            >
                                <span class="mi collapse-mini">menu</span>
                                <span class="mi collapse-expanded"
                                    >menu_open</span
                                >
                            </button>
                            <button
                                class="topbar-btn"
                                @click="toggleTheme"
                                :title="
                                    theme === 'dark'
                                        ? 'Chế độ sáng'
                                        : 'Chế độ tối'
                                "
                            >
                                <span class="mi">{{
                                    theme === "dark"
                                        ? "light_mode"
                                        : "dark_mode"
                                }}</span>
                            </button>
                        </div>
                        <div class="topbar-right">
                            <div
                                class="topbar-user"
                                @click="showUserMenu = !showUserMenu"
                            >
                                <div class="avatar">
                                    {{
                                        (adminUser?.username ||
                                            "A")[0].toUpperCase()
                                    }}
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        {{ adminUser?.username || "Admin" }}
                                    </div>
                                    <div class="user-role">Administrator</div>
                                </div>
                            </div>
                            <div
                                v-if="showUserMenu"
                                class="user-dropdown"
                                @click.stop
                            >
                                <button class="dropdown-item" @click="logout">
                                    <span class="mi" style="font-size: 18px"
                                        >logout</span
                                    >
                                    Đăng xuất
                                </button>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="page-content">
                    <transition name="fade" mode="out-in">
                        <router-view />
                    </transition>
                </main>
            </div>
        </template>
    </div>
</template>

<script>
export default {
    data() {
        return {
            sidebarCollapsed:
                localStorage.getItem("sidebarCollapsed") === "true",
            theme: localStorage.getItem("adminTheme") || "dark",
            adminUser: null,
            showUserMenu: false,
            scrollLockObserver: null,
            bodyScrollLocked: false,
            lockedScrollY: 0,
        };
    },
    computed: {
        layoutClass() {
            return (
                (this.sidebarCollapsed
                    ? "sidebar-collapsed"
                    : "sidebar-expanded") +
                " theme-" +
                this.theme
            );
        },
    },
    watch: {
        "$route.name"() {
            if (this.$route.meta.auth && !this.adminUser) {
                this.fetchUser();
            }
            this.showUserMenu = false;
        },
    },
    created() {
        if (this.$route.meta.auth) {
            this.fetchUser();
        }
        this.applyBodyBg();
    },
    mounted() {
        document.addEventListener("click", this.closeMenus);
        document.addEventListener("wheel", this.preventScrollBleed, {
            capture: true,
            passive: false,
        });
        this.scrollLockObserver = new MutationObserver(this.updateScrollLock);
        this.scrollLockObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["class", "style"],
        });
        this.updateScrollLock();
    },
    unmounted() {
        document.removeEventListener("click", this.closeMenus);
        document.removeEventListener("wheel", this.preventScrollBleed, {
            capture: true,
        });
        if (this.scrollLockObserver) {
            this.scrollLockObserver.disconnect();
            this.scrollLockObserver = null;
        }
        this.unlockBodyScroll();
    },
    methods: {
        closeMenus(e) {
            if (!e.target.closest(".topbar-right")) {
                this.showUserMenu = false;
            }
        },
        closeMobile() {
            if (window.innerWidth <= 990) {
                this.sidebarCollapsed = true;
                localStorage.setItem("sidebarCollapsed", "true");
            }
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem(
                "sidebarCollapsed",
                String(this.sidebarCollapsed),
            );
        },
        toggleTheme() {
            this.theme = this.theme === "dark" ? "light" : "dark";
            localStorage.setItem("adminTheme", this.theme);
            this.applyBodyBg();
        },
        applyBodyBg() {
            document.body.style.background =
                this.theme === "dark" ? "#0f1418" : "#e7ecef";
        },
        updateScrollLock() {
            const hasFloatingMenu = !!document.querySelector(
                ".admin-app .modal-overlay, .admin-app .picker-overlay, .admin-app .option-dropdown",
            );
            if (hasFloatingMenu) {
                this.lockBodyScroll();
            } else {
                this.unlockBodyScroll();
            }
        },
        lockBodyScroll() {
            if (this.bodyScrollLocked) return;
            this.lockedScrollY = window.scrollY || document.documentElement.scrollTop || 0;
            document.documentElement.classList.add("admin-scroll-lock");
            document.body.classList.add("admin-scroll-lock");
            document.body.style.top = `-${this.lockedScrollY}px`;
            this.bodyScrollLocked = true;
        },
        unlockBodyScroll() {
            if (!this.bodyScrollLocked) return;
            document.documentElement.classList.remove("admin-scroll-lock");
            document.body.classList.remove("admin-scroll-lock");
            document.body.style.top = "";
            window.scrollTo(0, this.lockedScrollY);
            this.bodyScrollLocked = false;
        },
        preventScrollBleed(event) {
            const boundary = event.target?.closest?.(
                ".modal-panel, .picker-panel, .option-dropdown, .user-dropdown, .boss-side, .group-grid, .catalog-list",
            );
            if (!boundary) return;

            const scrollable = this.closestScrollable(event.target, boundary);
            if (!scrollable) {
                event.preventDefault();
                return;
            }

            const deltaY = event.deltaY;
            const atTop = scrollable.scrollTop <= 0;
            const atBottom = Math.ceil(scrollable.scrollTop + scrollable.clientHeight) >= scrollable.scrollHeight;
            if ((deltaY < 0 && atTop) || (deltaY > 0 && atBottom)) {
                event.preventDefault();
            }
        },
        closestScrollable(target, boundary) {
            let node = target;
            while (node && node !== document.body) {
                if (node.nodeType === 1) {
                    const style = window.getComputedStyle(node);
                    const canScrollY = /(auto|scroll)/.test(style.overflowY);
                    if (canScrollY && node.scrollHeight > node.clientHeight + 1) {
                        return node;
                    }
                }
                if (node === boundary) break;
                node = node.parentElement;
            }
            return boundary.scrollHeight > boundary.clientHeight + 1 ? boundary : null;
        },
        async fetchUser() {
            try {
                const res = await fetch("/admin/api/me", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.adminUser = data.user;
                }
            } catch {
                // ignore
            }
        },
        async logout() {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            await fetch("/admin/api/logout", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": token,
                    "Content-Type": "application/json",
                },
            });
            this.adminUser = null;
            this.$router.push({ name: "admin.login" });
        },
    },
};
</script>

<style>
/* ═══════════════════════════════════════
   DASHER-INSPIRED ADMIN THEME (Dark)
   ═══════════════════════════════════════ */

/* ── CSS Variables ── */
:root {
    --ds-primary: #4b9e8b;
    --ds-primary-rgb: 75, 158, 139;
    --ds-primary-lighter: #8fd3c4;
    --ds-primary-darker: #327061;
    --ds-primary-soft: #70b8a8;
    --ds-danger: #d05c5c;
    --ds-danger-rgb: 208, 92, 92;
    --ds-warning: #d5a042;
    --ds-warning-rgb: 213, 160, 66;
    --ds-info: #4aa8b4;
    --ds-info-rgb: 74, 168, 180;
    --ds-success: #58ac74;
    --ds-success-rgb: 88, 172, 116;

    --ds-gray-100: #172029;
    --ds-gray-200: #202c37;
    --ds-gray-300: #2e3c49;
    --ds-gray-400: #5e7488;
    --ds-gray-500: #8093a3;
    --ds-gray-600: #a8b5c0;
    --ds-gray-700: #c4cdd5;
    --ds-gray-800: #dce3e8;
    --ds-gray-900: #edf2f6;

    --ds-body-bg: #0f1418;
    --ds-surface: #151d25;
    --ds-surface-2: #1b2530;
    --ds-border: rgba(94, 116, 136, 0.38);
    --ds-text: #b6c2cd;
    --ds-text-emphasis: #eef3f7;
    --ds-text-muted: #8a9fb2;

    --ds-shadow-xl:
        0 0 2px 0 rgba(0, 0, 0, 0.2), 0 12px 24px -4px rgba(0, 0, 0, 0.12);
    --ds-shadow-sm: 0 4px 8px 0 rgba(0, 0, 0, 0.16);
    --ds-radius: 0.75rem;
    --ds-radius-lg: 1rem;
}

/* ── Reset & Base ── */
.admin-app *,
.admin-app *::before,
.admin-app *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
.admin-app {
    font-family:
        "Inter",
        system-ui,
        -apple-system,
        sans-serif;
    background: var(--ds-body-bg);
    color: var(--ds-text);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}
.admin-app a {
    text-decoration: none;
}
.admin-app a:not(.btn) {
    color: var(--ds-primary);
}
.admin-app a:not(.btn):hover {
    color: var(--ds-primary-lighter);
}

html.admin-scroll-lock,
body.admin-scroll-lock {
    overflow: hidden !important;
}
body.admin-scroll-lock {
    position: fixed;
    left: 0;
    right: 0;
    width: 100%;
}
.admin-app .modal-overlay,
.admin-app .picker-overlay {
    z-index: 3000 !important;
    align-items: flex-start !important;
    justify-content: center !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    padding: 18px !important;
}
.admin-app .modal-panel,
.admin-app .picker-panel {
    max-height: calc(100dvh - 36px) !important;
    overflow: auto !important;
    overscroll-behavior: contain;
    margin: 0 auto;
}
.admin-app .option-dropdown,
.admin-app .user-dropdown,
.admin-app .boss-side,
.admin-app .group-grid,
.admin-app .catalog-list {
    overscroll-behavior: contain;
}

/* Material icon shorthand */
.mi {
    font-family: "Material Icons Round";
    font-weight: normal;
    font-style: normal;
    font-size: 20px;
    display: inline-block;
    line-height: 1;
    text-transform: none;
    letter-spacing: normal;
    word-wrap: normal;
    white-space: nowrap;
    direction: ltr;
    -webkit-font-smoothing: antialiased;
}

/* ═══ SIDEBAR ═══ */
#miniSidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    background: var(--ds-body-bg);
    border-right: 1px dashed var(--ds-border);
    z-index: 1050;
    transition: width 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.brand-logo {
    position: sticky;
    top: 0;
    background: var(--ds-body-bg);
    padding: 0.75rem 1rem;
    z-index: 2;
}
.brand-link {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none !important;
}
.brand-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(
        135deg,
        var(--ds-primary),
        var(--ds-primary-soft)
    );
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.site-logo-text {
    font-size: 16px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.navbar-nav {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 20px;
}
.nav-heading {
    color: var(--ds-text-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 16px 20px 4px;
    text-transform: uppercase;
}
.nav-line {
    border: none;
    border-top: 1px dashed var(--ds-border);
    margin: 4px 12px;
}
.nav-item {
}
.nav-link {
    display: flex;
    align-items: center;
    gap: 0;
    white-space: nowrap;
    overflow: hidden;
    padding: 10px 12px;
    color: var(--ds-text-muted);
    font-weight: 500;
    font-size: 14px;
    margin: 2px 8px;
    border-radius: 8px;
    background: transparent;
    transition: all 0.2s;
    text-decoration: none !important;
    line-height: 1.2;
}
.nav-link:hover {
    color: var(--ds-text);
    background: var(--ds-gray-100);
}
.nav-link.active {
    color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.12);
}
.nav-icon {
    font-size: 20px;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}
.nav-link .text {
    margin-left: 10px;
    font-weight: 500;
    vertical-align: middle;
}

/* ── Collapsed sidebar ── */
.sidebar-collapsed #miniSidebar {
    width: 60px;
}
.sidebar-collapsed #miniSidebar .site-logo-text,
.sidebar-collapsed #miniSidebar .nav-heading,
.sidebar-collapsed #miniSidebar .nav-link .text {
    display: none;
}
.sidebar-collapsed #miniSidebar .nav-line {
    display: block;
}

/* ── Expanded sidebar ── */
.sidebar-expanded #miniSidebar {
    width: 250px;
}
.sidebar-expanded #miniSidebar .nav-line {
    display: none;
}

/* ═══ MAIN CONTENT ═══ */
.sidebar-collapsed #content {
    margin-left: 60px;
    transition: margin-left 0.3s;
}
.sidebar-expanded #content {
    margin-left: 250px;
    transition: margin-left 0.3s;
}
#content {
    min-height: 100vh;
}

/* ═══ TOPBAR ═══ */
.navbar-glass {
    position: sticky;
    top: 0;
    z-index: 1030;
    backdrop-filter: blur(6px);
    background-color: rgba(15, 20, 24, 0.88);
    border-bottom: 1px dashed var(--ds-border);
}
.topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    padding: 0 24px;
}
.topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.topbar-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.topbar-btn:hover {
    background: var(--ds-gray-100);
    color: var(--ds-text);
}
.sidebar-collapsed .collapse-expanded {
    display: none;
}
.sidebar-collapsed .collapse-mini {
    display: block;
}
.sidebar-expanded .collapse-mini {
    display: none;
}
.sidebar-expanded .collapse-expanded {
    display: block;
}

/* ── Avatar / User ── */
.topbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: background 0.2s;
}
.topbar-user:hover {
    background: var(--ds-gray-100);
}
.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(
        135deg,
        var(--ds-primary),
        var(--ds-primary-soft)
    );
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.user-info {
}
.user-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.user-role {
    font-size: 11px;
    color: var(--ds-text-muted);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius);
    box-shadow: var(--ds-shadow-xl);
    min-width: 180px;
    z-index: 50;
    padding: 6px;
}
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: none;
    color: var(--ds-text);
    font-size: 14px;
    font-family: inherit;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}
.dropdown-item:hover {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}

/* ═══ PAGE CONTENT ═══ */
.page-content {
    padding: 24px 32px;
}

/* ═══ CARDS ═══ */
.card {
    background: var(--ds-surface);
    border: 0;
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    padding: 24px;
    margin-bottom: 24px;
}
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.card-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
}

/* ── Stat cards ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--ds-surface);
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 0;
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.stat-icon.primary {
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}
.stat-icon.info {
    background: rgba(var(--ds-info-rgb), 0.16);
    color: var(--ds-info);
}
.stat-icon.warning {
    background: rgba(var(--ds-warning-rgb), 0.16);
    color: var(--ds-warning);
}
.stat-icon.danger {
    background: rgba(var(--ds-danger-rgb), 0.16);
    color: var(--ds-danger);
}
.stat-icon.success {
    background: rgba(var(--ds-success-rgb), 0.16);
    color: var(--ds-success);
}
.stat-title {
    font-size: 13px;
    color: var(--ds-text-muted);
    margin-bottom: 4px;
}
.stat-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}

/* ═══ TABLE ═══ */
.table-wrap {
    overflow-x: auto;
}
.admin-app table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.admin-app th,
.admin-app td {
    padding: 12px 16px;
    text-align: left;
}
.admin-app thead {
    background: var(--ds-gray-100);
}
.admin-app th {
    color: var(--ds-text-muted);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 0;
}
.admin-app td {
    border-bottom: 1px dashed var(--ds-border);
    color: var(--ds-text);
}
.admin-app tr:last-child td {
    border-bottom-width: 0;
}
.admin-app tr:hover td {
    background: rgba(var(--ds-primary-rgb), 0.04);
}

/* ═══ BUTTONS ═══ */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-family: inherit;
    line-height: 1.2;
    white-space: nowrap;
    position: relative;
    pointer-events: auto;
}
.btn > * {
    pointer-events: none;
}
.btn:hover {
    opacity: 0.88;
}
.btn-primary {
    background: var(--ds-primary);
    border-color: var(--ds-primary);
    color: #fff;
}
.btn-success {
    background: var(--ds-success);
    border-color: var(--ds-success);
    color: #fff;
}
.btn-danger {
    background: var(--ds-danger);
    border-color: var(--ds-danger);
    color: #fff;
}
.btn-warning {
    background: var(--ds-warning);
    border-color: var(--ds-warning);
    color: #212b36;
}
.btn-outline {
    background: transparent;
    color: var(--ds-text-emphasis);
    border: 1px solid var(--ds-border);
}
.btn-outline:hover {
    background: var(--ds-gray-100);
    color: var(--ds-text-emphasis);
}
.btn:disabled {
    opacity: 0.56;
    cursor: not-allowed;
}
.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}
.btn-block {
    width: 100%;
}

/* ═══ FORMS ═══ */
.form-group {
    margin-bottom: 18px;
}
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ds-text);
    margin-bottom: 6px;
}
.form-input {
    width: 100%;
    padding: 10px 14px;
    background: var(--ds-body-bg);
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    color: var(--ds-text-emphasis);
    font-size: 14px;
    font-family: inherit;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}
.form-input:focus {
    outline: none;
    border-color: var(--ds-primary);
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.16);
}
.form-input::placeholder {
    color: var(--ds-text-muted);
}

/* ═══ BADGES ═══ */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.badge-success {
    background: rgba(var(--ds-success-rgb), 0.16);
    color: var(--ds-success);
}
.badge-danger {
    background: rgba(var(--ds-danger-rgb), 0.16);
    color: var(--ds-danger);
}
.badge-warning {
    background: rgba(var(--ds-warning-rgb), 0.16);
    color: var(--ds-warning);
}
.badge-info {
    background: rgba(var(--ds-info-rgb), 0.16);
    color: var(--ds-info);
}
.badge-primary {
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}

/* ═══ ALERTS ═══ */
.alert {
    padding: 14px 18px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success {
    background: rgba(var(--ds-success-rgb), 0.12);
    color: var(--ds-success);
    border: 1px solid rgba(var(--ds-success-rgb), 0.2);
}
.alert-error {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
    border: 1px solid rgba(var(--ds-danger-rgb), 0.2);
}

/* ═══ PAGINATION ═══ */
.pagination {
    display: flex;
    gap: 4px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.pagination button {
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 13px;
    border: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text);
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s;
}
.pagination button:hover {
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-primary);
}
.pagination button.active {
    background: var(--ds-primary);
    color: #fff;
    border-color: var(--ds-primary);
}
.pagination button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ═══ TRANSITION ═══ */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ═══ LIGHT THEME ═══ */
.admin-app.theme-light {
    --ds-primary-soft: #8ecdc0;
    --ds-gray-100: #e9eef2;
    --ds-gray-200: #d9e1e8;
    --ds-gray-300: #c0ccd7;
    --ds-gray-400: #77899b;
    --ds-gray-500: #56697b;
    --ds-gray-600: #405364;
    --ds-gray-700: #2c3f50;
    --ds-gray-800: #182b3a;
    --ds-gray-900: #0f2230;

    --ds-body-bg: #e7ecef;
    --ds-surface: #f1f4f6;
    --ds-surface-2: #e7edf1;
    --ds-border: rgba(86, 105, 123, 0.24);
    --ds-text: #3f5364;
    --ds-text-emphasis: #182b3a;
    --ds-text-muted: #6f8194;

    --ds-shadow-xl:
        0 0 2px 0 rgba(116, 129, 142, 0.16),
        0 14px 30px -10px rgba(116, 129, 142, 0.18);
    --ds-shadow-sm: 0 8px 18px -10px rgba(116, 129, 142, 0.22);
}
.admin-app.theme-light .navbar-glass {
    background-color: rgba(231, 236, 239, 0.88);
}
.admin-app.theme-light #miniSidebar {
    background: #edf1f4;
}
.admin-app.theme-light .brand-logo {
    background: #edf1f4;
}
.admin-app.theme-light .nav-link:hover {
    background: rgba(86, 105, 123, 0.08);
}
.admin-app.theme-light .topbar-user:hover,
.admin-app.theme-light .topbar-btn:hover,
.admin-app.theme-light .btn-outline:hover {
    background: rgba(86, 105, 123, 0.08);
}
.admin-app.theme-light tr:hover td {
    background: rgba(86, 105, 123, 0.08);
}

/* ═══ MOBILE ═══ */
@media (max-width: 990px) {
    .sidebar-collapsed #content,
    .sidebar-expanded #content {
        margin-left: 0;
    }
    .sidebar-collapsed #miniSidebar {
        transform: translateX(-100%);
    }
    .sidebar-expanded #miniSidebar {
        width: 250px;
    }
    .page-content {
        padding: 20px 16px;
    }
    .user-info {
        display: none;
    }
}

/* ═══ SCROLLBAR ═══ */
.admin-app ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
    background: transparent;
}
.admin-app ::-webkit-scrollbar-track {
    background: var(--ds-gray-100);
    border-radius: 8px;
}
.admin-app ::-webkit-scrollbar-thumb {
    background: var(--ds-gray-300);
    border-radius: 8px;
}
</style>
