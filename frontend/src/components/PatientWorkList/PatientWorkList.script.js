import { mapState, mapActions } from 'pinia';
import { useMonitoringStore } from '@/stores/monitoringStore';
import { drugApi } from '@/api/drug';
import PatientTable from '@/components/PatientTable/PatientTable.vue';

export default {
  name: 'PatientWorkList',
  components: { PatientTable },
  data() {
    return {
      activeTab: null,
      selectedDiagnosis: 'all',
      tabs: [],
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
  async created() {
    await this.loadDrugs();
  },
  methods: {
    ...mapActions(useMonitoringStore, ['fetchMonitoringData', 'setPage', 'nextPage', 'prevPage', 'firstPage', 'lastPage']),
    async loadDrugs() {
      try {
        const response = await drugApi.getAll({
          order: { group: 'asc', id: 'asc' },
        });
        const drugs = response.member || response;
        this.tabs = drugs.map(drug => ({
          id: drug.id,
          name: drug.nominative,
          drugId: drug.id,
        }));
        if (this.tabs.length > 0) {
          this.activeTab = this.tabs[0].drugId;
        }
      } catch (err) {
        console.error('Ошибка загрузки списка препаратов', err);
      }
    },
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