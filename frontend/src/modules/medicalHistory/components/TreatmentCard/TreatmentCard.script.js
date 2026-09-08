import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore';
import { formatMno } from '@/modules/shared/utils/formatters';

export default {
    name: 'TreatmentCard',
    emits: ['edit-start', 'edit-end'],
    setup() {
        const store = useTreatmentStore();
        const formatDate = (dateStr) => {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        };
        return { store, formatDate, formatMno };
    },
    methods: {
        startEditingTreatment() {
            this.$emit('edit-start');
            this.store.startEditingTreatment();
        },
        cancelEditingTreatment() {
            this.$emit('edit-end');
            this.store.cancelEditingTreatment();
        },
        async saveTreatment() {
            const success = await this.store.saveTreatment(this.$route.params.patientId);
            if (success) {
                this.$emit('edit-end');
            }
        }
    }
};