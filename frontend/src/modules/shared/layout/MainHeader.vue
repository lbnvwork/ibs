<template>
  <header class="main-header">
    <span v-if="!isBakulevo" class="page-title">{{ pageTitle }}</span>

    <div v-if="!isBakulevo" class="user" aria-label="Пользователь">
      {{ userDisplayName }}
      <div class="user-icon" aria-hidden="true">👤</div>
      <button @click="logout" class="logout-button" title="Выйти">🚪</button>
    </div>

    <!-- Бакулево: заголовка нет, справа — блок врача с раскрывашкой (в ней «Выйти») -->
    <div v-else class="doctor-block" @click.stop>
      <button
        class="doctor-toggle"
        aria-haspopup="true"
        :aria-expanded="doctorMenuOpen ? 'true' : 'false'"
        @click="toggleDoctorMenu"
      >
        <span class="doctor-avatar" aria-hidden="true">{{ initials }}</span>
        <span class="doctor-meta">
          <span class="doctor-name">{{ userDisplayName }}</span>
          <span class="doctor-role">{{ doctorRole }}</span>
        </span>
        <svg
          class="doctor-chevron"
          :class="{ 'doctor-chevron--open': doctorMenuOpen }"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" fill="none"/>
        </svg>
      </button>
      <div v-if="doctorMenuOpen" class="doctor-menu">
        <button class="doctor-menu__item" @click="logout">Выйти</button>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/modules/shared/stores/authStore';
import { storeToRefs } from 'pinia';
import { isBakulevo } from '@/themes';

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
    const doctorMenuOpen = ref(false);

    const initials = computed(() => {
      const name = (userDisplayName.value || '').trim();
      const parts = name.split(/\s+/).filter(Boolean);
      const first = parts[0]?.charAt(0) || '';
      const last = parts[1]?.charAt(0) || '';
      return (first + last).toUpperCase() || 'В';
    });

    const doctorRole = computed(() => authStore.user?.role || authStore.user?.position || 'Врач');

    const toggleDoctorMenu = () => { doctorMenuOpen.value = !doctorMenuOpen.value; };

    const closeDoctorMenu = () => { doctorMenuOpen.value = false; };
    const onDocClick = (e) => {
      if (!e.target.closest('.doctor-block')) closeDoctorMenu();
    };
    onMounted(() => document.addEventListener('click', onDocClick));
    onBeforeUnmount(() => document.removeEventListener('click', onDocClick));

    const logout = () => { authStore.logout(); };

    return {
      userDisplayName,
      isBakulevo,
      initials,
      doctorRole,
      doctorMenuOpen,
      toggleDoctorMenu,
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

/* ---------- Блок врача (Бакулево) ---------- */
.doctor-block {
  margin-left: auto;
  display: flex;
  align-items: center;
  position: relative;
}

.doctor-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px 10px;
  border-radius: var(--radius-md);
  color: var(--color-header-text);
}

.doctor-toggle:hover {
  background: var(--color-header-hover);
}

.doctor-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--color-accent);
  color: var(--color-on-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
}

.doctor-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  line-height: 1.2;
}

.doctor-name {
  font-weight: 700;
  font-size: 14px;
}

.doctor-role {
  font-size: 12px;
  color: var(--color-muted);
}

.doctor-chevron {
  width: 16px;
  height: 16px;
  color: var(--color-muted);
  transition: transform 0.2s ease;
}

.doctor-chevron--open {
  transform: rotate(180deg);
}

.doctor-menu {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  min-width: 160px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  padding: 6px;
  z-index: 30;
}

.doctor-menu__item {
  display: block;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  color: var(--color-text);
  font-size: 14px;
}

.doctor-menu__item:hover {
  background: var(--color-surface-alt);
}
</style>

