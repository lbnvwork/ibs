// frontend/src/modules/patientManagement/components/PatientMonitoring/PatientMonitoring.script.js

import { computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useMonitoringStore } from '@/modules/patientManagement/stores/monitoringStore';
import { useWorkListStore } from '@/modules/patientManagement/stores/workListStore';
import { usePagination } from '@/modules/shared/composables/usePagination';
import { useDrugTabs } from '@/modules/patientManagement/composables/useDrugTabs';
import PatientTable from '@/modules/patientManagement/components/PatientTable/PatientTable.vue';

export default {
  name: 'PatientMonitoring',
  components: { PatientTable },
  setup() {
    const store = useMonitoringStore();
    const workListStore = useWorkListStore();
    const { pageInput, goToPage } = usePagination(store);
    const { tabs } = useDrugTabs();

    const { patients, loading, error, totalPages, currentPage } = storeToRefs(store);

    const activeTab = computed({
      get: () => store.activeDrugId,
      set: (val) => { store.activeDrugId = val; },
    });

    workListStore.setSelectedDiagnosisCodes([]);

    watch(tabs, (newTabs) => {
      if (newTabs.length > 0 && !store.activeDrugId) {
        activeTab.value = newTabs[0].id;
      }
    }, { immediate: true });

    watch(() => store.activeDrugId, (newDrugId) => {
      if (newDrugId) {
        store.fetchMonitoringData(newDrugId, 1);
      }
    });

    return {
      patients,
      loading,
      error,
      totalPages,
      currentPage,
      nextPage: () => store.nextPage(),
      prevPage: () => store.prevPage(),
      firstPage: () => store.firstPage(),
      lastPage: () => store.lastPage(),
      pageInput,
      goToPage,
      tabs,
      activeTab,
      showDiagnosisFilter: false,
    };
  },
};