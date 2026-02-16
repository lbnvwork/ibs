import { defineStore } from 'pinia'
import { patientApi } from '@/api/patients'
import { PATIENTS_PER_PAGE } from '@/utils/constants'
import { transformForListPanel } from '@/transformers/patientTransformers'

export const usePatientStore = defineStore('patient', {
    state: () => ({
        rawPatients: new Map(),
        selectedPatient: null,
        loading: false,
        error: null,
        searchQuery: '',
        hospitalFilter: null,
        allPatientIds: [],
        hasMore: true,
        nextPage: 1,
    }),

    getters: {
        displayedPatients: (state) => {
            return state.allPatientIds
                .map(id => state.rawPatients.get(id))
                .filter(Boolean)
                .map(patient => transformForListPanel(patient))
        },

        totalLoaded: (state) => state.allPatientIds.length,
    },

    actions: {
        setHospitalFilter(hospitalId) {
            this.hospitalFilter = hospitalId;
            this.resetList()
            this.loadMore()
        },

        resetList() {
            this.allPatientIds = []
            this.rawPatients.clear()
            this.nextPage = 1
            this.hasMore = true
            this.selectedPatient = null
        },

        async selectPatient(id) {
            if (this.rawPatients.has(id)) {
                this.selectedPatient = this.rawPatients.get(id)
                return
            }
            try {
                const patient = await patientApi.getOne(id)
                this.rawPatients.set(patient.id, patient)
                this.selectedPatient = patient
            } catch (err) {
                console.error(`[PatientStore] Ошибка загрузки пациента ${id}:`, err)
                this.error = `Не удалось загрузить данные пациента`
            }
        },

        async loadMore() {
            if (this.loading || !this.hasMore) return

            this.loading = true;
            this.error = null;

            try {
                const filters = {}
                if (this.hospitalFilter) {
                    filters.hospital = `/api/hospitals/${this.hospitalFilter}`
                }
                if (this.searchQuery) {
                    filters.lastname = this.searchQuery
                }
                const order = { lastname: 'asc' }

                const result = await patientApi.getAll(this.nextPage, PATIENTS_PER_PAGE, filters, order)

                if (result.items.length === 0) {
                    this.hasMore = false
                    return
                }

                const newIds = []
                result.items.forEach(patient => {
                    this.rawPatients.set(patient.id, patient)
                    newIds.push(patient.id)
                })
                this.allPatientIds.push(...newIds)

                this.nextPage++

                if (result.items.length < PATIENTS_PER_PAGE) {
                    this.hasMore = false
                }

                if (this.allPatientIds.length > 0 && !this.selectedPatient) {
                    this.selectedPatient = this.rawPatients.get(this.allPatientIds[0])
                }
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки пациентов'
                console.error('[PatientStore]', err)
                // await this._loadMockPatients()
            } finally {
                this.loading = false
            }
        },

        async searchPatients(query) {
            if (!query.trim()) {
                this.searchQuery = ''
                this.resetList()
                this.loadMore()

                return
            }
            this.searchQuery = query
            this.resetList()
            this.loadMore()
        },

        clearSearch() {
            this.searchQuery = ''
            this.resetList()
            this.loadMore()
        },

        async _loadMockPatients() {
            try {
                const { panelPatients } = await import('@/data/patients')
                const mockItems = panelPatients.map((p, i) => ({
                    id: p.id,
                    lastname: p.name.split(' ')[0] || '',
                    firstname: p.name.split(' ')[1] || '',
                    secondName: p.name.split(' ')[2] || '',
                    birthday: '1958-04-07T00:00:00+00:00',
                    smsPhone: '+79192758819',
                    address: 'г. Курск, ул. Ломакина, д. 5, кв. 7',
                    comment: '',
                    hospital: '/api/hospitals/1'
                }))

                this.rawPatients.clear()
                this.allPatientIds = []
                mockItems.forEach(p => {
                    this.rawPatients.set(p.id, p)
                    this.allPatientIds.push(p.id)
                })
                this.hasMore = false
                this.nextPage = 1
                if (this.allPatientIds.length > 0 && !this.selectedPatient) {
                    this.selectedPatient = this.rawPatients.get(this.allPatientIds[0])
                }
            } catch (e) {
                console.error('Не удалось загрузить моки', e)
            }
        }
    }
})