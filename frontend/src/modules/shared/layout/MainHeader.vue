<template>
  <header>
    <span class="page-title">{{ pageTitle }}</span>
    <div class="user" aria-label="Пользователь">
      {{ userDisplayName }}
      <div class="user-icon" aria-hidden="true">👤</div>
      <button @click="logout" class="logout-button" title="Выйти">🚪</button>
    </div>
  </header>
</template>

<script>
import { useAuthStore } from '@/modules/shared/stores/authStore';
import { storeToRefs } from 'pinia';

const ROUTE_TITLES = {
  Home: 'Пациенты',
  MedicalHistory: 'История пациента',
  PatientAdd: 'Новый пациент',
  TreatmentAdd: 'Назначение терапии'
};

export default {
  name: 'MainHeader',
  setup() {
    const authStore = useAuthStore();
    const { userDisplayName } = storeToRefs(authStore);
    const logout = () => {
      authStore.logout();
    };

    return {
      userDisplayName,
      logout
    };
  },
  computed: {
    pageTitle() {
      return ROUTE_TITLES[this.$route.name] || 'Warfarin manager';
    }
  }
}
</script>
<style scoped>
.logout-button {
  background: none;
  border: none;
  color: var(--color-header-text);
  font-size: 20px;
  cursor: pointer;
  margin-left: 10px;
  padding: 0 5px;
  border-radius: var(--radius-sm);
  transition: background 0.2s;
}

.logout-button:hover {
  background: var(--color-header-hover);
}
</style>

