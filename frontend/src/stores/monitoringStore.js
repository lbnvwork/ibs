import { defineStore } from 'pinia';
import { patientApi } from '@/api/patients';
import { treatmentApi } from '@/api/treatments';
import { testHistoryApi } from '@/api/testHistory';
import { calculateAge } from '@/utils/formatters';
import { getIdFromIri } from '@/utils/apiHelpers';

export const useMonitoringStore = defineStore('monitoring', {
    state: () => ({
        patients: [],
        loading: false,
        error: null,
        activeDrugId: null,
    }),
    getters: {
        hasPatients: (state) => state.patients.length > 0,
    },
    actions: {
        async fetchMonitoringData(drugId) {
            
            if (!drugId) return;
            this.activeDrugId = drugId;
            this.loading = true;
            this.error = null;
            
            try {
                // 1. Пациенты по препарату
                const patientResponse = await patientApi.getAll(
                    1,                        // page
                    32,                       // itemsPerPage
                    { drug: drugId },         // filters
                    { lastname: 'asc' }       // order
                );
                const patients = patientResponse.items || patientResponse.member || [];
                if (!patients.length) {
                    this.patients = [];

                    return;
                }
                const patientIds = patients.map(p => p.id);

                // 2. Активные лечения для этих пациентов
                const treatmentsResponse = await treatmentApi.getAll({
                    patient: patientIds,
                    active: true,
                    order: { begDt: 'desc' },
                    itemsPerPage: 10000000,
                });
                const treatments = treatmentsResponse.member || treatmentsResponse;

                console.log('treatments count:', treatments.length);
                console.log('sample treatment:', treatments[0]);
                
                // Создаём Map patientId -> лечение (берём первое, если несколько)
                const treatmentByPatient = new Map();
                treatments.forEach(t => {
                    const patientId = getIdFromIri(t.patient);
                    if (patientId && !treatmentByPatient.has(patientId)) {
                        treatmentByPatient.set(patientId, t);
                    }
                });

                console.log('treatmentByPatient size:', treatmentByPatient.size);
                console.log('has patient 4184?', treatmentByPatient.has(4184));

                // 3. Получаем анализы для этих лечений
                const treatmentIds = Array.from(treatmentByPatient.values()).map(t => t.id);
                let testHistoryMap = new Map();
                if (treatmentIds.length) {
                    const params = {
                        'treatment[]': treatmentIds,
                        order: { creationDt: 'desc' },
                        itemsPerPage: 10000000
                    };
                    const historyResponse = await testHistoryApi.getAll(params);
                    const historyItems = historyResponse.member || historyResponse;

                    console.log('historyItems count:', historyItems.length);
                    console.log('sample history:', historyItems[0]);
                    console.log('has treatment 4160?', testHistoryMap.has(4160));

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

                    if (patient.id === 4184) {
                        console.log('Patient 4184 treatment:', treatment);
                        console.log('Patient 4184 testHistory:', testHistory);
                    }

                    return {
                        id: patient.id,
                        name: `${patient.lastname} ${patient.firstname} ${patient.secondName}`,
                        age: age ? `${age} лет` : '—',
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
    },
});