import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PatientWorkList from './PatientWorkList.vue'
import { useWorkListStore } from '@/modules/patientManagement/stores/workListStore'
import { drugApi } from '@/modules/shared/api/drug'

vi.mock('@/modules/shared/api/drug', () => ({ drugApi: { getAll: vi.fn() } }))

function mountPatientWorkList() {
  setActivePinia(createPinia())
  drugApi.getAll.mockResolvedValue({ member: [{ id: 1, nominative: 'варфарин' }, { id: 2, nominative: 'дабигатран' }] })
  const workListStore = useWorkListStore()
  workListStore.fetchWorkListData = vi.fn().mockResolvedValue()
  workListStore.setSelectedDiagnosisCodes = vi.fn()
  workListStore.nextPage = vi.fn()
  workListStore.prevPage = vi.fn()
  workListStore.firstPage = vi.fn()
  workListStore.lastPage = vi.fn()

  const wrapper = mount(PatientWorkList, {
    global: { stubs: { PatientTable: true } }
  })
  return { wrapper, workListStore }
}

describe('PatientWorkList.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows the diagnosis filter (unlike PatientMonitoring)', async () => {
    const { wrapper } = mountPatientWorkList()
    await flushPromises()
    expect(wrapper.vm.showDiagnosisFilter).toBe(true)
  })

  it('auto-selects the first drug tab and fetches its data', async () => {
    const { workListStore } = mountPatientWorkList()
    await flushPromises()

    expect(workListStore.activeDrugId).toBe(1)
    expect(workListStore.fetchWorkListData).toHaveBeenCalledWith(1, 1)
  })

  it('fetches again when the active tab changes', async () => {
    const { wrapper, workListStore } = mountPatientWorkList()
    await flushPromises()
    workListStore.fetchWorkListData.mockClear()

    wrapper.vm.activeTab = 2
    await flushPromises()

    expect(workListStore.fetchWorkListData).toHaveBeenCalledWith(2, 1)
  })

  it('forwards diagnosis code selection changes to the store', async () => {
    const { wrapper, workListStore } = mountPatientWorkList()
    await flushPromises()

    wrapper.vm.selectedDiagnosisCodes.push('I80')
    await flushPromises()

    expect(workListStore.setSelectedDiagnosisCodes).toHaveBeenCalledWith(['I80'])
  })

  it('delegates page navigation to the work list store', async () => {
    const { wrapper, workListStore } = mountPatientWorkList()
    await flushPromises()

    wrapper.vm.nextPage()
    wrapper.vm.prevPage()
    wrapper.vm.firstPage()
    wrapper.vm.lastPage()

    expect(workListStore.nextPage).toHaveBeenCalled()
    expect(workListStore.prevPage).toHaveBeenCalled()
    expect(workListStore.firstPage).toHaveBeenCalled()
    expect(workListStore.lastPage).toHaveBeenCalled()
  })
})
