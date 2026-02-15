import { defineStore } from 'pinia'
import { patientApi } from '@/api/patients'
import { PATIENTS_PER_PAGE } from '@/utils/constants'

export const usePatientStore = defineStore('patient', {
    state: () => ({
        rawPatients: new Map(),
        pageCache: new Map(),
        selectedPatient: null,
        loading: false,
        error: null,
        pagination: {
            currentPage: 1,
            totalItems: 0,
            itemsPerPage: PATIENTS_PER_PAGE,
            totalPages: 0
        },
        searchQuery: '',
        hospitalFilter: null,
    }),

    actions: {
        setHospitalFilter(hospitalId) {
            this.hospitalFilter = hospitalId;
            this.selectedPatient = null;
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

        async loadPatients(page = 1, itemsPerPage = PATIENTS_PER_PAGE) {
            this.loading = true;
            this.error = null;
            this.pagination.currentPage = page;
            this.pagination.itemsPerPage = itemsPerPage;

            try {
                const filters = {}
                if (this.hospitalFilter) {
                    filters.hospital = `/api/hospitals/${this.hospitalFilter}`
                }
                const order = { lastname: 'asc' }
                const result = await patientApi.getAll(page, itemsPerPage, filters, order)
                this.pagination.totalItems = result.totalItems
                this.pagination.totalPages = Math.ceil(result.totalItems / itemsPerPage)

                const ids = []
                result.items.forEach(patient => {
                    this.rawPatients.set(patient.id, patient)
                    ids.push(patient.id)
                })
                this.pageCache.set(page, ids)

                if (page === 1 && !this.selectedPatient && ids.length > 0) {
                    this.selectedPatient = this.rawPatients.get(ids[0])
                }
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки пациентов'
                console.error('[PatientStore]', err)
                await this._loadMockPatients()
            } finally {
                this.loading = false
            }
        },

        async searchPatients(query, page = 1) {
            if (!query.trim()) {
                return this.loadPatients(1)
            }
            this.loading = true
            this.error = null
            this.searchQuery = query
            try {
                const filters = { lastname: query }
                if (this.hospitalFilter) {
                    filters.hospital = `/api/hospitals/${this.hospitalFilter}`
                }
                const order = { lastname: 'asc' }
                const result = await patientApi.getAll(page, PATIENTS_PER_PAGE, filters, order)
                const cacheKey = `search-${query}-${page}`
                const ids = []
                result.items.forEach(patient => {
                    this.rawPatients.set(patient.id, patient)
                    ids.push(patient.id)
                })
                this.pageCache.set(cacheKey, ids)
                this.pagination.currentPage = cacheKey
                this.pagination.totalItems = result.totalItems
                this.pagination.totalPages = Math.ceil(result.totalItems / PATIENTS_PER_PAGE)
            } catch (err) {
                this.error = err.message || 'Ошибка поиска'
            } finally {
                this.loading = false
            }
        },

        clearSearch() {
            this.searchQuery = ''
            this.loadPatients(1)
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
                const ids = []
                mockItems.forEach(p => {
                    this.rawPatients.set(p.id, p)
                    ids.push(p.id)
                })
                this.pageCache.set(1, ids)
                this.pagination.totalItems = mockItems.length
                this.pagination.totalPages = 1
                this.pagination.currentPage = 1
            } catch (e) {
                console.error('Не удалось загрузить моки', e)
            }
        }
    }
})