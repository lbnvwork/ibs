import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore';
import { formatPhone, formatPassport, formatSnils, formatDate } from '@/modules/shared/utils/formatters';
import PatientMaxDeeplink from '@/modules/medicalHistory/components/PatientMaxDeeplink/PatientMaxDeeplink.vue';

export default {
    name: 'PatientCard',
    components: { PatientMaxDeeplink },
    emits: ['edit-start', 'edit-end'],
    setup() {
        const store = usePatientCardStore();
        return { store, formatPhone, formatPassport, formatSnils, formatDate };
    },
    methods: {
        startEditingPatient() {
            this.$emit('edit-start');
            this.store.startEditingPatient();
        },
        cancelEditingPatient() {
            this.$emit('edit-end');
            this.store.cancelEditingPatient();
        },
        async savePatient() {
            const success = await this.store.savePatient(this.$route.params.patientId);
            if (success) {
                this.$emit('edit-end');
            }
        }
    }
};