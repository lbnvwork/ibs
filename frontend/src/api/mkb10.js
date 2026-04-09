import apiClient from './client';

export const mkb10Api = {
    getPopular() {
        return apiClient.get('/mkb10/popular').then(res => res.data);
    },
    search(query) {
        return apiClient.get('/mkb10/search', { params: { q: query, itemsPerPage: 20 } }).then(res => res.data);
    },
};