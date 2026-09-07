import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PatientMonitoring from './PatientMonitoring.vue'
import { useMonitoringStore } from '@/modules/patientManagement/stores/monitoringStore'
import { useWorkListStore } from '@/modules/patientManagement/stores/workListStore'
import { drugApi } from '@/modules/shared/api/drug'

vi.mock('@/modules/shared/api/drug', () => ({ drugApi: { getAll: vi.fn() } }))

function mountPatientMonitoring() {
  setActivePinia(createPinia())
  drugApi.getAll.mockResolvedValue({ member: [{ id: 1, nominative: 'варфарин' }, { id: 2, nominative: 'дабигатран' }] })
  const monitoringStore = useMonitoringStore()
  monitoringStore.fetchMonitoringData = vi.fn().mockResolvedValue()
  monitoringStore.nextPage = vi.fn()
  monitoringStore.prevPage = vi.fn()
  monitoringStore.firstPage = vi.fn()
  monitoringStore.lastPage = vi.fn()

  const wrapper = mount(PatientMonitoring, {
    global: { stubs: { PatientTable: true } }
  })
  return { wrapper, monitoringStore }
}

describe('PatientMonitoring.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('clears the work list selected diagnosis codes on mount', () => {
    setActivePinia(createPinia())
    const workListStore = useWorkListStore()
    workListStore.setSelectedDiagnosisCodes = vi.fn()
    drugApi.getAll.mockResolvedValue({ member: [] })

    mount(PatientMonitoring, { global: { stubs: { PatientTable: true } } })

    expect(workListStore.setSelectedDiagnosisCodes).toHaveBeenCalledWith([])
  })

  it('auto-selects the first drug tab once tabs load, and fetches its data', async () => {
    const { monitoringStore } = mountPatientMonitoring()
    await flushPromises()

    expect(monitoringStore.activeDrugId).toBe(1)
    expect(monitoringStore.fetchMonitoringData).toHaveBeenCalledWith(1, 1)
  })

  it('fetches monitoring data again when the active drug tab changes', async () => {
    const { wrapper, monitoringStore } = mountPatientMonitoring()
    await flushPromises()
    monitoringStore.fetchMonitoringData.mockClear()

    wrapper.vm.activeTab = 2
    await flushPromises()

    expect(monitoringStore.fetchMonitoringData).toHaveBeenCalledWith(2, 1)
  })

  it('delegates page navigation to the monitoring store', async () => {
    const { wrapper, monitoringStore } = mountPatientMonitoring()
    await flushPromises()

    wrapper.vm.nextPage()
    wrapper.vm.prevPage()
    wrapper.vm.firstPage()
    wrapper.vm.lastPage()

    expect(monitoringStore.nextPage).toHaveBeenCalled()
    expect(monitoringStore.prevPage).toHaveBeenCalled()
    expect(monitoringStore.firstPage).toHaveBeenCalled()
    expect(monitoringStore.lastPage).toHaveBeenCalled()
  })

  it('disables the diagnosis filter (monitoring shows all patients on the active drug)', async () => {
    const { wrapper } = mountPatientMonitoring()
    await flushPromises()
    expect(wrapper.vm.showDiagnosisFilter).toBe(false)
  })
})
