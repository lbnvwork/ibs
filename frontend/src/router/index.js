import { createRouter, createWebHistory } from 'vue-router';
import Home from '@/modules/shared/layout/Home/Home.vue';
import PatientHistory from '@/modules/patientCard/components/PatientHistory/PatientHistory.vue';
import Login from '@/modules/shared/components/Login/Login.vue';
import { useAuthStore } from '@/modules/shared/stores/authStore';
import PatientAdd from '@/modules/patientManagement/components/PatientAdd/PatientAdd.vue';
import TreatmentAdd from '@/modules/patientManagement/components/TreatmentAdd/TreatmentAdd.vue';
import { 
  HOME_PATH, 
  LOGIN_PATH, 
  PATIENT_ADD_PATH, 
  PATIENT_HISTORY_TEMPLATE, 
  TREATMENT_ADD_TEMPLATE 
} from './paths';

const routes = [
  {
    path: LOGIN_PATH,
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false, isLoginPage: true }
  },
  { 
    path: HOME_PATH, 
    name: 'Home',
    component: Home,
    meta: { requiresAuth: true, backTarget: null }
  },
  { 
    path: PATIENT_HISTORY_TEMPLATE,
    name: 'PatientHistory', 
    component: PatientHistory,
    props: (route) => ({ id: route.params.patientId }),
    meta: { requiresAuth: true, backTarget: HOME_PATH }
  },
  {
      path: PATIENT_ADD_PATH,
      name: 'PatientAdd',
      component: PatientAdd,
      meta: { requiresAuth: true, backTarget: HOME_PATH }
  },
  {
      path: TREATMENT_ADD_TEMPLATE,
      name: 'TreatmentAdd',
      component: TreatmentAdd,
      props: true,
      meta: { requiresAuth: true, backTarget: HOME_PATH }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from) => {
    const authStore = useAuthStore();
    const isAuthenticated = authStore.isAuthenticated;

    if (to.meta.requiresAuth && !isAuthenticated) {
      return { name: 'Login' };
    }
    if (to.name === 'Login' && isAuthenticated) {
      return { name: 'Home' };
    }

    return true;
});

export default router;