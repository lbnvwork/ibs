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
    getBatch(patientIds) {
        return apiClient.get('/patient_vitals_latests/batch', {
            params: { 'patient_id[]': patientIds }
        });
    },
    getByTreatment(treatmentIri) {
        return apiClient.get('/patient_vitals', {
            params: {
                treatment: treatmentIri,
                order: { recordDt: 'asc' },
                itemsPerPage: 1000
            }
        });
    }
};