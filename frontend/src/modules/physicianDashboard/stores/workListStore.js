// frontend/src/modules/physicianDashboard/stores/workListStore.js
import { defineStore } from 'pinia';
import { patientApi } from '@/api/patients';
import { treatmentApi } from '@/api/treatments';
import { testHistoryApi } from '@/api/testHistory';
import { vitalsApi } from '@/api/vitals';
import { calculateAge, formatAge } from '@/utils/formatters';
import { getIdFromIri } from '@/utils/apiHelpers';
import { buildIndicators } from '@/modules/physicianDashboard/utils/vitalsHelpers';

export const useWorkListStore = defineStore('workList', {
    state: () => ({
        patients: [],
        loading: false,
        error: null,
        activeDrugId: null,
        selectedDiagnosisCodes: [],
        currentPage: 1,
        itemsPerPage: 30,
        totalItems: 0,
        totalPages: 0,
        nextPageUrl: null,
        prevPageUrl: null,
        vitalsMap: new Map(),
    }),

    getters: {
        hasPatients: (state) => state.patients.length > 0,
    },

    actions: {
        async fetchWorkListData(drugId, page = 1) {
            if (!drugId) return;
            this.activeDrugId = drugId;
            this.loading = true;
            this.error = null;
            
            try {
                const filters = { drug: drugId };
                if (this.selectedDiagnosisCodes && this.selectedDiagnosisCodes.length) {
                    filters.diagnosisCode = this.selectedDiagnosisCodes;
                }

                const patientResponse = await patientApi.getAll(
                    page,
                    this.itemsPerPage,
                    filters,
                    { lastname: 'asc' }
                );
                const patients = patientResponse.items || patientResponse.member || [];
                if (!patients.length) {
                    this.patients = [];
                    this.totalItems = 0;
                    this.totalPages = 0;
                    this.nextPageUrl = null;
                    this.prevPageUrl = null;
                    return;
                }

                this.totalItems = patientResponse.totalItems;
                this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
                this.nextPageUrl = patientResponse.view?.next || null;
                this.prevPageUrl = patientResponse.view?.previous || null;
                this.currentPage = page;

                const patientIds = patients.map(p => p.id);

                const [treatmentsResponse, vitalsResponse] = await Promise.all([
                    treatmentApi.getAllWithoutPagination({
                        patient: patientIds,
                        active: true,
                        order: { begDt: 'desc' },
                    }),
                    vitalsApi.getBatch(patientIds)
                ]);

                const treatments = treatmentsResponse.member || treatmentsResponse;
                const vitalsRaw = vitalsResponse.data || vitalsResponse;
                const vitalsArray = vitalsRaw.member || vitalsRaw.items || vitalsRaw;

                const treatmentByPatient = new Map();
                treatments.forEach(t => {
                    const patientId = getIdFromIri(t.patient);
                    if (patientId && !treatmentByPatient.has(patientId)) {
                        treatmentByPatient.set(patientId, t);
                    }
                });

                this.vitalsMap.clear();
                (Array.isArray(vitalsArray) ? vitalsArray : []).forEach(v => {
                    const pid = getIdFromIri(v.patient) || v.patientId;
                    if (pid) {
                        this.vitalsMap.set(Number(pid), v);
                    }
                });

                const treatmentIds = Array.from(treatmentByPatient.values()).map(t => t.id);
                let testHistoryMap = new Map();
                if (treatmentIds.length) {
                    const historyItems = await testHistoryApi.getLatestByTreatments(treatmentIds);
                    historyItems.forEach(h => {
                        const treatmentId = getIdFromIri(h.treatment);
                        if (treatmentId && !testHistoryMap.has(treatmentId)) {
                            testHistoryMap.set(treatmentId, h);
                        }
                    });
                }

                const monitoringPatients = patients.map(patient => {
                    const treatment = treatmentByPatient.get(patient.id);
                    const testHistory = treatment ? testHistoryMap.get(treatment.id) : null;
                    const age = calculateAge(patient.birthday);
                    const vitals = this.vitalsMap.get(patient.id) || null;

                    const indicatorsHtml = buildIndicators(testHistory, vitals);

                    let highlightRed = false;
                    let highlightBlue = false;

                    if (testHistory && testHistory.mno !== undefined && testHistory.mno !== null) {
                        const mno = testHistory.mno;
                        const mnoFrom = treatment?.mnoFrom;
                        const mnoTo = treatment?.mnoTo;

                        if (mnoFrom !== undefined && mnoTo !== undefined) {
                            if (mno < mnoFrom) {
                                highlightBlue = true;
                            } else if (mno > mnoTo) {
                                highlightRed = true;
                            }
                        }
                    }

                    return {
                        id: patient.id,
                        name: `${patient.lastname} ${patient.firstname} ${patient.secondName}`,
                        age: age ? formatAge(age) : '—',
                        diagnosis: treatment?.diagnosis || '—',
                        smsStatus: '📱',
                        indicators: indicatorsHtml,
                        comment: '',
                        highlightRed,
                        highlightBlue,
                    };
                });

                this.patients = monitoringPatients;
            } catch (err) {
                console.error('[WorkListStore]', err);
                this.error = err.message || 'Ошибка загрузки данных';
            } finally {
                this.loading = false;
            }
        },

        setSelectedDiagnosisCodes(codes) {
            if (JSON.stringify(this.selectedDiagnosisCodes) !== JSON.stringify(codes)) {
                this.selectedDiagnosisCodes = codes;
                if (this.activeDrugId) {
                    this.fetchWorkListData(this.activeDrugId, 1);
                }
            }
        },

        async setDrug(drugId) {
            this.selectedDiagnosisCodes = [];
            this.currentPage = 1;
            await this.fetchWorkListData(drugId);
        },
        
        async setPage(page) {
            if (this.activeDrugId && page >= 1 && page <= this.totalPages) {
                await this.fetchWorkListData(this.activeDrugId, page);
            }
        },
        
        async nextPage() {
            if (this.nextPageUrl) {
                await this.setPage(this.currentPage + 1);
            }
        },
        
        async prevPage() {
            if (this.prevPageUrl) {
                await this.setPage(this.currentPage - 1);
            }
        },
        
        async firstPage() {
            if (this.totalPages > 0) {
                await this.setPage(1);
            }
        },
        
        async lastPage() {
            if (this.totalPages > 0) {
                await this.setPage(this.totalPages);
            }
        },
    },
});