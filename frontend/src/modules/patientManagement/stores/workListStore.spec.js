import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useWorkListStore } from './workListStore'
import { patientApi } from '@/modules/shared/api/patients'
import { treatmentApi } from '@/modules/shared/api/treatments'
import { testHistoryApi } from '@/modules/shared/api/testHistory'
import { vitalsApi } from '@/modules/shared/api/vitals'

vi.mock('@/modules/shared/api/patients', () => ({ patientApi: { getAll: vi.fn() } }))
vi.mock('@/modules/shared/api/treatments', () => ({ treatmentApi: { getAllWithoutPagination: vi.fn() } }))
vi.mock('@/modules/shared/api/testHistory', () => ({ testHistoryApi: { getLatestByTreatments: vi.fn() } }))
vi.mock('@/modules/shared/api/vitals', () => ({ vitalsApi: { getBatch: vi.fn() } }))

function mockOnePatientScenario({ mno, mnoFrom = 2, mnoTo = 3 }) {
  patientApi.getAll.mockResolvedValue({
    items: [{ id: 1, lastname: 'Иванов', firstname: 'Пётр', secondName: 'Сергеевич', birthday: '1980-01-01' }],
    totalItems: 1,
    view: { next: null, previous: null }
  })
  treatmentApi.getAllWithoutPagination.mockResolvedValue({
    member: [{ id: 10, patient: '/api/patients/1', diagnosis: 'Тромбоз', mnoFrom, mnoTo }]
  })
  vitalsApi.getBatch.mockResolvedValue({ data: { member: [] } })
  testHistoryApi.getLatestByTreatments.mockResolvedValue([{ treatment: '/api/treatments/10', mno }])
}

describe('workListStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('does nothing when no drug is selected', async () => {
    const store = useWorkListStore()
    await store.fetchWorkListData(null)
    expect(patientApi.getAll).not.toHaveBeenCalled()
  })

  it('highlights red when the latest MNO is above the treatment range', async () => {
    mockOnePatientScenario({ mno: 3.5, mnoFrom: 2, mnoTo: 3 })

    const store = useWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.patients).toHaveLength(1)
    expect(store.patients[0].highlightRed).toBe(true)
    expect(store.patients[0].highlightBlue).toBe(false)
    expect(store.patients[0].name).toBe('Иванов Пётр Сергеевич')
    expect(store.patients[0].diagnosis).toBe('Тромбоз')
  })

  it('highlights blue when the latest MNO is below the treatment range', async () => {
    mockOnePatientScenario({ mno: 1.2, mnoFrom: 2, mnoTo: 3 })

    const store = useWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.patients[0].highlightBlue).toBe(true)
    expect(store.patients[0].highlightRed).toBe(false)
  })

  it('highlights neither when the latest MNO is within range', async () => {
    mockOnePatientScenario({ mno: 2.5, mnoFrom: 2, mnoTo: 3 })

    const store = useWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.patients[0].highlightRed).toBe(false)
    expect(store.patients[0].highlightBlue).toBe(false)
  })

  it('resets to an empty list when no patients are returned', async () => {
    patientApi.getAll.mockResolvedValue({ items: [], totalItems: 0, view: {} })

    const store = useWorkListStore()
    store.patients = [{ id: 99 }]
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.patients).toEqual([])
    expect(store.totalPages).toBe(0)
  })

  it('computes totalPages from totalItems and itemsPerPage', async () => {
    patientApi.getAll.mockResolvedValue({
      items: [{ id: 1, lastname: 'A', firstname: 'B', secondName: 'C', birthday: '1980-01-01' }],
      totalItems: 65,
      view: { next: '/next', previous: null }
    })
    treatmentApi.getAllWithoutPagination.mockResolvedValue({ member: [] })
    vitalsApi.getBatch.mockResolvedValue({ data: { member: [] } })

    const store = useWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.totalPages).toBe(3) // ceil(65 / 30)
    expect(store.nextPageUrl).toBe('/next')
  })

  it('includes diagnosisCode in the filters once codes are selected, and refetches on change', async () => {
    mockOnePatientScenario({ mno: 2.5 })

    const store = useWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')
    patientApi.getAll.mockClear()

    store.setSelectedDiagnosisCodes(['I80'])
    await vi.waitFor(() => expect(patientApi.getAll).toHaveBeenCalled())

    expect(patientApi.getAll).toHaveBeenCalledWith(
      1,
      30,
      { drug: '/api/drugs/1', diagnosisCode: ['I80'] },
      { lastname: 'asc' }
    )
  })

  it('reports an error message when a request fails', async () => {
    patientApi.getAll.mockRejectedValue(new Error('network down'))

    const store = useWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.error).toBe('network down')
    expect(store.loading).toBe(false)
  })

  describe('pagination guards', () => {
    it('nextPage/prevPage only navigate when a corresponding URL is known', async () => {
      mockOnePatientScenario({ mno: 2.5 })
      const store = useWorkListStore()
      await store.fetchWorkListData('/api/drugs/1')
      patientApi.getAll.mockClear()

      await store.prevPage() // prevPageUrl is null
      expect(patientApi.getAll).not.toHaveBeenCalled()
    })

    it('setPage ignores out-of-range page numbers', async () => {
      mockOnePatientScenario({ mno: 2.5 })
      const store = useWorkListStore()
      await store.fetchWorkListData('/api/drugs/1')
      patientApi.getAll.mockClear()

      await store.setPage(999)
      expect(patientApi.getAll).not.toHaveBeenCalled()
    })
  })
})
