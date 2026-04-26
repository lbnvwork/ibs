import { patientApi } from '@/api/patients';
import { treatmentApi } from '@/api/treatments';
import { drugApi } from '@/api/drug';
import { extractIdFromIri } from '@/utils/apiHelpers';
import { calculateAge, formatAge } from '@/utils/formatters';
import RiskScale from '@/components/RiskScale/RiskScale.vue';
import apiClient from '@/api/client';

export default {
  name: 'PatientHistory',
  components: { RiskScale },
  props: {
    id: { type: String, default: null }
  },
  data() {
    return {
      patient: null,
      loading: true,
      error: null,
      medicalData: []
    }
  },
  watch: {
    id(newId) {
      if (newId) this.loadPatientData();
    }
  },
  created() {
    if (this.id) this.loadPatientData();
  },
  methods: {
    async loadPatientData() {
      this.loading = true;
      this.error = null;
      try {
        const patientData = await patientApi.getOne(this.id);
        const treatmentResp = await treatmentApi.getAll({
          patient: `/api/patients/${this.id}`,
          active: true,
          itemsPerPage: 1,
          order: { begDt: 'desc' }
        });
        const treatments = treatmentResp.member || [];
        const treatment = treatments.length > 0 ? treatments[0] : null;

        let hospitalName = '';
        if (patientData.hospital) {
          const hospitalId = extractIdFromIri(patientData.hospital);
          if (hospitalId) {
            const hospResp = await apiClient.get(`/hospitals/${hospitalId}`);
            hospitalName = hospResp.data.name || '';
          }
        }

        let drugName = '';
        if (treatment?.drug) {
          const drugId = extractIdFromIri(treatment.drug);
          if (drugId) {
            const drugs = await drugApi.getAll().member || [];
            const found = drugs.find(d => d.id === drugId);
            if (found) drugName = found.nominative || found.genitive || '';
          }
        }

        const fullName = [
          patientData.lastname,
          patientData.firstname,
          patientData.secondName
        ].filter(Boolean).join(' ') || 'Без имени';
        const age = calculateAge(patientData.birthday);

        this.patient = {
          name: fullName,
          age: age ? formatAge(age) : '—',
          birthDate: patientData.birthday
            ? new Date(patientData.birthday).toLocaleDateString('ru-RU')
            : '—',
          address: patientData.address || '—',
          phone: patientData.smsPhone || '—',
          email: '—',
          passport: patientData.passport || '—',
          insurance: patientData.healthInsurance || '—',
          snils: patientData.snils || '—',
          hospital: hospitalName || '—',
          diagnosis: treatment?.diagnosis || '—',
          additionalConditions: treatment?.comorbidities
            ? [treatment.comorbidities] 
            : ['Нет данных'],
          consentSigned: false,
          riskScores: null,
          pharmacogenetics: [],
          mutations: []
        };
      } catch (err) {
        console.error('Ошибка загрузки истории:', err);
        this.error = 'Не удалось загрузить данные пациента.';
      } finally {
        this.loading = false;
      }
    }
  }
};