import apiClient from './client';

export const treatmentApi = {
    getAll(params = {}) {
        return apiClient.get('/treatments', { params }).then(res => res.data);
    },
    getOne(id) {
        return apiClient.get(`/treatments/${id}`).then(res => res.data);
    },
};