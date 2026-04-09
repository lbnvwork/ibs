import { mapActions } from 'pinia';
import { useMonitoringStore } from '@/stores/monitoringStore';

export default {
    data() {
        return {
            selectedDiagnosisCodes: [],
        };
    },
    watch: {
        selectedDiagnosisCodes: {
            handler(newVal, oldVal) {
                if (JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
                    this.setSelectedDiagnosisCodes(newVal);
                }
            },
            deep: true,
        },
    },
    methods: {
        ...mapActions(useMonitoringStore, ['setSelectedDiagnosisCodes']),
    }
};