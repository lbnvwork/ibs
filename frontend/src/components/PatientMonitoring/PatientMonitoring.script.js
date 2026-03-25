import { mapState, mapActions } from 'pinia';
import { useMonitoringStore } from '@/stores/monitoringStore';
import { drugApi } from '@/api/drug';

export default {
  name: 'PatientMonitoring',
  data() {
    return {
      activeFilter: 'monitoring',
      activeTab: null,
      selectedDiagnosis: 'all',
      tabs: [],
    };
  },
  computed: {
    ...mapState(useMonitoringStore, ['patients', 'loading', 'error', 'currentPage', 'totalPages']),
    filteredPatients() {
      // Пока без фильтра по диагнозам – просто возвращаем всех
      return this.patients;
    },
  },
  watch: {
    currentPage: {
        immediate: true,
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
    goToPageInput() {
        let page = parseInt(this.pageInput);
        if (isNaN(page)) page = 1;
        page = Math.min(Math.max(page, 1), this.totalPages);
        if (page !== this.currentPage) {
            this.setPage(page);
        }
    },
  },
}