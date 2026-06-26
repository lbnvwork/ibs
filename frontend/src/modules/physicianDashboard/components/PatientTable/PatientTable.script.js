import MultiDiagnosisSelect from '@/components/common/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';

export default {
  components: { MultiDiagnosisSelect },
  name: 'PatientTable',
  props: {
    tabs: Array,
    activeTab: [String, Number],
    selectedDiagnosis: Array,
    patients: Array,
    loading: Boolean,
    error: String,
    totalPages: Number,
    currentPage: Number,
    pageInput: [Number, String],
    showDiagnosisFilter: {
      type: Boolean,
      default: true,
    },
  },
  emits: [
    'update:activeTab', 'update:selectedDiagnosis', 'update:pageInput',
    'goToPage', 'nextPage', 'prevPage', 'firstPage', 'lastPage'
  ],
  computed: {
    selectedDiagnosisLocal: {
      get() { return this.selectedDiagnosis; },
      set(val) { this.$emit('update:selectedDiagnosis', val); }
    },
    colspan() {
      return 100;
    }
  }
};