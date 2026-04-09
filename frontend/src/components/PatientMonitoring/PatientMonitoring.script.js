import { mapState } from 'pinia';
import { useMonitoringStore } from '@/stores/monitoringStore';
import PatientTable from '@/components/PatientTable/PatientTable.vue';
import drugsMixin from '@/mixins/drugsMixin';
import diagnosisFilterMixin from '@/mixins/diagnosisFilterMixin';
import paginationMixin from '@/mixins/paginationMixin';

export default {
  name: 'PatientMonitoring',
  components: { PatientTable },
  mixins: [drugsMixin, diagnosisFilterMixin, paginationMixin],
  computed: {
    ...mapState(useMonitoringStore, ['patients', 'loading', 'error']),
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