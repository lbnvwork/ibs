import apiClient from './client'
import { extractData } from '@/modules/shared/utils/apiHelpers'

export const hospitalApi = {
    async getAll() {
        const response = await apiClient.get('/hospitals', {
            params: { itemsPerPage: 100 }
        })
        const result = extractData(response)
        return result.items
    }
}