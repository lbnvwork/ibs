import { hospitalApi } from '@/api/hospitals';
import { patientApi } from '@/api/patients';
import { formatPhone, formatPassport, formatSnils } from '@/utils/formatters';
import { isValidPhone, isValidPassport, isValidSnils, isValidEmail } from '@/utils/validators';
import { parseApiError } from '@/utils/apiErrorHandler';

export default {
    name: 'PatientAdd',
    data() {
        return {
            patient: {
                lastname: '',
                firstname: '',
                secondName: '',
                birthday: '',
                sex: 0,
                smsPhone: '',
                address: '',
                passport: '',
                snils: '',
                healthInsurance: '',
                email: '',
                comment: '',
                hospitalId: null,
            },
            hospitals: [],
            loadingHospitals: false,
            loading: false,
            error: null,
            fieldErrors: {},
        };
    },
    async created() {
        await this.loadHospitals();
    },
    methods: {
        async loadHospitals() {
            this.loadingHospitals = true;
            try {
                this.hospitals = await hospitalApi.getAll();
            } catch (err) {
                console.error('Ошибка при загрузке больниц:', err);
            } finally {
                this.loadingHospitals = false;
            }
        },
        formatPhoneField() {
            this.patient.smsPhone = formatPhone(this.patient.smsPhone);
        },
        formatPassportField() {
            this.patient.passport = formatPassport(this.patient.passport);
        },
        formatSnilsField() {
            this.patient.snils = formatSnils(this.patient.snils);
        },
        validateFormAndSetErrors() {
            const rules = {
                lastname: { required: true, message: 'Фамилия обязательна' },
                firstname: { required: true, message: 'Имя обязательно' },
                birthday: {
                    required: true,
                    message: 'Дата рождения обязательна',
                    validator: (val) => {
                        if (!val) return false;
                        const date = new Date(val);
                        if (isNaN(date.getTime())) return false;
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const minDate = new Date();
                        minDate.setFullYear(today.getFullYear() - 120);
                        return date <= today && date >= minDate;
                    },
                    errorMsg: 'Дата рождения не может быть в будущем и возраст не может превышать 120 лет.'
                },
                smsPhone: {
                    required: true,
                    message: 'Телефон обязателен',
                    validator: (val) => isValidPhone(val),
                    errorMsg: 'Телефон должен содержать 11 цифр (8 или 7) в формате 8(XXX)XXX-XX-XX'
                },
                hospitalId: { required: true, message: 'Больница обязательна' },
                address: { required: true, message: 'Адрес обязателен' },
                passport: {
                    required: true,
                    message: 'Паспорт обязателен',
                    validator: (val) => isValidPassport(val),
                    errorMsg: 'Паспорт должен быть в формате XXXX XXXXXX (4 и 6 цифр)'
                },
                snils: {
                    required: true,
                    message: 'СНИЛС обязателен',
                    validator: (val) => isValidSnils(val),
                    errorMsg: 'СНИЛС должен содержать 11 цифр в формате XXX-XXX-XXX XX'
                },
                email: {
                    validator: isValidEmail,
                    errorMsg: 'Неверный формат email',
                },
            };

            const newErrors = {};
            let hasError = false;

            for (const [field, rule] of Object.entries(rules)) {
                const value = this.patient[field];
                if (rule.required && !value) {
                    newErrors[field] = rule.message;
                    hasError = true;
                } else if (rule.validator && !rule.validator(value)) {
                    newErrors[field] = rule.errorMsg;
                    hasError = true;
                }
            }

            this.fieldErrors = newErrors;
            return hasError;
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
                const patientData = {
                    lastname: this.patient.lastname,
                    firstname: this.patient.firstname,
                    secondName: this.patient.secondName,
                    birthday: this.patient.birthday,
                    sex: Number(this.patient.sex),
                    smsPhone: this.patient.smsPhone,
                    address: this.patient.address,
                    passport: this.patient.passport,
                    snils: this.patient.snils,
                    healthInsurance: this.patient.healthInsurance,
                    email: this.patient.email.trim() || null,
                    comment: this.patient.comment,
                    hospital: `/api/hospitals/${this.patient.hospitalId}`,
                };

                const createdPatient = await patientApi.create(patientData);
                this.$router.push(`/patient/${createdPatient.id}/treatment/add`);
            } catch (err) {
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
        }
    },
};