import { useMedicalTableStore } from '@/modules/medicalHistory/stores/medicalTableStore';
import MnoChart from '@/modules/medicalHistory/components/MnoChart/MnoChart.vue';
import { buildIndicatorsFromRow } from '@/modules/shared/utils/vitalsHelpers';
import { computed } from 'vue';

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

        const chartData = computed(() => {
            return store.events
                .filter(e => e.mno !== null)
                .map(e => ({ date: e.date, inr: e.mno }));
        });

        return { store, chartData, buildIndicatorsFromRow };
    }
};