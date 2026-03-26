import apiClient from './client';

export const testHistoryApi = {
    getAll(params = {}) {
        return apiClient.get('/test_histories', { params }).then(res => res.data)
    },
    getLatestByTreatments(treatmentIds) {
        return apiClient.get('/test_histories/latest', {
            params: { treatment: treatmentIds }
        }).then(res => res.data.member || res.data);
    },
    getOne(id) {
        return apiClient.get(`/test_histories/${id}`).then(res => res.data);
    },
};