import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    { path: '/', component: () => import('../views/HomeView.vue') },
    { path: '/patient', component: () => import('../views/PatientView.vue') },
    // { path: '/doctor', component: () => import('../views/DoctorView.vue') },
    // { path: '/admin', component: () => import('../views/AdminView.vue') },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
