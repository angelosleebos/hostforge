import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import '../css/app.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Enable dark mode by default
document.documentElement.classList.add('dark');

app.mount('#app');
