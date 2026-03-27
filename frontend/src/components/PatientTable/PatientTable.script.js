export default {
  name: 'PatientTable',
  props: {
    tabs: Array,
    activeTab: [String, Number],
    selectedDiagnosis: String,
    patients: Array,
    loading: Boolean,
    error: String,
    totalPages: Number,
    currentPage: Number,
    pageInput: [Number, String],
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
      // Для правильного отображения сообщений о загрузке/ошибке/пустоте
      // можно вычислить количество колонок из слотов. Но для простоты поставим большое число.
      return 100;
    }
  }
};