import { computed, watch, toRefs } from 'vue';
import { useMonitoringStore } from '@/modules/physicianDashboard/stores/monitoringStore';
import { useWorkListStore } from '@/modules/physicianDashboard/stores/workListStore';
import { usePagination } from '@/modules/physicianDashboard/composables/usePagination';
import { useDrugTabs } from '@/modules/physicianDashboard/composables/useDrugTabs';
import PatientTable from '@/modules/physicianDashboard/components/PatientTable/PatientTable.vue';

export default {
  name: 'PatientMonitoring',
  components: { PatientTable },
  setup() {
    const store = useMonitoringStore();
    const workListStore = useWorkListStore();
    const { pageInput, goToPage } = usePagination(store);
    const { tabs } = useDrugTabs();

    const activeTab = computed({
      get: () => store.activeDrugId,
      set: (val) => { store.activeDrugId = val; },
    });

    // Сброс фильтра рабочего списка при входе в мониторинг
    workListStore.setSelectedDiagnosisCodes([]);

    // Автовыбор первого препарата, если он ещё не выбран
    watch(tabs, (newTabs) => {
      if (newTabs.length > 0 && !store.activeDrugId) {
        activeTab.value = newTabs[0].id;
      }
    }, { immediate: true });

    // Загрузка данных при смене препарата
    watch(() => store.activeDrugId, (newDrugId) => {
      if (newDrugId) {
        store.fetchMonitoringData(newDrugId, 1);
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
      showDiagnosisFilter: false,
    };
  },
};