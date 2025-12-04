import { createApp } from 'vue';
import { createPinia } from 'pinia';
import adminRouter from './admin/router';
import AdminApp from './admin/AdminApp.vue';
import '../css/app.css';

const app = createApp(AdminApp);
const pinia = createPinia();

app.use(pinia);
app.use(adminRouter);

// Enable dark mode by default
document.documentElement.classList.add('dark');

app.mount('#admin-app');
