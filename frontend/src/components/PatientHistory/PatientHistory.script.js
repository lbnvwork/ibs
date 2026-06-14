import { extractIdFromIri } from '@/utils/apiHelpers';
import apiClient from '@/api/client';
import { testHistoryApi } from '@/api/testHistory';
import { useAppointmentAddStore } from '@/stores/appointmentAddStore';
import AppointmentAdd from '@/components/PatientHistory/AppointmentAdd/AppointmentAdd.vue';
import TestAddModal from '@/components/PatientHistory/TestAddModal/TestAddModal.vue';
import MnoChart from '@/components/PatientHistory/MnoChart/MnoChart.vue';
import PatientCard from '@/components/PatientHistory/PatientCard/PatientCard.vue';
import TreatmentCard from '@/components/PatientHistory/TreatmentCard/TreatmentCard.vue';
import { usePatientCardStore } from '@/stores/patientCardStore';
import { useTreatmentStore } from '@/stores/treatmentStore';

export default {
    name: 'PatientHistory',
    components: { RiskScale: null, AppointmentAdd, TestAddModal, MnoChart, PatientCard, TreatmentCard },
    props: {
        id: { type: String, default: null }
    },
    data() {
        return {
            loading: true,
            error: null,
            medicalData: [],
            showTestModal: false,
            showAppointmentInlineModal: false
        };
    },
    computed: {
        showAppointmentModal() {
            return useAppointmentAddStore().isModalOpen;
        },
        treatmentStore() {
            return useTreatmentStore();
        }
    },
    watch: {
        id: {
            immediate: true,
            handler(newId) {
                if (newId) {
                    this.loadPatientData();
                    usePatientCardStore().fetchPatient(newId);
                    useTreatmentStore().fetchTreatment(newId);
                }
            }
        }
    },
    methods: {
        extractIdFromIri,

        async loadPatientData() {
            this.loading = true;
            useAppointmentAddStore().setTreatmentActive(false);
            this.error = null;
            this.medicalData = [];

            try {
                const treatmentStore = useTreatmentStore();
                const treatment = treatmentStore.treatment;
                if (!treatment) {
                    this.loading = false;
                    return;
                }

                const isActive = treatment.realEndDt === null || treatment.realEndDt === undefined;
                useAppointmentAddStore().setTreatmentActive(isActive);

                let historyItems = [];
                let appointments = [];
                if (treatment['@id']) {
                    const apptResp = await apiClient.get('/appointments', {
                        params: {
                            treatment: treatment['@id'],
                            order: { appointmentDt: 'asc' },
                            itemsPerPage: 1000
                        }
                    });
                    appointments = apptResp.data.member || [];

                    const historyResp = await testHistoryApi.getAll({
                        treatment: treatment['@id'],
                        order: { creationDt: 'desc' },
                        itemsPerPage: 300
                    });
                    historyItems = historyResp.member || [];
                }

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

                this.medicalData = events.map(event => ({
                    date: event.date
                        ? new Date(event.date).toLocaleDateString('ru-RU')
                        : '—',
                    inr: event.inr,
                    analysis1: '—',
                    analysis2: '—',
                    heartRate: '—',
                    bloodPressure: '—',
                    currentDose: event.currentDose,
                    prescribedDose: event.prescribedDose,
                    recommendations: event.recommendations,
                    comment: event.comment,
                    isAppointment: event.type === 'appointment'
                }));
            } catch (err) {
                console.error('Ошибка загрузки истории:', err);
                this.error = 'Не удалось загрузить данные пациента.';
            } finally {
                this.loading = false;
            }
        },

        closeAppointmentModal() {
            useAppointmentAddStore().closeModal();
        },
        onAppointmentSaved() {
            this.loadPatientData();
            useAppointmentAddStore().closeModal();
        },
        openTestModal() {
            this.showTestModal = true;
        },
        onTestSaved() {
            this.loadPatientData();
            this.showTestModal = false;
        },
        openAppointmentInlineModal() {
            this.showAppointmentInlineModal = true;
        },
        onAppointmentInlineSaved() {
            this.loadPatientData();
            this.showAppointmentInlineModal = false;
        }
    }
};