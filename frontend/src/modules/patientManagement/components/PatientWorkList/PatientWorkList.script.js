import { ref, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useWorkListStore } from '@/modules/patientManagement/stores/workListStore';
import { usePagination } from '@/modules/shared/composables/usePagination';
import { useDrugTabs } from '@/modules/patientManagement/composables/useDrugTabs';
import PatientTable from '@/modules/patientManagement/components/PatientTable/PatientTable.vue';

export default {
  name: 'PatientWorkList',
  components: { PatientTable },
  setup() {
    const store = useWorkListStore();
    const { pageInput, goToPage } = usePagination(store);
    const { tabs } = useDrugTabs();

    const selectedDiagnosisCodes = ref([]);

    const { patients, loading, error, totalPages, currentPage } = storeToRefs(store);

    const activeTab = computed({
      get: () => store.activeDrugId,
      set: (val) => { store.activeDrugId = val; },
    });

    watch(tabs, (newTabs) => {
      if (newTabs.length > 0 && !store.activeDrugId) {
        activeTab.value = newTabs[0].id;
      }
    }, { immediate: true });

    watch(selectedDiagnosisCodes, (newVal) => {
      store.setSelectedDiagnosisCodes(newVal);
    }, { deep: true });

    watch(() => store.activeDrugId, (newDrugId) => {
      if (newDrugId) {
        store.fetchWorkListData(newDrugId, 1);
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
      selectedDiagnosisCodes,
      showDiagnosisFilter: true,
    };
  },
};