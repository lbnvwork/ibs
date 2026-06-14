import { defineStore } from 'pinia';
import apiClient from '@/api/client';
import { testHistoryApi } from '@/api/testHistory';

export const useMedicalHistoryStore = defineStore('medicalHistory', {
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
                const [apptResp, historyResp] = await Promise.all([
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
                    })
                ]);

                let appointments = apptResp.data.member || [];
                const historyItems = historyResp.member || [];
                const events = [];

                historyItems.forEach(item => {
                    const testDate = new Date(item.creationDt);
                    const testDateStr = testDate.toLocaleDateString('ru-RU');
                    const matchingAppt = appointments.find(a => {
                        const apptDate = new Date(a.appointmentDt);
                        return apptDate.toLocaleDateString('ru-RU') === testDateStr;
                    });
                    if (matchingAppt) {
                        appointments = appointments.filter(a => a !== matchingAppt);
                    }
                    events.push({
                        type: 'test',
                        date: item.creationDt,
                        inr: item.mno !== undefined ? item.mno : '—',
                        currentDose: item.doze !== undefined ? item.doze : '—',
                        prescribedDose: matchingAppt ? matchingAppt.doze : '—',
                        recommendations: matchingAppt ? (matchingAppt.comment || '') : '',
                        comment: item.comment || ''
                    });
                });

                appointments.forEach(a => {
                    events.push({
                        type: 'appointment',
                        date: a.appointmentDt,
                        inr: '—',
                        currentDose: '—',
                        prescribedDose: a.doze,
                        recommendations: a.comment || '',
                        comment: ''
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