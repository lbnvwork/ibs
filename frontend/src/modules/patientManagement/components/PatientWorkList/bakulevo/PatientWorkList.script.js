import { ref, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useBakulevoWorkListStore } from '@/modules/patientManagement/stores/bakulevo/workListStore';
import { useHospitalStore } from '@/modules/shared/stores/hospitalStore';
import { useDrugGroupStore } from '@/modules/shared/stores/drugGroupStore';
import { usePagination } from '@/modules/shared/composables/usePagination';
import { useDrugTabs } from '@/modules/patientManagement/composables/useDrugTabs';
import debounce from 'lodash/debounce';
import PatientTable from '@/modules/patientManagement/components/PatientTable/bakulevo/PatientTable.vue';
import MultiDiagnosisSelect from '@/modules/shared/components/MultiDiagnosisSelect/MultiDiagnosisSelect.vue';

export default {
  name: 'PatientWorkList',
  components: { PatientTable, MultiDiagnosisSelect },
  setup() {
    const store = useBakulevoWorkListStore();
    const hospitalStore = useHospitalStore();
    const drugGroupStore = useDrugGroupStore();
    const { goToPage } = usePagination(store);
    const { tabs } = useDrugTabs();

    const selectedDiagnosisCodes = ref([]);
    const searchQuery = ref('');

    const {
      patients, loading, error, totalPages, currentPage, totalItems,
      itemsPerPage, hospitalId, drugGroupId,
    } = storeToRefs(store);

    const { hospitalOptions } = storeToRefs(hospitalStore);
    const { drugGroupOptions } = storeToRefs(drugGroupStore);

    const activeTab = computed({
      get: () => store.activeDrugId,
      set: (val) => { store.activeDrugId = val; },
    });

    watch(tabs, (newTabs) => {
      if (newTabs.length > 0 && !store.activeDrugId) {
        activeTab.value = newTabs[0].id;
        store.fetchWorkListData(newTabs[0].id, 1);
      }
    }, { immediate: true });

    watch(selectedDiagnosisCodes, (newVal) => {
      store.setSelectedDiagnosisCodes(newVal);
    }, { deep: true });

    const onSearchInput = debounce((query) => {
      store.setSearchQuery(query);
    }, 300);

    watch(searchQuery, (q) => onSearchInput(q));

    hospitalStore.loadHospitals();
    drugGroupStore.loadDrugGroups();

    // Кнопка «Обновить»: препарат → первый в списке (исходное состояние),
    // остальные фильтры → по умолчанию, страница → 1.
    const onRefresh = () => {
      const firstId = tabs.value[0]?.id ?? null;
      activeTab.value = firstId;
      store.resetFilters(firstId);
      searchQuery.value = '';
      selectedDiagnosisCodes.value = [];
    };

    return {
      patients,
      loading,
      error,
      totalPages,
      currentPage,
      totalItems,
      itemsPerPage,
      hospitalId,
      drugGroupId,
      hospitalOptions,
      drugGroupOptions,
      nextPage: () => store.nextPage(),
      prevPage: () => store.prevPage(),
      setItemsPerPage: (n) => store.setItemsPerPage(n),
      goToPage,
      tabs,
      activeTab,
      selectedDiagnosisCodes,
      searchQuery,
      onHospitalChange: (e) => store.setHospital(e.target.value ? Number(e.target.value) : null),
      onDrugGroupChange: (e) => store.setDrugGroup(e.target.value ? Number(e.target.value) : null),
      onDrugChange: (e) => {
        const id = Number(e.target.value);
        activeTab.value = id;
        store.fetchWorkListData(id, 1);
      },
      onRefresh,
    };
  },
};