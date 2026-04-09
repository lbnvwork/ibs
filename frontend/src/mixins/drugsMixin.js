import { drugApi } from '@/api/drug';

export default {
  data() {
    return {
      tabs: [],
      drugsLoading: false,
      drugsError: null,
      activeTab: null,
    };
  },
  methods: {
    async loadDrugs() {
      this.drugsLoading = true;
      this.drugsError = null;
      try {
        const response = await drugApi.getAll({ order: { group: 'asc', id: 'asc' } });
        const drugs = response.member || response;
        this.tabs = drugs.map(drug => ({
          id: drug.id,
          name: drug.nominative.charAt(0).toUpperCase() + drug.nominative.slice(1),
          drugId: drug.id,
        }));
        if (this.tabs.length > 0 && !this.activeTab) {
          this.activeTab = this.tabs[0].drugId;
        }
      } catch (err) {
        this.drugsError = err.message || 'Ошибка загрузки препаратов';
        console.error('Ошибка загрузки препаратов', err);
      } finally {
        this.drugsLoading = false;
      }
    },
  },
  created() {
    this.loadDrugs();
  },
};