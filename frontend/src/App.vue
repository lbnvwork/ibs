<template>
  <div class="app">
    <template v-if="!isLoginPage">
      <Sidebar />
      <PatientListPanel v-if="showPatientListPanel" />
    </template>
    <div class="main" :class="{ 'full-width': isLoginPage }" role="main">
      <MainHeader v-if="!isLoginPage" />
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import { isBakulevo, FEATURES } from '@/themes';
import Sidebar from '@/modules/shared/layout/Sidebar/Sidebar.vue';
import SidebarBakulevo from '@/modules/shared/layout/Sidebar/bakulevo/Sidebar.vue';
import PatientListPanel from '@/modules/patientManagement/components/PatientListPanel/PatientListPanel.vue';
import MainHeader from '@/modules/shared/layout/MainHeader.vue';

export default {
  name: 'App',
  components: {
    Sidebar: isBakulevo ? SidebarBakulevo : Sidebar,
    PatientListPanel,
    MainHeader
  },
  computed: {
    isLoginPage() {
      return this.$route.meta.isLoginPage === true;
    },
    showPatientListPanel() {
      return FEATURES.patientListPanel;
    }
  }
}
</script>

