import { defineStore } from 'pinia';

export const useAppointmentAddStore = defineStore('appointmentAdd', {
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