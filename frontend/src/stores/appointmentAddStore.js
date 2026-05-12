import { defineStore } from 'pinia';

export const useAppointmentAddStore = defineStore('appointmentAdd', {
  state: () => ({
    isModalOpen: false,
    isTreatmentActive: false,
  }),
  actions: {
    openModal() {
      this.isModalOpen = true;
    },
    closeModal() {
      this.isModalOpen = false;
    },
    setTreatmentActive(value) {
      this.isTreatmentActive = value;
    },
  },
});