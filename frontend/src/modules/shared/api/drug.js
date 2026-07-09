import apiClient from './client';

export const drugApi = {
    getAll(params = {}) {
        return apiClient.get('/drugs', { params }).then(res => res.data);
    },
};