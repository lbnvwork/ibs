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
                comment: ''
            }
        };
    },
    methods: {
        submitForm() {
            // временно
            console.log('Форма отправлена', this.patient);
        }
    }
};