import { createRouter, createWebHistory } from "vue-router";
import HomePage from "../pages/HomePage.vue";

const routes = [
    {
        path: "/",
        name: "home",
        component: HomePage,
    },
    {
        path: "/bxh",
        name: "bxh",
        component: () => import("../pages/BxhPage.vue"),
    },
    {
        path: "/giftcode",
        name: "giftcode",
        component: () => import("../pages/GiftcodePage.vue"),
    },
    {
        path: "/login",
        name: "login",
        component: () => import("../pages/LoginPage.vue"),
    },
    {
        path: "/register",
        name: "register",
        component: () => import("../pages/RegisterPage.vue"),
    },
    {
        path: "/profile",
        name: "profile",
        component: () => import("../pages/ProfilePage.vue"),
    },
    {
        path: "/nap-atm",
        name: "topup-atm",
        component: () => import("../pages/TopupAtmPage.vue"),
    },
    {
        path: "/nap-card",
        name: "topup-card",
        component: () => import("../pages/TopupCardPage.vue"),
    },
    {
        path: "/post/:slug",
        name: "post-detail",
        component: () => import("../pages/PostDetailPage.vue"),
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

export default router;
