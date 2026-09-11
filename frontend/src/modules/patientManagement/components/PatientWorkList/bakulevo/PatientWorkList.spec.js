import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PatientWorkList from './PatientWorkList.vue'
import { useBakulevoWorkListStore } from '@/modules/patientManagement/stores/bakulevo/workListStore'
import { useHospitalStore } from '@/modules/shared/stores/hospitalStore'
import { useDrugGroupStore } from '@/modules/shared/stores/drugGroupStore'
import { drugApi } from '@/modules/shared/api/drug'

vi.mock('@/modules/shared/api/drug', () => ({ drugApi: { getAll: vi.fn() } }))

function mountPatientWorkList() {
  setActivePinia(createPinia())
  drugApi.getAll.mockResolvedValue({ member: [{ id: 1, nominative: 'варфарин' }, { id: 2, nominative: 'дабигатран' }] })

  const store = useBakulevoWorkListStore()
  store.fetchWorkListData = vi.fn().mockResolvedValue()
  store.setSelectedDiagnosisCodes = vi.fn()
  store.setSearchQuery = vi.fn()
  store.setHospital = vi.fn()
  store.setDrugGroup = vi.fn()
  store.setItemsPerPage = vi.fn()
  store.nextPage = vi.fn()
  store.prevPage = vi.fn()
  store.resetFilters = vi.fn()

  const hospitalStore = useHospitalStore()
  hospitalStore.loadHospitals = vi.fn()

  const drugGroupStore = useDrugGroupStore()
  drugGroupStore.loadDrugGroups = vi.fn()

  const wrapper = mount(PatientWorkList, {
    global: { stubs: { PatientTable: true, MultiDiagnosisSelect: true } }
  })
  return { wrapper, store }
}

describe('PatientWorkList.vue (Бакулево)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders a header with the «Пациенты» title and the refresh button', () => {
    const { wrapper } = mountPatientWorkList()
    expect(wrapper.find('.list-header').exists()).toBe(true)
    expect(wrapper.find('.list-title').text()).toBe('Пациенты')
    expect(wrapper.find('.refresh-btn').exists()).toBe(true)
  })

  it('renders the filter bar with drug, ЛПУ, Категории, Нозологии and search', () => {
    const { wrapper } = mountPatientWorkList()
    expect(wrapper.find('.list-filters').exists()).toBe(true)
    expect(wrapper.find('#drug-filter').exists()).toBe(true)
    expect(wrapper.find('#hospital-filter').exists()).toBe(true)
    expect(wrapper.find('#category-filter').exists()).toBe(true)
    expect(wrapper.find('.select-filter--diagnoses').exists()).toBe(true)
    expect(wrapper.find('input[type="search"]').exists()).toBe(true)
  })

  it('resets all filters on refresh', async () => {
    const { wrapper, store } = mountPatientWorkList()
    await flushPromises()
    store.resetFilters.mockClear()

    wrapper.vm.onRefresh()

    expect(store.resetFilters).toHaveBeenCalledWith(1)
  })

  it('delegates drug change to the store and refetches', async () => {
    const { wrapper, store } = mountPatientWorkList()
    await flushPromises()
    store.fetchWorkListData.mockClear()

    wrapper.vm.onDrugChange({ target: { value: '2' } })
    await flushPromises()

    expect(store.activeDrugId).toBe(2)
    expect(store.fetchWorkListData).toHaveBeenCalledWith(2, 1)
  })

  it('auto-selects the first drug tab and fetches its data', async () => {
    const { store } = mountPatientWorkList()
    await flushPromises()

    expect(store.activeDrugId).toBe(1)
    expect(store.fetchWorkListData).toHaveBeenCalledWith(1, 1)
  })

  it('forwards diagnosis code selection changes to the store', async () => {
    const { wrapper, store } = mountPatientWorkList()
    await flushPromises()

    wrapper.vm.selectedDiagnosisCodes.push('I80')
    await flushPromises()

    expect(store.setSelectedDiagnosisCodes).toHaveBeenCalledWith(['I80'])
  })

  it('delegates pagination to the store', async () => {
    const { wrapper, store } = mountPatientWorkList()
    await flushPromises()

    wrapper.vm.nextPage()
    wrapper.vm.prevPage()

    expect(store.nextPage).toHaveBeenCalled()
    expect(store.prevPage).toHaveBeenCalled()
  })

  it('delegates hospital/category/page-size changes to the store', async () => {
    const { wrapper, store } = mountPatientWorkList()
    await flushPromises()

    wrapper.vm.onHospitalChange({ target: { value: '7' } })
    wrapper.vm.onDrugGroupChange({ target: { value: '3' } })
    wrapper.vm.setItemsPerPage(20)

    expect(store.setHospital).toHaveBeenCalledWith(7)
    expect(store.setDrugGroup).toHaveBeenCalledWith(3)
    expect(store.setItemsPerPage).toHaveBeenCalledWith(20)
  })
})
