import { ref, onMounted } from 'vue';
import { drugApi } from '@/api/drug';

export function useDrugTabs() {
  const tabs = ref([]);

  async function loadDrugs() {
    try {
      const response = await drugApi.getAll();
      const drugs = response.member || response.items || [];
      
      // Преобразуем названия: первая буква заглавная, остальные строчные
      const formatted = drugs.map(drug => ({
        id: drug.id,
        name: drug.nominative.charAt(0).toUpperCase() + drug.nominative.slice(1).toLowerCase(),
        style: '',
      }));

      // При желании можно отсортировать, чтобы варфарин был первым
      // (если нужно гарантировать порядок)
      // tabs.value = formatted.sort((a, b) => ...);
      tabs.value = formatted;
    } catch (err) {
      console.error('Ошибка загрузки препаратов:', err);
      tabs.value = [];
    }
  }

  onMounted(loadDrugs);

  return { tabs };
}