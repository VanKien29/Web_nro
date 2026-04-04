import { createApp } from "vue";
import AdminApp from "./admin/App.vue";
import router from "./admin/router";
import axios from "axios";

axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

const app = createApp(AdminApp);
app.config.globalProperties.$http = axios;
app.use(router);
app.mount("#admin-app");
