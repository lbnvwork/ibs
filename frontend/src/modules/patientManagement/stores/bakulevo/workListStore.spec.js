import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useBakulevoWorkListStore } from './workListStore'
import { patientApi } from '@/modules/shared/api/patients'
import { treatmentApi } from '@/modules/shared/api/treatments'
import { testHistoryApi } from '@/modules/shared/api/testHistory'
import { vitalsApi } from '@/modules/shared/api/vitals'
import { hospitalApi } from '@/modules/shared/api/hospitals'

vi.mock('@/modules/shared/api/patients', () => ({ patientApi: { getAll: vi.fn() } }))
vi.mock('@/modules/shared/api/treatments', () => ({ treatmentApi: { getAllWithoutPagination: vi.fn() } }))
vi.mock('@/modules/shared/api/testHistory', () => ({ testHistoryApi: { getLatestByTreatments: vi.fn() } }))
vi.mock('@/modules/shared/api/vitals', () => ({ vitalsApi: { getBatch: vi.fn() } }))
vi.mock('@/modules/shared/api/hospitals', () => ({ hospitalApi: { getAll: vi.fn() } }))

function mockScenario({ mno = 2.5, mnoFrom = 2, mnoTo = 3 } = {}) {
  patientApi.getAll.mockResolvedValue({
    items: [{
      id: 1,
      lastname: 'Иванов',
      firstname: 'Пётр',
      secondName: 'Сергеевич',
      birthday: '1980-04-12',
      sex: 1,
      hospital: '/api/hospitals/7',
    }],
    totalItems: 1,
    view: { next: null, previous: null },
  })
  treatmentApi.getAllWithoutPagination.mockResolvedValue({
    member: [{ id: 10, patient: '/api/patients/1', diagnosis: 'Тромбоз', mnoFrom, mnoTo }],
  })
  vitalsApi.getBatch.mockResolvedValue({ data: { member: [] } })
  testHistoryApi.getLatestByTreatments.mockResolvedValue([{ treatment: '/api/treatments/10', mno }])
  hospitalApi.getAll.mockResolvedValue([
    { '@id': '/api/hospitals/7', name: 'ФГБУ НМИЦ им. Бакулева' },
  ])
}

describe('workListBakulevoStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('does nothing when no drug is selected', async () => {
    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData(null)
    expect(patientApi.getAll).not.toHaveBeenCalled()
  })

  it('maps new columns: birthDate, sex, hospitalName and triage flags', async () => {
    mockScenario({ mno: 3.5 })

    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.patients).toHaveLength(1)
    expect(store.patients[0].name).toBe('Иванов Пётр Сергеевич')
    expect(store.patients[0].birthDate).toBe('12.04.1980')
    expect(store.patients[0].sex).toBe(1)
    expect(store.patients[0].hospitalName).toBe('ФГБУ НМИЦ им. Бакулева')
    expect(store.patients[0].highlightRed).toBe(true)
    expect(store.patients[0].highlightBlue).toBe(false)
  })

  it('falls back to em-dash for missing hospital and formats female sex', async () => {
    mockScenario({ mno: 2.5 })
    patientApi.getAll.mockResolvedValue({
      items: [{
        id: 2,
        lastname: 'Петров',
        firstname: 'Иван',
        secondName: 'Иванович',
        birthday: '1975-06-01',
        sex: 0,
      }],
      totalItems: 1,
      view: { next: null, previous: null },
    })

    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.patients[0].hospitalName).toBe('—')
    expect(store.patients[0].birthDate).toBe('01.06.1975')
    expect(store.patients[0].sex).toBe(0)
  })

  it('sends hospital, drugGroup and lastname filters', async () => {
    mockScenario({ mno: 2.5 })

    const store = useBakulevoWorkListStore()
    store.hospitalId = 7
    store.drugGroupId = 3
    store.searchQuery = 'Иванов'
    await store.fetchWorkListData('/api/drugs/1')

    expect(patientApi.getAll).toHaveBeenCalledWith(
      1,
      30,
      {
        drug: '/api/drugs/1',
        hospital: '/api/hospitals/7',
        drugGroup: 3,
        lastname: 'Иванов',
      },
      { lastname: 'asc' }
    )
  })

  it('setSearchQuery trims and refetches from page 1', async () => {
    mockScenario({ mno: 2.5 })
    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')
    patientApi.getAll.mockClear()

    store.setSearchQuery('  Смирнов  ')
    await vi.waitFor(() => expect(patientApi.getAll).toHaveBeenCalled())

    expect(store.searchQuery).toBe('Смирнов')
    expect(patientApi.getAll).toHaveBeenCalledWith(
      1, 30,
      { drug: '/api/drugs/1', lastname: 'Смирнов' },
      { lastname: 'asc' }
    )
  })

  it('setItemsPerPage refetches with the new page size', async () => {
    mockScenario({ mno: 2.5 })
    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')
    patientApi.getAll.mockClear()

    store.setItemsPerPage(10)
    await vi.waitFor(() => expect(patientApi.getAll).toHaveBeenCalled())

    expect(store.itemsPerPage).toBe(10)
    expect(patientApi.getAll).toHaveBeenCalledWith(1, 10, { drug: '/api/drugs/1' }, { lastname: 'asc' })
  })

  it('resetFilters сбрасывает фильтры и препарат к заданному, обновляя с первой страницы', async () => {
    mockScenario({ mno: 2.5 })
    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData('/api/drugs/2')
    store.hospitalId = 7
    store.drugGroupId = 3
    store.searchQuery = 'Иванов'
    store.selectedDiagnosisCodes = ['I80']
    patientApi.getAll.mockClear()

    await store.resetFilters('/api/drugs/1')

    expect(store.hospitalId).toBeNull()
    expect(store.drugGroupId).toBeNull()
    expect(store.searchQuery).toBe('')
    expect(store.selectedDiagnosisCodes).toEqual([])
    expect(store.currentPage).toBe(1)
    expect(store.activeDrugId).toBe('/api/drugs/1')
    expect(patientApi.getAll).toHaveBeenCalledWith(1, 30, { drug: '/api/drugs/1' }, { lastname: 'asc' })
  })

  it('reports an error message when a request fails', async () => {
    patientApi.getAll.mockRejectedValue(new Error('network down'))

    const store = useBakulevoWorkListStore()
    await store.fetchWorkListData('/api/drugs/1')

    expect(store.error).toBe('network down')
    expect(store.loading).toBe(false)
  })
})
