import { patients } from '@/data/patients.js';

export default {
  name: 'PatientHistory',
  props: {
    id: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      patient: null,
      medicalData: [],
      isScrollable: false
    }
  },
  mounted() {
    if (this.id) {
      this.loadPatientData(this.id);
    }
    
    this.$nextTick(() => {
      window.addEventListener('resize', this.checkTableScroll);
      this.checkTableScroll();
    });
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.checkTableScroll);
  },
  methods: {
    loadPatientData(patientId) {
      const foundPatient = patients.find(p => p.id === parseInt(patientId));
      
      if (foundPatient) {
        this.patient = foundPatient;
        this.medicalData = foundPatient.medicalHistory || [];
        
        this.$nextTick(() => {
          setTimeout(() => {
            this.checkTableScroll();
          }, 100);
        });
      }
    },
    checkTableScroll() {
      this.$nextTick(() => {
        setTimeout(() => {
          if (this.$refs.medicalDataContainer) {
            const tableContainer = this.$refs.medicalDataContainer;
            const table = tableContainer.querySelector('table');
            if (table && tableContainer) {
              const isWideScreen = window.innerWidth > 1366;
              if (isWideScreen) {
                const tableContentWidth = Array.from(table.querySelectorAll('th, td'))
                  .reduce((sum, cell) => sum + cell.offsetWidth, 0);
                this.isScrollable = tableContentWidth > tableContainer.clientWidth;
              } else {
                this.isScrollable = table.scrollWidth > tableContainer.clientWidth;
              }
            }
          }
        }, 100);
      });
    }
  },
  watch: {
    id(newId) {
      if (newId) {
        this.loadPatientData(newId);
      }
    },
    medicalData() {
      this.$nextTick(() => {
        setTimeout(() => {
          this.checkTableScroll();
        }, 100);
      });
    }
  }
}