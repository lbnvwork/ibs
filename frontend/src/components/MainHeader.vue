<template>
  <header>
    Warfarin manager
    <div class="user" aria-label="Пользователь">
      {{ userDisplayName }}
      <div class="user-icon" aria-hidden="true">👤</div>
      <button @click="logout" class="logout-button" title="Выйти">🚪</button>
    </div>
  </header>
</template>

<script>
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';

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
  }
}
</script>
<style scoped>
.logout-button {
  background: none;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  margin-left: 10px;
  padding: 0 5px;
  border-radius: 4px;
  transition: background 0.2s;
}

.logout-button:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>
