import { drugApi } from '@/api/drug';
import { treatmentApi } from '@/api/treatments';
import { mkb10Api } from '@/api/mkb10';
import MultiDiagnosisSelect from '@/components/common/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';

export default {
    name: 'TreatmentAdd',
    components: { MultiDiagnosisSelect },
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
                diagnosis: '',
                diagnosisCode: '',
            },
            selectedDiagnosisCodes: [],
            drugs: [],
            loading: false,
            error: null,
        };
    },
    watch: {
        selectedDiagnosisCodes: {
            async handler(newVal) {
                if (newVal && newVal.length) {
                    const code = newVal[0];
                    try {
                        const data = await mkb10Api.getByCode(code);
                        const member = data.member || data;
                        if (member.length) {
                            this.treatment.diagnosis = member[0].mkbName;
                            this.treatment.diagnosisCode = member[0].mkbCode;
                        } else {
                            this.treatment.diagnosis = '';
                            this.treatment.diagnosisCode = code;
                        }
                    } catch (err) {
                        console.error('Ошибка загрузки диагноза', err);
                        this.treatment.diagnosis = '';
                        this.treatment.diagnosisCode = code;
                    }
                } else {
                    this.treatment.diagnosis = '';
                    this.treatment.diagnosisCode = '';
                }
            },
            deep: true,
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
                    diagnosis: this.treatment.diagnosis,
                    diagnosisCode: this.treatment.diagnosisCode,
                };

                await treatmentApi.create(treatmentData);
                this.$router.push(`/patient/${this.patientId}`);
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.detail || 'Ошибка при сохранении лечения';
            } finally {
                this.loading = false;
            }
        },
    },
};