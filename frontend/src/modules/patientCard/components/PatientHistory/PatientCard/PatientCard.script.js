import { usePatientCardStore } from '@/modules/patientCard/stores/patientCardStore';
import { formatPhone, formatPassport, formatSnils, formatDate } from '@/modules/shared/utils/formatters';

export default {
    name: 'PatientCard',
    setup() {
        const store = usePatientCardStore();
        return { store, formatPhone, formatPassport, formatSnils, formatDate };
    },
    methods: {
        async savePatient() {
            await this.store.savePatient(this.$route.params.patientId);
        }
    }
};