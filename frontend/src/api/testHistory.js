import apiClient from './client';

export const testHistoryApi = {
    getAll(params = {}) {
        return apiClient.get('/test_histories', { params }).then(res => res.data)
    },
    getOne(id) {
        return apiClient.get(`/test_histories/${id}`).then(res => res.data);
    },
};