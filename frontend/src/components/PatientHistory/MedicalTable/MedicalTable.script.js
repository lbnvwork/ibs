import { useMedicalTableStore } from '@/stores/medicalTableStore';
import MnoChart from '@/components/PatientHistory/MnoChart/MnoChart.vue';

export default {
    name: 'MedicalTable',
    components: { MnoChart },
    props: {
        mnoFrom: { type: Number, default: null },
        mnoTo: { type: Number, default: null }
    },
    emits: ['open-test-modal', 'open-appointment-modal'],
    setup() {
        const store = useMedicalTableStore();
        return { store };
    }
};