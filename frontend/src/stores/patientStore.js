import { defineStore } from 'pinia'
import { patientApi } from '@/api/patients'
import { PATIENTS_PER_PAGE } from '@/utils/constants'
import { transformForListPanel } from '@/transformers/patientTransformers'
import apiClient from '@/api/client'

export const usePatientStore = defineStore('patient', {
    state: () => ({
        rawPatients: new Map(),
        statuses: {},
        selectedPatient: null,
        loading: false,
        error: null,
        searchQuery: '',
        hospitalFilter: null,
        allPatientIds: [],
        hasMore: true,
        nextPage: 1,
        drugGroupFilter: null,
    }),

    getters: {
        displayedPatients: (state) => {
            return state.allPatientIds
                .map(id => {
                    const patient = state.rawPatients.get(id);
                    if (!patient) return null;
                    const status = state.statuses[id];

                    return transformForListPanel({ ...patient, status });
                })
                .filter(Boolean);
        },

        totalLoaded: (state) => state.allPatientIds.length,
    },

    actions: {
        setDrugGroupFilter(drugGroupId) {
            this.drugGroupFilter = drugGroupId;
            this.resetList();
            this.loadMore();
        },

        setHospitalFilter(hospitalId) {
            this.hospitalFilter = hospitalId;
            this.resetList();
            this.loadMore();
        },

        resetList() {
            this.allPatientIds = [];
            this.rawPatients.clear();
            this.statuses = {};
            this.nextPage = 1;
            this.hasMore = true;
            this.selectedPatient = null;
        },

        async selectPatient(id) {
            if (this.rawPatients.has(id)) {
                this.selectedPatient = this.rawPatients.get(id);
                return;
            }
            try {
                const patient = await patientApi.getOne(id);
                this.rawPatients.set(patient.id, patient);
                this.selectedPatient = patient;
            } catch (err) {
                console.error(`[PatientStore] Ошибка загрузки пациента ${id}:`, err)
                this.error = `Не удалось загрузить данные пациента`
            }
        },

        async loadMore() {
            if (this.loading || !this.hasMore) return;

            this.loading = true;
            this.error = null;

            try {
                const filters = {}
                if (this.hospitalFilter) {
                    filters.hospital = `/api/hospitals/${this.hospitalFilter}`;
                }
                if (this.searchQuery) {
                    filters.lastname = this.searchQuery;
                }
                if (this.drugGroupFilter) {
                    filters.drugGroup = this.drugGroupFilter;
                }
                const order = { lastname: 'asc' }

                const result = await patientApi.getAll(this.nextPage, PATIENTS_PER_PAGE, filters, order);

                if (result.items.length === 0) {
                    this.hasMore = false;
                    return;
                }

                const newIds = [];
                result.items.forEach(patient => {
                    this.rawPatients.set(patient.id, patient);
                    newIds.push(patient.id);
                })
                this.allPatientIds.push(...newIds);
                this.nextPage++;

                this.hasMore = !!result.next;

                this.loadStatuses(newIds).catch(err => {
                    console.error('Ошибка загрузки статусов:', err);
                });

                if (this.allPatientIds.length > 0 && !this.selectedPatient) {
                    this.selectedPatient = this.rawPatients.get(this.allPatientIds[0]);
                }
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки пациентов';
                console.error('[PatientStore]', err);
            } finally {
                this.loading = false;
            }
        },

        async loadStatuses(patientIds) {
            if (!patientIds.length) return;

            const idsToLoad = patientIds.filter(id => !this.statuses[id]);
            if (!idsToLoad.length) return;

            try {
                const response = await apiClient.post('/patients/status', { ids: idsToLoad });
                let statusArray = response.data?.member;

                statusArray.forEach(item => {
                    this.statuses[item.id] = item.status;
                });
            } catch (err) {
                console.error('Failed to load statuses', err);
            }
        },

        async searchPatients(query) {
            if (!query.trim()) {
                this.searchQuery = '';
                this.resetList();
                this.loadMore();

                return;
            }
            this.searchQuery = query
            this.resetList()
            this.loadMore()
        },

        clearSearch() {
            this.searchQuery = '';
            this.resetList();
            this.loadMore();
        },
    }
})