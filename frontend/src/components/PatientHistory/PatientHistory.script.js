import { extractIdFromIri } from '@/utils/apiHelpers';
import { useAppointmentAddStore } from '@/stores/appointmentAddStore';
import AppointmentAdd from '@/components/PatientHistory/AppointmentAdd/AppointmentAdd.vue';
import TestAddModal from '@/components/PatientHistory/TestAddModal/TestAddModal.vue';
import PatientCard from '@/components/PatientHistory/PatientCard/PatientCard.vue';
import TreatmentCard from '@/components/PatientHistory/TreatmentCard/TreatmentCard.vue';
import { usePatientCardStore } from '@/stores/patientCardStore';
import { useTreatmentStore } from '@/stores/treatmentStore';
import { useMedicalTableStore } from '@/stores/medicalTableStore';
import MedicalTable from '@/components/PatientHistory/MedicalTable/MedicalTable.vue';

export default {
    name: 'PatientHistory',
    components: { RiskScale: null, AppointmentAdd, TestAddModal, PatientCard, TreatmentCard, MedicalTable },
    props: {
        id: { type: String, default: null }
    },
    data() {
        return {
            loading: true,
            error: null,
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
            async handler(newId) {
                if (newId) {
                    const patientCardStore = usePatientCardStore();
                    const treatmentStore = useTreatmentStore();
                    await Promise.all([
                        patientCardStore.fetchPatient(newId),
                        treatmentStore.fetchTreatment(newId)
                    ]);
                    this.loadPatientData();
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

            try {
                const treatmentStore = useTreatmentStore();
                const treatment = treatmentStore.treatment;
                if (!treatment) {
                    this.loading = false;
                    return;
                }

                const isActive = treatment.realEndDt === null || treatment.realEndDt === undefined;
                useAppointmentAddStore().setTreatmentActive(isActive);

                if (treatment['@id']) {
                    await useMedicalTableStore().fetchMedicalData(treatment['@id']);
                }
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