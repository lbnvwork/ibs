import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMonitoringStore } from './monitoringStore'
import { patientApi } from '@/modules/shared/api/patients'
import { treatmentApi } from '@/modules/shared/api/treatments'
import { testHistoryApi } from '@/modules/shared/api/testHistory'
import { vitalsApi } from '@/modules/shared/api/vitals'

vi.mock('@/modules/shared/api/patients', () => ({ patientApi: { getAll: vi.fn() } }))
vi.mock('@/modules/shared/api/treatments', () => ({ treatmentApi: { getAllWithoutPagination: vi.fn() } }))
vi.mock('@/modules/shared/api/testHistory', () => ({ testHistoryApi: { getLatestByTreatments: vi.fn() } }))
vi.mock('@/modules/shared/api/vitals', () => ({ vitalsApi: { getBatch: vi.fn() } }))

describe('monitoringStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('does nothing when no drug is selected', async () => {
    const store = useMonitoringStore()
    await store.fetchMonitoringData(null)
    expect(patientApi.getAll).not.toHaveBeenCalled()
  })

  it('builds monitoring rows with MNO-range highlighting, same as the work list', async () => {
    patientApi.getAll.mockResolvedValue({
      items: [{ id: 1, lastname: 'Иванов', firstname: 'Пётр', secondName: 'Сергеевич', birthday: '1980-01-01' }],
      totalItems: 1,
      view: { next: null, previous: null }
    })
    treatmentApi.getAllWithoutPagination.mockResolvedValue({
      member: [{ id: 10, patient: '/api/patients/1', diagnosis: 'Тромбоз', mnoFrom: 2, mnoTo: 3 }]
    })
    vitalsApi.getBatch.mockResolvedValue({ data: { member: [] } })
    testHistoryApi.getLatestByTreatments.mockResolvedValue([{ treatment: '/api/treatments/10', mno: 3.5 }])

    const store = useMonitoringStore()
    await store.fetchMonitoringData('/api/drugs/1')

    expect(store.hasPatients).toBe(true)
    expect(store.patients[0].highlightRed).toBe(true)
    expect(store.patients[0].diagnosis).toBe('Тромбоз')
  })

  it('reports an error message when a request fails', async () => {
    patientApi.getAll.mockRejectedValue(new Error('network down'))

    const store = useMonitoringStore()
    await store.fetchMonitoringData('/api/drugs/1')

    expect(store.error).toBe('network down')
  })

  it('setDrug resets to page 1 and fetches', async () => {
    patientApi.getAll.mockResolvedValue({ items: [], totalItems: 0, view: {} })

    const store = useMonitoringStore()
    store.currentPage = 5
    await store.setDrug('/api/drugs/2')

    expect(store.currentPage).toBe(1)
    expect(store.activeDrugId).toBe('/api/drugs/2')
  })
})
