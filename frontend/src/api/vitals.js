import apiClient from './client';

export const vitalsApi = {
    create(data) {
        return apiClient.post('/patient_vitals', data);
    },
    getLatest(patientId) {
        return apiClient.get('/patient_vitals_latests', {
            params: { patient: `/api/patients/${patientId}` }
        });
    },
};