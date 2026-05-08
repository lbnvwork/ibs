import { drugApi } from '@/api/drug';
import { treatmentApi } from '@/api/treatments';
import { mkb10Api } from '@/api/mkb10';
import MultiDiagnosisSelect from '@/components/common/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';
import { parseApiError } from '@/utils/apiErrorHandler';

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
            fieldErrors: {},
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
        validateFormAndSetErrors() {
            const rules = {
                drugId: { required: true, message: 'Выберите препарат' },
                begDt: { required: true, message: 'Укажите дату начала лечения' },
                diagnosis: {
                    required: true,
                    message: 'Введите диагноз',
                    validator: (val) => val && val.trim().length > 0,
                    errorMsg: 'Введите диагноз (текст)',
                },
                mnoFrom: { required: true, message: 'Целевой диапазон МНО (от) обязателен' },
                mnoTo: { required: true, message: 'Целевой диапазон МНО (до) обязателен' },
            };

            const extraChecks = (errors, data) => {
                if (data.mnoFrom && data.mnoTo && data.mnoFrom > data.mnoTo) {
                    errors.mnoFrom = 'МНО «от» не может быть больше МНО «до»';
                }
            };

            this.fieldErrors = validateForm(this.treatment, rules, extraChecks);
            return Object.keys(this.fieldErrors).length > 0;
        },
        async submitForm() {
            if (this.validateFormAndSetErrors()) {
                this.error = null;
                return;
            }

            this.loading = true;
            this.error = null;
            this.fieldErrors = {};

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
                const parsed = parseApiError(err);
                if (parsed.violations) {
                    const fieldErrors = {};
                    parsed.violations.forEach(v => {
                        const field = v.propertyPath;
                        let msg = v.message;
                        const prefix = `${field}: `;
                        if (msg.startsWith(prefix)) {
                            msg = msg.slice(prefix.length);
                        }
                        if (!fieldErrors[field]) fieldErrors[field] = [];
                        fieldErrors[field].push(msg);
                    });
                    this.fieldErrors = Object.fromEntries(
                        Object.entries(fieldErrors).map(([f, arr]) => [f, arr.join(' ')])
                    );
                    this.error = null;
                } else {
                    this.fieldErrors = {};
                    this.error = parsed.generalError;
                }
            } finally {
                this.loading = false;
            }
        },
    },
};