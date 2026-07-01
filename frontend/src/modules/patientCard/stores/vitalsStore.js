import { defineStore } from 'pinia';
import { vitalsApi } from '@/api/vitals';

export const usePatientVitalsLatestStore = defineStore('patientVitalsLatest', {
    state: () => ({
        latest: null,
        loading: false,
        error: null,
    }),

    actions: {
        /**
         * Загрузить последние витальные показатели для пациента.
         * @param {string|number} patientId
         */
        async fetchLatest(patientId) {
            if (!patientId) return;
            this.loading = true;
            this.error = null;
            try {
                const { data } = await vitalsApi.getLatest(patientId);
                this.latest = data;
            } catch (err) {
                console.error('Ошибка загрузки витальных показателей:', err);
                this.error = 'Не удалось загрузить витальные показатели.';
                this.latest = null;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Сохранить новое измерение и вернуть ответ API.
         * @param {object} payload – готовый объект для POST
         * @returns {Promise<object>}
         */
        async saveMeasurement(payload) {
            const { data } = await vitalsApi.create(payload);
            return data;
        },
    },
});