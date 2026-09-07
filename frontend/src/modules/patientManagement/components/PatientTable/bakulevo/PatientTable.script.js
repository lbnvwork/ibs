import MultiDiagnosisSelect from '@/modules/shared/components/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';

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
    totalCount: {
      type: Number,
      default: 0,
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
    },
    patientCount() {
      return this.totalCount || this.patients.length;
    }
  },
  methods: {
    triageBadge(patient) {
      const p = patient || {};
      if (p.highlightRed) return { label: 'Угроза кровотечения', type: 'danger' };
      if (p.highlightBlue) return { label: 'Риск тромбоза', type: 'info' };
      return { label: 'В норме', type: 'success' };
    }
  }
};