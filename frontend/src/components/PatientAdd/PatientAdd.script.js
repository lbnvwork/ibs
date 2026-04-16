import { hospitalApi } from '@/api/hospitals';
import { patientApi } from '@/api/patients';

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
            } catch (error) {
                console.error('Ошибка при загрузке больниц:', error);
            } finally {
                this.loadingHospitals = false;
            }
        },
        async submitForm() {
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

                console.log('Отправляемые данные:', patientData);
                const createdPatient = await patientApi.create(patientData);
                const patientId = createdPatient.id;
                //this.$router.push(`/patient/${patientId}`);
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.detail || 'Ошибка при сохранении пациента';
            } finally {
                this.loading = false;
            }
        }
    }
};