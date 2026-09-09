import { mkb10Api } from '@/modules/shared/api/mkb10';
import MultiDiagnosisSelect from '@/modules/shared/components/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore';

export default {
    name: 'TreatmentCard',
    components: { MultiDiagnosisSelect },
    emits: ['edit-start', 'edit-end'],
    data() {
        return {
            selectedDiagnosisCodes: [],
        };
    },
    setup() {
        const store = useTreatmentStore();
        const formatDate = (dateStr) => {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        };
        return { store, formatDate };
    },
    watch: {
        selectedDiagnosisCodes: {
            async handler(codes) {
                const list = Array.isArray(codes) ? codes : [];
                if (list.length === 0) {
                    this.store.editingTreatmentData.diagnosis = '';
                    this.store.editingTreatmentData.diagnosisCode = '';
                    return;
                }
                const code = list[list.length - 1];
                try {
                    const data = await mkb10Api.getByCode(code);
                    const member = data.member || data;
                    if (member.length) {
                        this.store.editingTreatmentData.diagnosis = member[0].mkbName;
                        this.store.editingTreatmentData.diagnosisCode = member[0].mkbCode;
                    } else {
                        this.store.editingTreatmentData.diagnosis = '';
                        this.store.editingTreatmentData.diagnosisCode = code;
                    }
                } catch (err) {
                    console.error('Ошибка загрузки диагноза', err);
                    this.store.editingTreatmentData.diagnosis = '';
                    this.store.editingTreatmentData.diagnosisCode = code;
                }
            },
            deep: true,
        },
    },
    methods: {
        startEditingTreatment() {
            this.$emit('edit-start');
            this.store.startEditingTreatment();
            this.selectedDiagnosisCodes = this.store.editingTreatmentData.diagnosisCode
                ? [this.store.editingTreatmentData.diagnosisCode]
                : [];
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