import { createApp } from "vue";
import "@fortawesome/fontawesome-free/css/all.min.css";
import AdminApp from "./admin/App.vue";
import router from "./admin/router";
import axios from "axios";
import AdminIcon from "./admin/components/AdminIcon.vue";
import { createAutoDismissMessages } from "./shared/autoDismissMessages";

axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

const app = createApp(AdminApp);
app.config.globalProperties.$http = axios;
app.mixin(createAutoDismissMessages({ delay: 4600 }));
app.component("AdminIcon", AdminIcon);
app.use(router);
app.mount("#admin-app");
