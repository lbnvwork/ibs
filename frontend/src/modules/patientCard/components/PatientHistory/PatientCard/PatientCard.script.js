import { usePatientCardStore } from '@/modules/patientCard/stores/patientCardStore';
import { formatPhone, formatPassport, formatSnils } from '@/modules/shared/utils/formatters';

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