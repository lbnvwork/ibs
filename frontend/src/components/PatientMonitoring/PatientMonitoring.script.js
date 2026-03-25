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
    ...mapState(useMonitoringStore, ['patients', 'loading', 'error']),
    filteredPatients() {
      // Пока без фильтра по диагнозам – просто возвращаем всех
      return this.patients;
    },
  },
  watch: {
    activeTab: {
      immediate: true,
      handler(newDrugId) {
        if (newDrugId) {
          this.fetchMonitoringData(newDrugId);
        }
      },
    },
  },
  async created() {
    await this.loadDrugs();
  },
  methods: {
    ...mapActions(useMonitoringStore, ['fetchMonitoringData']),
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
    }
  },
}