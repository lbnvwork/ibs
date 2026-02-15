import apiClient from './client'
import { extractData, createPaginationParams } from '@/utils/apiHelpers'
import { PATIENTS_PER_PAGE } from '@/utils/constants'

export const patientApi = {
    getAll(page = 1, itemsPerPage = PATIENTS_PER_PAGE, filters = {}, order = {}) {
        const params = createPaginationParams(page, itemsPerPage, filters)

        Object.entries(order).forEach(([field, direction]) => {
            params[`order[${field}]`] = direction
        })

        return apiClient.get('/patients', { params }).then(extractData)
    },

    getOne(id) {
        return apiClient.get(`/patients/${id}`).then(res => res.data)
    },

    search(query, page = 1, itemsPerPage = PATIENTS_PER_PAGE) {
        return this.getAll(page, itemsPerPage, { lastname: query })
    }
}