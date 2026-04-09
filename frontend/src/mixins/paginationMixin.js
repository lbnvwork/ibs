import { mapState, mapActions } from 'pinia';
import { useMonitoringStore } from '@/stores/monitoringStore';

export default {
  data() {
    return {
      pageInput: 1,
    };
  },
  computed: {
    ...mapState(useMonitoringStore, ['currentPage', 'totalPages']),
  },
  watch: {
    currentPage(val) {
      this.pageInput = val;
    },
  },
  methods: {
    ...mapActions(useMonitoringStore, [
        'setPage', 
        'nextPage', 
        'prevPage', 
        'firstPage', 
        'lastPage',
        'fetchMonitoringData',
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
};