import { mapState, mapActions } from 'pinia';
import { useWorkListStore } from '@/stores/physicianDashboard/workListStore';
import PatientTable from '@/components/PatientList/PatientTable/PatientTable.vue';
import drugsMixin from '@/mixins/drugsMixin';
import diagnosisFilterMixin from '@/mixins/diagnosisFilterMixin';
import paginationMixin from '@/mixins/paginationMixin';

export default {
  name: 'PatientWorkList',
  components: { PatientTable },
  mixins: [drugsMixin, diagnosisFilterMixin, paginationMixin],
  computed: {
    ...mapState(useWorkListStore, ['patients', 'loading', 'error']),
  },
  methods: {
    ...mapActions(useWorkListStore, [
      'fetchWorkListData',
      'setSelectedDiagnosisCodes',
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
          this.fetchWorkListData(newDrugId, 1);
        }
      },
    },
  },
};