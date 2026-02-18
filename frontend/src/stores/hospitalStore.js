import { defineStore } from 'pinia'
import { hospitalApi } from '@/api/hospitals'
import { extractIdFromIri } from '@/utils/apiHelpers'

export const useHospitalStore = defineStore('hospital', {
    state: () => ({
        hospitals: [],
        selectedHospitalId: null,
        loading: false,
        error: null
    }),

    getters: {
        hospitalOptions: (state) => {
            const options = [{ value: '', label: 'Все лечебные учреждения' }]
            state.hospitals.forEach(h => {
                options.push({ value: h.id, label: h.name })
            })
            return options
        }
    },

    actions: {
        async loadHospitals() {
            this.loading = true
            this.error = null
            try {
                const items = await hospitalApi.getAll()
                let hospitals = items.map(h => ({
                    id: extractIdFromIri(h['@id']) || h.id,
                    name: h.name
                }))

                hospitals.sort((a, b) => a.name.localeCompare(b.name, 'ru'))
                this.hospitals = hospitals
            } catch (err) {
                this.error = err.message
                console.error('Ошибка загрузки больниц:', err)
            } finally {
                this.loading = false
            }
        },

        setSelectedHospital(id) {
            this.selectedHospitalId = id
        },

        clearSelectedHospital() {
            this.selectedHospitalId = null
        }
    }
})