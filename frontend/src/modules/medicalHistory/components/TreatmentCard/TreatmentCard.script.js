import { mkb10Api } from '@/modules/shared/api/mkb10';
import MultiDiagnosisSelect from '@/modules/shared/components/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore';

export default {
    name: 'TreatmentCard',
    components: { MultiDiagnosisSelect },
    emits: ['edit-start', 'edit-end'],
    data() {
        return {
            selectedDiagnosisCode: null,
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
    computed: {
        diagnosisCodes() {
            return this.selectedDiagnosisCode ? [this.selectedDiagnosisCode] : [];
        },
    },
    watch: {
        selectedDiagnosisCode(code) {
            this.applyDiagnosisCode(code);
        },
    },
    methods: {
        applyDiagnosisCode(code) {
            if (!code) {
                this.store.editingTreatmentData.diagnosis = '';
                this.store.editingTreatmentData.diagnosisCode = '';
                return;
            }
            mkb10Api.getByCode(code)
                .then((data) => {
                    const member = data.member || data;
                    if (member.length) {
                        this.store.editingTreatmentData.diagnosis = member[0].mkbName;
                        this.store.editingTreatmentData.diagnosisCode = member[0].mkbCode;
                    } else {
                        this.store.editingTreatmentData.diagnosis = '';
                        this.store.editingTreatmentData.diagnosisCode = code;
                    }
                })
                .catch((err) => {
                    console.error('Ошибка загрузки диагноза', err);
                    this.store.editingTreatmentData.diagnosis = '';
                    this.store.editingTreatmentData.diagnosisCode = code;
                });
        },
        onDiagnosisSelect(codes) {
            const list = Array.isArray(codes) ? codes : [];
            this.selectedDiagnosisCode = list.length ? list[list.length - 1] : null;
        },
        startEditingTreatment() {
            this.$emit('edit-start');
            this.store.startEditingTreatment();
            this.selectedDiagnosisCode = this.store.editingTreatmentData.diagnosisCode || null;
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