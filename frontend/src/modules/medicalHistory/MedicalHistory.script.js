import { extractIdFromIri } from '@/modules/shared/utils/apiHelpers';
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore';
import AppointmentAdd from '@/modules/medicalHistory/components/AppointmentAdd/AppointmentAdd.vue';
import TestAddModal from '@/modules/medicalHistory/components/TestAddModal/TestAddModal.vue';
import PatientCard from '@/modules/medicalHistory/components/PatientCard/PatientCard.vue';
import TreatmentCard from '@/modules/medicalHistory/components/TreatmentCard/TreatmentCard.vue';
import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore';
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore';
import { useMedicalTableStore } from '@/modules/medicalHistory/stores/medicalTableStore';
import MedicalTable from '@/modules/medicalHistory/components/MedicalTable/MedicalTable.vue';
import Pharmacogenetics from '@/modules/medicalHistory/components/Pharmacogenetics/Pharmacogenetics.vue';
import VitalsCard from '@/modules/medicalHistory/components/VitalsCard/VitalsCard.vue';
import RiskScale from '@/modules/medicalHistory/components/RiskScale/RiskScale.vue';

export default {
    name: 'MedicalHistory',
    components: { 
        RiskScale, 
        AppointmentAdd, 
        TestAddModal, 
        PatientCard, 
        TreatmentCard, 
        MedicalTable, 
        Pharmacogenetics,
        VitalsCard
    },
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
        },
        activeTreatmentId() {
            const treatment = this.treatmentStore.treatment;
            return treatment ? this.extractIdFromIri(treatment['@id']) : null;
        },
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
                const patientCardStore = usePatientCardStore();
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
        },
        async reloadPatient() {
            if (this.id) {
                const patientCardStore = usePatientCardStore();
                await patientCardStore.fetchPatient(this.id);
            }
        },
    }
};