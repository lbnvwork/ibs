import apiClient from './client';

export const pharmacogeneticsApi = {
    getForPatient(patientId, config = {}) {
        return apiClient.get(`/patients/${patientId}/pharmacogenetics`, config);
    },
    createResult(data) {
        return apiClient.post('/patient_genetic_results', data);
    },
    updateResult(id, data) {
        return apiClient.put(`/patient_genetic_results/${id}`, data);
    },
    deleteResult(id) {
        return apiClient.delete(`/patient_genetic_results/${id}`);
    }
};