import { defineStore } from 'pinia';
import { vitalsApi } from '@/modules/shared/api/vitals';

export const usePatientVitalsLatestStore = defineStore('patientVitalsLatest', {
    state: () => ({
        latest: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchLatest(patientId) {
            if (!patientId) return;
            this.loading = true;
            this.error = null;
            try {
                const { data } = await vitalsApi.getLatest(patientId);
                const items = data['member'] || data['items'] || data;
                this.latest = Array.isArray(items) ? items[0] || null : items;
            } catch (err) {
                console.error('Ошибка загрузки витальных показателей:', err);
                this.error = 'Не удалось загрузить данные.';
                this.latest = null;
            } finally {
                this.loading = false;
            }
        },

        async saveMeasurement(payload) {
            const { data } = await vitalsApi.create(payload);
            return data;
        },
    },
});