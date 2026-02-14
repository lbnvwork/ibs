import apiClient from './client'
import { extractData, createPaginationParams } from '@/utils/apiHelpers'

export const patientApi = {
    getAll(page = 1, itemsPerPage = 30, filters = {}) {
        const params = createPaginationParams(page, itemsPerPage, filters)
        return apiClient.get('/patients', { params }).then(extractData)
    },

    getOne(id) {
        return apiClient.get(`/patients/${id}`).then(res => res.data)
    },

    search(query, page = 1, itemsPerPage = 30) {
        return this.getAll(page, itemsPerPage, { lastname: query })
    }
}