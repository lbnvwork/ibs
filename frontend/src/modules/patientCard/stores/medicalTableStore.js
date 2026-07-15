import { defineStore } from 'pinia';
import apiClient from '@/modules/shared/api/client';
import { testHistoryApi } from '@/modules/shared/api/testHistory';
import { vitalsApi } from '@/modules/shared/api/vitals';
import { formatDate } from '@/modules/shared/utils/formatters';

export const useMedicalTableStore = defineStore('medicalTable', {
    state: () => ({
        events: [],
        loading: false,
        error: null
    }),
    actions: {
        async fetchMedicalData(treatmentIri) {
            if (!treatmentIri) {
                this.events = [];
                return;
            }
            this.loading = true;
            this.error = null;
            try {
                const [apptResp, historyResp, vitalsResp] = await Promise.all([
                    apiClient.get('/appointments', {
                        params: {
                            treatment: treatmentIri,
                            order: { appointmentDt: 'asc' },
                            itemsPerPage: 1000
                        }
                    }),
                    testHistoryApi.getAll({
                        treatment: treatmentIri,
                        order: { creationDt: 'desc' },
                        itemsPerPage: 300
                    }),
                    vitalsApi.getByTreatment(treatmentIri)
                ]);

                let appointments = apptResp.data.member || [];
                const historyItems = historyResp.member || [];
                const vitalsItems = vitalsResp.data.member || [];

                const vitalsByDay = new Map();
                vitalsItems.forEach(v => {
                    const dayKey = v.recordDt.split('T')[0];
                    if (!vitalsByDay.has(dayKey)) {
                        vitalsByDay.set(dayKey, []);
                    }
                    vitalsByDay.get(dayKey).push(v);
                });

                const events = [];
                const coveredDays = new Set();

                historyItems.forEach(item => {
                    const testDate = new Date(item.creationDt);
                    const dayKey = testDate.toISOString().split('T')[0];
                    const matchingAppt = appointments.find(a => {
                        const apptDate = new Date(a.appointmentDt);
                        return apptDate.toISOString().split('T')[0] === dayKey;
                    });
                    if (matchingAppt) {
                        appointments = appointments.filter(a => a !== matchingAppt);
                    }

                    const dayVitals = vitalsByDay.get(dayKey) || [];
                    const latestVitals = getLatestVitalsForDay(dayVitals);

                    events.push({
                        type: 'test',
                        date: item.creationDt,
                        displayDate: formatDate(item.creationDt),
                        mno: item.mno !== undefined ? item.mno : null,
                        currentDose: item.doze !== undefined ? item.doze : '—',
                        prescribedDose: matchingAppt ? matchingAppt.doze : '—',
                        recommendations: matchingAppt ? (matchingAppt.comment || '') : '',
                        comment: item.comment || '',
                        ...latestVitals
                    });
                    coveredDays.add(dayKey);
                });

                for (const [dayKey, vitalsList] of vitalsByDay.entries()) {
                    if (!coveredDays.has(dayKey) && vitalsList.length > 0) {
                        const latestVitals = getLatestVitalsForDay(vitalsList);
                        const hasAny = Object.values(latestVitals).some(v => v !== null);
                        if (hasAny) {
                            const matchingAppt = appointments.find(a => {
                                const apptDate = new Date(a.appointmentDt);
                                return apptDate.toISOString().split('T')[0] === dayKey;
                            });
                            if (matchingAppt) {
                                appointments = appointments.filter(a => a !== matchingAppt);
                            }
                            events.push({
                                type: 'vitals_only',
                                date: dayKey + 'T12:00:00',
                                displayDate: formatDate(dayKey + 'T12:00:00'),
                                mno: null,
                                currentDose: '—',
                                prescribedDose: matchingAppt ? matchingAppt.doze : '—',
                                recommendations: matchingAppt ? (matchingAppt.comment || '') : '',
                                comment: '',
                                ...latestVitals
                            });
                            coveredDays.add(dayKey);
                        }
                    }
                }

                appointments.forEach(a => {
                    events.push({
                        type: 'appointment',
                        date: a.appointmentDt,
                        displayDate: formatDate(a.appointmentDt),
                        mno: null,
                        currentDose: '—',
                        prescribedDose: a.doze,
                        recommendations: a.comment || '',
                        comment: '',
                        hb: null, heartRate: null,
                        systolicPressure: null, diastolicPressure: null,
                        saturation: null, weight: null
                    });
                });

                events.sort((a, b) => new Date(b.date) - new Date(a.date));
                this.events = events;
            } catch (err) {
                console.error('Ошибка загрузки медицинской истории:', err);
                this.error = 'Не удалось загрузить историю';
            } finally {
                this.loading = false;
            }
        }
    }
});

/**
 * Из массива витальных записей за день возвращает последние не-null значения
 * каждого показателя. Записи должны быть отсортированы по recordDt ASC.
 */
function getLatestVitalsForDay(vitalsList) {
    const result = {
        hb: null,
        heartRate: null,
        systolicPressure: null,
        diastolicPressure: null,
        saturation: null,
        weight: null
    };
    for (const v of vitalsList) {
        if (result.hb === null && v.hb !== null) result.hb = v.hb;
        if (result.heartRate === null && v.heartRate !== null) result.heartRate = v.heartRate;
        if (result.systolicPressure === null && v.systolicPressure !== null) result.systolicPressure = v.systolicPressure;
        if (result.diastolicPressure === null && v.diastolicPressure !== null) result.diastolicPressure = v.diastolicPressure;
        if (result.saturation === null && v.saturation !== null) result.saturation = v.saturation;
        if (result.weight === null && v.weight !== null) result.weight = v.weight;
    }
    return result;
}