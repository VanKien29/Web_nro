import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { createAutoDismissMessages } from "./shared/autoDismissMessages";

const app = createApp(App);
app.mixin(createAutoDismissMessages({ delay: 4600 }));
app.use(router);
app.mount("#app");
