import { usePatientCardStore } from '@/stores/patientCardStore';
import { formatPhone, formatPassport, formatSnils } from '@/utils/formatters';

export default {
    name: 'PatientCard',
    setup() {
        const store = usePatientCardStore();
        return { store, formatPhone, formatPassport, formatSnils };
    },
    methods: {
        async savePatient() {
            await this.store.savePatient(this.$route.params.patientId);
        }
    }
};