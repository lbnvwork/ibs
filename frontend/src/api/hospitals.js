import apiClient from './client'
import { extractData } from '@/utils/apiHelpers'

export const hospitalApi = {
    async getAll() {
        const response = await apiClient.get('/hospitals', {
            params: { itemsPerPage: 100 } // больше чем 45
        })
        const result = extractData(response)
        return result.items
    }
}