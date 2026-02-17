import { patients } from '@/data/patients.js';
import RiskScale from '@/components/RiskScale/RiskScale.vue';

export default {
  name: 'PatientHistory',
  components: {
    RiskScale
  },
  props: {
    id: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      patient: null,
      medicalData: []
    }
  },
  mounted() {
    if (this.id) {
      this.loadPatientData(this.id);
    }
  },
  methods: {
    loadPatientData(patientId) {
      const foundPatient = patients.find(p => p.id === parseInt(patientId));
      if (foundPatient) {
        this.patient = foundPatient;
        this.medicalData = foundPatient.medicalHistory || [];
      }
    }
  },
  watch: {
    id(newId) {
      if (newId) {
        this.loadPatientData(newId);
      }
    }
  }
}