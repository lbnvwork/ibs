import { drugApi } from '@/api/drug';
import { treatmentApi } from '@/api/treatments';
import MultiDiagnosisSelect from '@/components/common/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';

export default {
    name: 'TreatmentAdd',
    components: {
        MultiDiagnosisSelect,
    },
    props: {
        patientId: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            treatment: {
                drugId: null,
                begDt: new Date().toISOString().slice(0, 10),
                mnoFrom: null,
                mnoTo: null,
                comment: '',
            },
            selectedDiagnosisCodes: [],
            drugs: [],
            loading: false,
            error: null,
        };
    },
    computed: {
        patientId() {
            return this.patientId;
        },
    },
    async created() {
        await this.loadDrugs();
    },
    methods: {
        async loadDrugs() {
            try {
                const response = await drugApi.getAll({ order: { nominative: 'asc' } });
                this.drugs = response.member || response;
            } catch (err) {
                console.error('Ошибка загрузки препаратов', err);
            }
        },
        async submitForm() {
            this.loading = true;
            this.error = null;

            try {
                const treatmentData = {
                    patient: `/api/patients/${this.patientId}`,
                    drug: `/api/drugs/${this.treatment.drugId}`,
                    begDt: this.treatment.begDt,
                    mnoFrom: this.treatment.mnoFrom,
                    mnoTo: this.treatment.mnoTo,
                    comment: this.treatment.comment,
                };

                if (this.selectedDiagnosisCodes.length) {
                    treatmentData.diagnosisCode = this.selectedDiagnosisCodes[0];
                }

                await treatmentApi.create(treatmentData);
                //this.$router.push(`/patient/${this.patientId}`);
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.detail || 'Ошибка при сохранении лечения';
            } finally {
                this.loading = false;
            }
        },
    },
};