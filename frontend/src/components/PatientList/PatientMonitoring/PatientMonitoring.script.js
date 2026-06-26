import { mapState, mapActions } from 'pinia';
import { useMonitoringStore } from '@/stores/physicianDashboard/monitoringStore';
import PatientTable from '@/components/PatientList/PatientTable/PatientTable.vue';
import drugsMixin from '@/mixins/drugsMixin';
import paginationMixin from '@/mixins/paginationMixin';

export default {
  name: 'PatientMonitoring',
  components: { PatientTable },
  mixins: [drugsMixin, paginationMixin],
  computed: {
    ...mapState(useMonitoringStore, ['patients', 'loading', 'error']),
  },
  methods: {
    ...mapActions(useMonitoringStore, [
      'fetchMonitoringData',
      'setDrug',
      'setPage',
      'nextPage',
      'prevPage',
      'firstPage',
      'lastPage'
    ]),
  },
  watch: {
    activeTab: {
      immediate: true,
      handler(newDrugId) {
        if (newDrugId) {
          this.fetchMonitoringData(newDrugId, 1);
        }
      },
    },
  },
};