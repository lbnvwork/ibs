<template>
  <div class="app">
    <Sidebar />
    <PatientListPanel />
    <div class="main" role="main">
      <MainHeader />
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import Sidebar from './components/Sidebar.vue'
import PatientListPanel from './components/PatientListPanel/PatientListPanel.vue'
import MainHeader from './components/MainHeader.vue'

export default {
  name: 'App',
  components: {
    Sidebar,
    PatientListPanel,
    MainHeader
  }
}
</script>
