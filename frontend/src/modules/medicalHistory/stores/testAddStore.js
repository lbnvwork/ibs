import { defineStore } from 'pinia';

// Стор открытия формы «Добавление анализа» (TestAddModal) — по аналогии с appointmentAddStore.
// Позволяет сайдбару (sibling-компонент в App.vue) открывать модалку на странице истории
// пациента (MedicalHistory) через общий флаг, а не через локальное состояние компонента.
export const useTestAddStore = defineStore('testAdd', {
  state: () => ({
    isModalOpen: false,
  }),
  actions: {
    openModal() {
      this.isModalOpen = true;
    },
    closeModal() {
      this.isModalOpen = false;
    },
  },
});
