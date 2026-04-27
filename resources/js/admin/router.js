import { createRouter, createWebHistory } from "vue-router";
import LoginPage from "./pages/LoginPage.vue";
import DashboardPage from "./pages/DashboardPage.vue";

const routes = [
    {
        path: "/admin/login",
        name: "admin.login",
        component: LoginPage,
        meta: { guest: true },
    },
    {
        path: "/admin",
        name: "admin.dashboard",
        component: DashboardPage,
        meta: { auth: true },
    },
    {
        path: "/admin/accounts",
        name: "admin.accounts",
        component: () => import("./pages/AccountsPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/accounts/create",
        name: "admin.accounts.create",
        component: () => import("./pages/AccountFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/accounts/:id/edit",
        name: "admin.accounts.edit",
        component: () => import("./pages/AccountFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/giftcodes",
        name: "admin.giftcodes",
        component: () => import("./pages/GiftcodesPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/giftcodes/create",
        name: "admin.giftcodes.create",
        component: () => import("./pages/GiftcodeFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/giftcodes/:id/edit",
        name: "admin.giftcodes.edit",
        component: () => import("./pages/GiftcodeFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/items",
        name: "admin.items",
        component: () => import("./pages/ItemsPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/milestones/:type",
        name: "admin.milestones",
        component: () => import("./pages/MilestonesPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/milestones/:type/create",
        name: "admin.milestones.create",
        component: () => import("./pages/MilestoneFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/milestones/:type/:id/edit",
        name: "admin.milestones.edit",
        component: () => import("./pages/MilestoneFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/shops",
        name: "admin.shops",
        component: () => import("./pages/ShopsPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/shops/tab/:tabId/edit",
        name: "admin.shops.tab.edit",
        component: () => import("./pages/ShopTabFormPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/bosses",
        name: "admin.bosses",
        component: () => import("./pages/BossesPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/map-mobs",
        name: "admin.map_mobs",
        component: () => import("./pages/MapMobsPage.vue"),
        meta: { auth: true },
    },
    {
        path: "/admin/admin-logs",
        name: "admin.logs",
        component: () => import("./pages/AdminLogsPage.vue"),
        meta: { auth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const CHUNK_RELOAD_KEY = "admin_chunk_reload_once";

function emitAdminRouteLoading(loading) {
    if (typeof window === "undefined") return;
    window.dispatchEvent(
        new CustomEvent("admin-route-loading", { detail: { loading } }),
    );
}

router.beforeEach(async (to, from, next) => {
    if (to.fullPath !== from.fullPath) {
        emitAdminRouteLoading(true);
    }

    // Check if route needs auth
    if (to.meta.auth) {
        try {
            const res = await fetch("/admin/api/me", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!res.ok) {
                return next({ name: "admin.login" });
            }
        } catch {
            return next({ name: "admin.login" });
        }
    }

    // Redirect logged-in users away from login page
    if (to.meta.guest) {
        try {
            const res = await fetch("/admin/api/me", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (res.ok) {
                return next({ name: "admin.dashboard" });
            }
        } catch {
            // not logged in, continue
        }
    }

    next();
});

router.afterEach(() => {
    emitAdminRouteLoading(false);
});

router.onError((error, to) => {
    emitAdminRouteLoading(false);

    const msg = String(error?.message || "");
    const isChunkError =
        /Failed to fetch dynamically imported module/i.test(msg) ||
        /Importing a module script failed/i.test(msg) ||
        /error loading dynamically imported module/i.test(msg) ||
        /Loading chunk [\d]+ failed/i.test(msg);

    if (!isChunkError) return;

    const now = Date.now();
    const last = Number(sessionStorage.getItem(CHUNK_RELOAD_KEY) || 0);
    if (!last || now - last > 15000) {
        sessionStorage.setItem(CHUNK_RELOAD_KEY, String(now));
        window.location.assign(to?.fullPath || window.location.href);
    }
});

export default router;
