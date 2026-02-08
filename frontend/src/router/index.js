import { createRouter, createWebHistory } from 'vue-router'
import PatientMonitoring from '../components/PatientMonitoring/PatientMonitoring.vue'
import PatientHistory from '../components/PatientHistory/PatientHistory.vue'

const routes = [
  { 
    path: '/', 
    name: 'Home',
    component: PatientMonitoring 
  },
  { 
    path: '/patient/:id',
    name: 'PatientHistory', 
    component: PatientHistory,
    props: true
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
