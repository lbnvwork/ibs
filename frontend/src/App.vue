<template>
  <div class="app">
    <template v-if="!isLoginPage">
      <Sidebar />
      <PatientListPanel />
    </template>
    <div class="main" :class="{ 'full-width': isLoginPage }" role="main">
      <MainHeader v-if="!isLoginPage" />
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import Sidebar from './components/Sidebar/Sidebar.vue';
import PatientListPanel from './components/PatientListPanel/PatientListPanel.vue';
import MainHeader from './components/MainHeader.vue';

export default {
  name: 'App',
  components: {
    Sidebar,
    PatientListPanel,
    MainHeader
  },
  computed: {
    isLoginPage() {
      return this.$route.meta.isLoginPage === true;
    }
  }
}
</script>

