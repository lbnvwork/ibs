<template>
  <div class="sidebar" aria-label="Navigation sidebar">
    <button 
      :title="backButtonTitle"
      :class="{ 'disabled-button': !isPatientHistory }"
      @click="handleBackButton"
    >
      ↶
    </button>
    
    <template v-for="(item, index) in sidebarItems" :key="index">
      <button 
        v-if="item.type === 'button'" 
        :title="item.title"
        @click="handleButtonClick(item.title)"
      >
        {{ item.icon }}
      </button>
      <div v-else-if="item.type === 'divider'" class="divider"></div>
    </template>
  </div>
</template>

<script>
export default {
  name: 'Sidebar',
  data() {
    return {
      sidebarItems: [
        { title: "Добавить в систему пациента или ЛПУ", icon: "👥", type: "button" },
        { title: "Сформировать рекомендации пациенту", icon: "📋", type: "button" },
        { title: "Отправить сообщение пациенту или группе пациентов", icon: "✉️", type: "button" },
        { title: "Редактирование данных ЛПУ или пациента", icon: "✏️", type: "button" },
        
        { type: "divider" },
        
        { title: "Календарь планировщик пациентов", icon: "📅", type: "button" },
        { title: "Помощь искусственного интеллекта", icon: "🤖", type: "button" },
        { title: "Статанализ данных", icon: "📊", type: "button" },
        
        { type: "divider" },
        
        { title: "Список маломобильных пациентов", icon: "♿", type: "button" },
        { title: "Вход в чат пациентов", icon: "💬", type: "button" },
        { title: "Печать из выделенного окна", icon: "🖨️", type: "button" },
        { title: "Сохранить данные в различных форматах", icon: "💾", type: "button" }
      ]
    }
  },
  computed: {
    isPatientHistory() {
      return this.$route && this.$route.path.startsWith('/patient/')
    },
    backButtonTitle() {
      return this.isPatientHistory 
        ? 'Вернуться к списку пациентов' 
        : 'Отмена действия, возврат'
    }
  },
  methods: {
    handleBackButton() {
      if (this.isPatientHistory && this.$router) {
        this.$router.push('/')
      }
    }
  }
}
</script>