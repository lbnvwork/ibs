import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './assets/style.css';
import bakulevoLogo from './assets/logos/bakulevo.png';
import almazovoLogo from './assets/logos/almazovo.png';
import { useAuthStore } from '@/modules/shared/stores/authStore';

// build-time выбор темы через VITE_THEME (СЦ-3.58.6: неизвестное значение → fallback almazovo).
// Статическая проверка (не runtime-флаг), чтобы Vite бандлил только выбранную тему.
const isBakulevo = import.meta.env.VITE_THEME === 'bakulevo';

if (isBakulevo) {
  import('./themes/bakulevo.css');
} else {
  import('./themes/almazovo.css');
}

// Тема-зависимые метаданные браузера: title вкладки + favicon.
document.title = isBakulevo
  ? 'Coag Analyzer'
  : 'МАКТ — Менеджмент антикоагулянтной терапии';
const favicon = document.querySelector('link[rel="icon"]');
if (favicon) {
  favicon.setAttribute('href', isBakulevo ? bakulevoLogo : almazovoLogo);
}

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const authStore = useAuthStore();
authStore.initAuth();

app.mount('#app');