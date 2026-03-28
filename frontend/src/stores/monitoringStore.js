import { defineStore } from 'pinia';
import { patientApi } from '@/api/patients';
import { treatmentApi } from '@/api/treatments';
import { testHistoryApi } from '@/api/testHistory';
import { calculateAge, formatAge } from '@/utils/formatters';
import { getIdFromIri } from '@/utils/apiHelpers';

export const useMonitoringStore = defineStore('monitoring', {
    state: () => ({
        patients: [],
        loading: false,
        error: null,
        activeDrugId: null,
        currentPage: 1,
        itemsPerPage: 30,
        totalItems: 0,
        totalPages: 0,
        nextPageUrl: null,
        prevPageUrl: null,
    }),
    getters: {
        hasPatients: (state) => state.patients.length > 0,
    },
    actions: {
        async fetchMonitoringData(drugId, page = 1) {
            if (!drugId) return;
            this.activeDrugId = drugId;
            this.loading = true;
            this.error = null;
            
            try {
                // 1. Пациенты по препарату
                const patientResponse = await patientApi.getAll(
                    page,
                    this.itemsPerPage,
                    { drug: drugId },
                    { lastname: 'asc' }
                );
                const patients = patientResponse.items || patientResponse.member || [];
                if (!patients.length) {
                    this.patients = [];
                    this.totalItems = 0;
                    this.totalPages = 0;
                    this.nextPageUrl = null;
                    this.prevPageUrl = null;
                    this.currentPage = page;

                    return;
                }

                this.totalItems = patientResponse.totalItems;
                this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
                this.nextPageUrl = patientResponse.view?.next || null;
                this.prevPageUrl = patientResponse.view?.previous || null;
                this.currentPage = page;

                const patientIds = patients.map(p => p.id);

                // 2. Активные лечения для этих пациентов
                const treatmentsResponse = await treatmentApi.getAllWithoutPagination({
                    patient: patientIds,
                    active: true,
                    order: { begDt: 'desc' },
                });
                const treatments = treatmentsResponse.member || treatmentsResponse;
                
                // Создаём Map patientId -> лечение (берём первое, если несколько)
                const treatmentByPatient = new Map();
                treatments.forEach(t => {
                    const patientId = getIdFromIri(t.patient);
                    if (patientId && !treatmentByPatient.has(patientId)) {
                        treatmentByPatient.set(patientId, t);
                    }
                });

                // 3. Получаем анализы для этих лечений
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

                // 4. Формируем итоговый массив для таблицы
                const monitoringPatients = patients.map(patient => {
                    const treatment = treatmentByPatient.get(patient.id);
                    const testHistory = treatment ? testHistoryMap.get(treatment.id) : null;
                    const age = calculateAge(patient.birthday);
                    
                    let indicatorsHtml = '';
                    let highlightRed = false;
                    let highlightBlue = false;

                    if (testHistory && testHistory.mno !== undefined && testHistory.mno !== null) {
                        const mno = testHistory.mno;
                        const mnoFrom = treatment?.mnoFrom;
                        const mnoTo = treatment?.mnoTo;

                        if (mnoFrom !== undefined && mnoTo !== undefined) {
                            if (mno < mnoFrom) {
                                indicatorsHtml = `<span class="value-down">↓&nbsp;МНО - ${mno}</span>`;
                                highlightBlue = true;
                            } else if (mno > mnoTo) {
                                indicatorsHtml = `<span class="value-up">↑&nbsp;МНО - ${mno}</span>`;
                                highlightRed = true;
                            } else {
                                indicatorsHtml = `<span>МНО - ${mno}</span>`;
                            }
                            indicatorsHtml += `, Целевой диапазон: ${mnoFrom}-${mnoTo}`;
                        } else {
                            indicatorsHtml = `МНО - ${mno}`;
                        }
                    } else {
                        indicatorsHtml = 'нет данных';
                    }

                    return {
                        id: patient.id,
                        name: `${patient.lastname} ${patient.firstname} ${patient.secondName}`,
                        age: age ? formatAge(age) : '—',
                        diagnosis: treatment?.diagnosis || '—',
                        smsStatus: '📱',
                        indicators: indicatorsHtml,
                        comment: treatment?.comment || patient.comment || '',
                        highlightRed,
                        highlightBlue,
                    }
                });

                this.patients = monitoringPatients;
            } catch (err) {
                console.error('[MonitoringStore]', err);
                this.error = err.message || 'Ошибка загрузки данных';
            } finally {
                this.loading = false;
            }
        },
        async setDrug(drugId) {
            await this.fetchMonitoringData(drugId)
        },
        async setPage(page) {
            if (this.activeDrugId && page >= 1 && page <= this.totalPages) {
                await this.fetchMonitoringData(this.activeDrugId, page);
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