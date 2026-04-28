import { createRouter, createWebHistory } from "vue-router";

const pageLoaders = {
    home: () => import("../pages/HomePage.vue"),
    bxh: () => import("../pages/BxhPage.vue"),
    giftcode: () => import("../pages/GiftcodePage.vue"),
    login: () => import("../pages/LoginPage.vue"),
    register: () => import("../pages/RegisterPage.vue"),
    profile: () => import("../pages/ProfilePage.vue"),
    topupAtm: () => import("../pages/TopupAtmPage.vue"),
    topupCard: () => import("../pages/TopupCardPage.vue"),
    postDetail: () => import("../pages/PostDetailPage.vue"),
};

const loadedPages = new Map();

function loadPage(key) {
    if (!loadedPages.has(key)) {
        loadedPages.set(key, pageLoaders[key]());
    }
    return loadedPages.get(key);
}

export function prefetchPages(keys) {
    keys.forEach((key) => {
        if (pageLoaders[key]) {
            loadPage(key).catch(() => loadedPages.delete(key));
        }
    });
}

const routes = [
    {
        path: "/",
        name: "home",
        component: () => loadPage("home"),
    },
    {
        path: "/bxh",
        name: "bxh",
        component: () => loadPage("bxh"),
    },
    {
        path: "/giftcode",
        name: "giftcode",
        component: () => loadPage("giftcode"),
    },
    {
        path: "/login",
        name: "login",
        component: () => loadPage("login"),
    },
    {
        path: "/register",
        name: "register",
        component: () => loadPage("register"),
    },
    {
        path: "/profile",
        name: "profile",
        component: () => loadPage("profile"),
    },
    {
        path: "/nap-atm",
        name: "topup-atm",
        component: () => loadPage("topupAtm"),
    },
    {
        path: "/nap-card",
        name: "topup-card",
        component: () => loadPage("topupCard"),
    },
    {
        path: "/post/:slug",
        name: "post-detail",
        component: () => loadPage("postDetail"),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to) {
        if (to.hash) {
            return { el: to.hash, behavior: "smooth" };
        }
        return { top: 0 };
    },
});

function emitRouteLoading(loading) {
    if (typeof window === "undefined") return;
    window.dispatchEvent(
        new CustomEvent("route-loading", { detail: { loading } }),
    );
}

router.beforeEach((to, from) => {
    if (to.fullPath !== from.fullPath) {
        emitRouteLoading(true);
    }
});

router.afterEach(() => {
    emitRouteLoading(false);
});

router.onError(() => {
    emitRouteLoading(false);
});

export default router;
