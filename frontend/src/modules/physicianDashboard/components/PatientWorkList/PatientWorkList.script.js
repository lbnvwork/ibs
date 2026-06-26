import { ref, computed, watch, toRefs } from 'vue';
import { useWorkListStore } from '@/modules/physicianDashboard/stores/workListStore';
import { usePagination } from '@/modules/physicianDashboard/composables/usePagination';
import { useDrugTabs } from '@/modules/physicianDashboard/composables/useDrugTabs';
import PatientTable from '@/modules/physicianDashboard/components/PatientTable/PatientTable.vue';

export default {
  name: 'PatientWorkList',
  components: { PatientTable },
  setup() {
    const store = useWorkListStore();
    const { pageInput, goToPage } = usePagination(store);
    const { tabs } = useDrugTabs();

    const selectedDiagnosisCodes = ref([]);

    const activeTab = computed({
      get: () => store.activeDrugId,
      set: (val) => { store.activeDrugId = val; },
    });

    // Автовыбор первого препарата
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
      ...toRefs(store),
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