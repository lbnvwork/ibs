// frontend/src/modules/patientManagement/stores/bakulevo/workListStore.js
import { defineStore } from 'pinia';
import { patientApi } from '@/modules/shared/api/patients';
import { treatmentApi } from '@/modules/shared/api/treatments';
import { testHistoryApi } from '@/modules/shared/api/testHistory';
import { vitalsApi } from '@/modules/shared/api/vitals';
import { hospitalApi } from '@/modules/shared/api/hospitals';
import { getIdFromIri } from '@/modules/shared/utils/apiHelpers';
import { buildIndicators } from '@/modules/shared/utils/vitalsHelpers';

// Детерминированный формат даты рождения: YYYY-MM-DD -> DD.MM.YYYY
function formatBirthDate(dateStr) {
    if (!dateStr) return '—';
    const parts = String(dateStr).slice(0, 10).split('-');
    if (parts.length === 3) return `${parts[2]}.${parts[1]}.${parts[0]}`;
    return dateStr;
}

function resolveHospitalName(iri, map) {
    if (!iri) return '—';
    const id = getIdFromIri(iri);
    if (id == null) return '—';
    return map.get(Number(id)) || '—';
}

export const useBakulevoWorkListStore = defineStore('workListBakulevo', {
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
        hospitalId: null,
        drugGroupId: null,
        searchQuery: '',
        hospitalNameMap: new Map(),
    }),

    getters: {
        hasPatients: (state) => state.patients.length > 0,
    },

    actions: {
        async ensureHospitalsLoaded() {
            if (this.hospitalNameMap.size) return this.hospitalNameMap;
            try {
                const items = await hospitalApi.getAll();
                (Array.isArray(items) ? items : []).forEach(h => {
                    const id = getIdFromIri(h['@id']) || h.id;
                    if (id != null) this.hospitalNameMap.set(Number(id), h.name);
                });
            } catch (err) {
                console.error('[WorkListBakulevoStore] Ошибка загрузки ЛПУ:', err);
            }
            return this.hospitalNameMap;
        },

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
                if (this.hospitalId) {
                    filters.hospital = `/api/hospitals/${this.hospitalId}`;
                }
                if (this.drugGroupId) {
                    filters.drugGroup = this.drugGroupId;
                }
                if (this.searchQuery) {
                    filters.lastname = this.searchQuery;
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

                const [treatmentsResponse, vitalsResponse, hospitalMap] = await Promise.all([
                    treatmentApi.getAllWithoutPagination({
                        patient: patientIds,
                        active: true,
                        order: { begDt: 'desc' },
                    }),
                    vitalsApi.getBatch(patientIds),
                    this.ensureHospitalsLoaded(),
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

                this.patients = patients.map(patient => {
                    const treatment = treatmentByPatient.get(patient.id);
                    const testHistory = treatment ? testHistoryMap.get(treatment.id) : null;
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
                        name: [patient.lastname, patient.firstname, patient.secondName]
                            .filter(Boolean).join(' ').trim() || 'Без имени',
                        birthDate: formatBirthDate(patient.birthday),
                        sex: patient.sex,
                        hospitalName: resolveHospitalName(patient.hospital, hospitalMap),
                        indicators: indicatorsHtml,
                        highlightRed,
                        highlightBlue,
                    };
                });
            } catch (err) {
                console.error('[WorkListBakulevoStore]', err);
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

        setHospital(id) {
            if (this.hospitalId === id) return;
            this.hospitalId = id;
            if (this.activeDrugId) this.fetchWorkListData(this.activeDrugId, 1);
        },

        setDrugGroup(id) {
            if (this.drugGroupId === id) return;
            this.drugGroupId = id;
            if (this.activeDrugId) this.fetchWorkListData(this.activeDrugId, 1);
        },

        setSearchQuery(query) {
            const q = (query || '').trim();
            if (this.searchQuery === q) return;
            this.searchQuery = q;
            if (this.activeDrugId) this.fetchWorkListData(this.activeDrugId, 1);
        },

        // Сброс фильтров (кнопка «Обновить»): очищает поиск/ЛПУ/категории/нозологии,
        // возвращает на первую страницу и перегружает с указанным препаратом
        // (по умолчанию — текущий; компонент передаёт первый препарат как «исходное»).
        async resetFilters(drugId = this.activeDrugId) {
            this.searchQuery = '';
            this.hospitalId = null;
            this.drugGroupId = null;
            this.selectedDiagnosisCodes = [];
            this.currentPage = 1;
            if (drugId) {
                await this.fetchWorkListData(drugId, 1);
            }
        },

        setItemsPerPage(n) {
            const parsed = Number(n);
            if (!parsed || parsed === this.itemsPerPage) return;
            this.itemsPerPage = parsed;
            if (this.activeDrugId) this.fetchWorkListData(this.activeDrugId, 1);
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
