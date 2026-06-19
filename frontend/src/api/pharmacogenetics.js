import apiClient from './client';

export const pharmacogeneticsApi = {
    getForPatient(patientId) {
        return apiClient.get(`/patients/${patientId}/pharmacogenetics`);
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