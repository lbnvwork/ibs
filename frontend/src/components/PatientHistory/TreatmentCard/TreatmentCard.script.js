import { useTreatmentStore } from '@/stores/treatmentStore';

export default {
    name: 'TreatmentCard',
    setup() {
        const store = useTreatmentStore();
        const formatDate = (dateStr) => {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        };
        return { store, formatDate };
    }
};  