import { hospitalApi } from '@/api/hospitals';

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
        submitForm() {
            console.log('Форма отправлена', this.patient);
        }
    }
};