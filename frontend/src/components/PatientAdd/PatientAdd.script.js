import { hospitalApi } from '@/api/hospitals';
import { patientApi } from '@/api/patients';
import { formatPhone, formatPassport, formatSnils } from '@/utils/formatters';
import { isValidPhone, isValidPassport, isValidSnils } from '@/utils/validators';
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
                comment: '',
                hospitalId: null,
            },
            hospitals: [],
            loadingHospitals: false,
            loading: false,
            error: null,
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
        validateForm() {
            const rules = {
                lastname: { 
                    required: true, 
                    message: 'Фамилия обязательна' 
                },
                firstname: { 
                    required: true, 
                    message: 'Имя обязательно' 
                },
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
                    errorMsg: 'Телефон должен содержать 11 цифр (8 или 7) в формате 8(XXX)XXX-XX-XX' },
                hospitalId: { 
                    required: true, 
                    message: 'Больница обязательна' 
                },
                address: { 
                    required: true, 
                    message: 'Адрес обязателен' 
                },
                passport: { 
                    required: true, 
                    message: 'Паспорт обязателен',
                    validator: (val) => !val || isValidPassport(val), 
                    errorMsg: 'Паспорт должен быть в формате XXXX XXXXXX (4 и 6 цифр)' 
                },
                snils: { 
                    required: true, 
                    message: 'СНИЛС обязателен',
                    validator: (val) => !val || isValidSnils(val), 
                    errorMsg: 'СНИЛС должен содержать 11 цифр в формате XXX-XXX-XXX XX' 
                },
            };

            for (const [field, rule] of Object.entries(rules)) {
                const value = this.patient[field];
                if (rule.required && !value) {
                    return rule.message;
                }
                if (rule.validator && !rule.validator(value)) {
                    return rule.errorMsg;
                }
            }
            return null;
        },
        async submitForm() {
            const validationError = this.validateForm();
            if (validationError) {
                this.error = validationError;
                return;
            }

            this.loading = true;
            this.error = null;

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
                    comment: this.patient.comment,
                    hospital: `/api/hospitals/${this.patient.hospitalId}`,
                };

                const createdPatient = await patientApi.create(patientData);
                this.$router.push(`/patient/${createdPatient.id}/treatment/add`);
            } catch (err) {
                console.error(err);
                this.error = parseApiError(err);
            } finally {
                this.loading = false;
            }
        }
    },
};