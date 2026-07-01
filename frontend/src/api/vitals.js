import apiClient from './client';

export const vitalsApi = {
    /**
     * Получить последние витальные показатели пациента.
     * @param {string|number} patientId
     * @returns {Promise}
     */
    getLatest(patientId) {
        return apiClient.get(`/patient_vitals_latest/${patientId}`);
    },

    /**
     * Создать новое измерение витальных показателей.
     * @param {object} data – тело запроса (patient, treatment, recordDt, hb, ...)
     * @returns {Promise}
     */
    create(data) {
        return apiClient.post('/patient_vitals', data);
    },
};