import { mapState, mapActions } from 'pinia';
import { useMonitoringStore } from '@/stores/monitoringStore';
import PatientTable from '@/components/PatientTable/PatientTable.vue';
import drugsMixin from '@/mixins/drugsMixin';

export default {
  name: 'PatientMonitoring',
  components: { PatientTable },
  mixins: [drugsMixin],
  data() {
    return {
      activeTab: null,
      selectedDiagnosis: 'all',
      pageInput: 1,
    };
  },
  computed: {
    ...mapState(useMonitoringStore, ['patients', 'loading', 'error', 'currentPage', 'totalPages']),
    filteredPatients() {
      return this.patients;
    },
  },
  watch: {
    currentPage: {
        handler(val) {
            this.pageInput = val;
        }
    },
    activeTab: {
      immediate: true,
      handler(newDrugId) {
        if (newDrugId) {
          this.fetchMonitoringData(newDrugId, 1);
        }
      },
    },
  },
  methods: {
    ...mapActions(useMonitoringStore, [
      'fetchMonitoringData',
      'setPage',
      'nextPage',
      'prevPage',
      'firstPage',
      'lastPage'
    ]),
    goToPage(page) {
      let num = parseInt(page);
      if (isNaN(num)) num = 1;
      num = Math.min(Math.max(num, 1), this.totalPages);
      if (num !== this.currentPage) {
        this.setPage(num);
      }
    },
  },
}