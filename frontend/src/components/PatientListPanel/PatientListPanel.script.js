import { panelPatients } from '@/data/patients.js';

export default {
  name: 'PatientListPanel',
  data() {
    return {
      patients: panelPatients,
      selectedPatient: panelPatients[0],
      searchQuery: ''
    }
  },
  computed: {
    filteredPatients() {
      if (!this.searchQuery) return this.patients;
      
      const query = this.searchQuery.toLowerCase();
      return this.patients.filter(patient => 
        patient.name.toLowerCase().includes(query) ||
        patient.diagnosis.toLowerCase().includes(query)
      );
    }
  },
  methods: {
    selectPatient(patient) {
      this.selectedPatient = patient;
    },
    viewPatientHistory(patient) {
      this.$router.push(`/patient/${patient.id}`);
    }
  }
}