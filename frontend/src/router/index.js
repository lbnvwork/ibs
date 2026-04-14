import { createRouter, createWebHistory } from 'vue-router';
import Home from '@/components/Home/Home.vue';
import PatientHistory from '../components/PatientHistory/PatientHistory.vue';
import Login from '../components/Login/Login.vue';
import { useAuthStore } from '../stores/authStore';
import PatientAdd from '@/components/PatientAdd/PatientAdd.vue';

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
    component: Home,
    meta: { requiresAuth: true, backTarget: null }
  },
  { 
    path: '/patient/:id',
    name: 'PatientHistory', 
    component: PatientHistory,
    props: true,
    meta: { requiresAuth: true, backTarget: '/' }
  },
  {
      path: '/patient/add',
      name: 'PatientAdd',
      component: PatientAdd,
      meta: { requiresAuth: true, backTarget: '/' }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
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

export default router;