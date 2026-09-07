import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './assets/style.css';
// build-time выбор темы через VITE_THEME (СЦ-3.58.6: неизвестное значение → fallback almazovo).
// Статические пути импорта, чтобы Vite бандлил только выбранную тему.
if (import.meta.env.VITE_THEME === 'bakulevo') {
  import('./themes/bakulevo.css');
} else {
  import('./themes/almazovo.css');
}
import { useAuthStore } from '@/modules/shared/stores/authStore';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const authStore = useAuthStore();
authStore.initAuth();

app.mount('#app');