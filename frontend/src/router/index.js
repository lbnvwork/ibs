import { createRouter, createWebHistory } from 'vue-router';
import PatientMonitoring from '../components/PatientMonitoring/PatientMonitoring.vue';
import PatientHistory from '../components/PatientHistory/PatientHistory.vue';
import Login from '../components/Login/Login.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false, isLoginPage: true }
  },
  { 
    path: '/', 
    name: 'Home',
    component: PatientMonitoring,
    meta: { requiresAuth: true }
  },
  { 
    path: '/patient/:id',
    name: 'PatientHistory', 
    component: PatientHistory,
    props: true,
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  import('../stores/authStore').then(({ useAuthStore }) => {
    const authStore = useAuthStore();
    const isAuthenticated = authStore.isAuthenticated;
    if (to.meta.requiresAuth && !isAuthenticated) {
      next({ name: 'Login' });
    } else if (to.name === 'Login' && isAuthenticated) {
      next({ name: 'Home' });
    } else {
      next();
    }
  });
});

export default router;