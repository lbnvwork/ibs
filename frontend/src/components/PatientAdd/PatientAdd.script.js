import { hospitalApi } from '@/api/hospitals';
import { patientApi } from '@/api/patients';
import { formatPhone, formatPassport, formatSnils } from '@/utils/formatters';
import { isValidPhone, isValidPassport, isValidSnils } from '@/utils/validators';

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
            if (!this.patient.lastname) return 'Фамилия обязательна';
            if (!this.patient.firstname) return 'Имя обязательно';
            if (!this.patient.birthday) return 'Дата рождения обязательна';
            if (!this.patient.smsPhone) return 'Телефон обязателен';
            if (!this.patient.hospitalId) return 'Больница обязательна';

            if (!isValidPhone(this.patient.smsPhone)) {
                return 'Телефон должен содержать 11 цифр (8 или 7) в формате 8(XXX)XXX-XX-XX';
            }
            if (this.patient.passport && !isValidPassport(this.patient.passport)) {
                return 'Паспорт должен быть в формате XXXX XXXXXX (4 и 6 цифр)';
            }
            if (this.patient.snils && !isValidSnils(this.patient.snils)) {
                return 'СНИЛС должен содержать 11 цифр в формате XXX-XXX-XXX XX';
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
                    sex: this.patient.sex,
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
                const detail = err.response?.data?.detail;
                if (detail && detail.includes('duplicate key value violates unique constraint')) {
                    this.error = 'Пациент с такими данными (возможно, телефон) уже существует.';
                } else {
                    this.error = detail || 'Ошибка при сохранении пациента';
                }
            } finally {
                this.loading = false;
            }
        },
    },
};