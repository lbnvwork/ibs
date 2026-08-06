import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { flushPromises } from '@vue/test-utils'
import { usePatientListStore } from './patientListStore'
import { patientApi } from '@/modules/shared/api/patients'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/patients', () => ({
  patientApi: { getAll: vi.fn(), getOne: vi.fn() }
}))

vi.mock('@/modules/shared/api/client', () => ({
  default: { post: vi.fn() }
}))

function patientsPage(items, next = null) {
  return { items, totalItems: items.length, next }
}

describe('patientListStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    apiClient.post.mockResolvedValue({ data: { member: [] } })
  })

  it('loadMore appends patients, advances the page and stops when hasMore is false', async () => {
    patientApi.getAll.mockResolvedValueOnce(patientsPage([{ id: 1, lastname: 'Иванов' }], null))

    const store = usePatientListStore()
    await store.loadMore()
    await flushPromises()

    expect(store.allPatientIds).toEqual([1])
    expect(store.nextPage).toBe(2)
    expect(store.hasMore).toBe(false)
    expect(store.selectedPatient).toEqual({ id: 1, lastname: 'Иванов' })
  })

  it('does not fetch again while a load is already in progress or there is nothing more', async () => {
    const store = usePatientListStore()
    store.loading = true
    await store.loadMore()
    expect(patientApi.getAll).not.toHaveBeenCalled()

    store.loading = false
    store.hasMore = false
    await store.loadMore()
    expect(patientApi.getAll).not.toHaveBeenCalled()
  })

  it('marks hasMore false and stops when a page comes back empty', async () => {
    patientApi.getAll.mockResolvedValueOnce(patientsPage([]))

    const store = usePatientListStore()
    await store.loadMore()

    expect(store.hasMore).toBe(false)
    expect(store.allPatientIds).toEqual([])
  })

  it('passes hospital, search and drug group filters through to the API', async () => {
    patientApi.getAll.mockResolvedValueOnce(patientsPage([]))

    const store = usePatientListStore()
    store.hospitalFilter = 5
    store.searchQuery = 'Иванов'
    store.drugGroupFilter = 2
    await store.loadMore()

    expect(patientApi.getAll).toHaveBeenCalledWith(
      1,
      expect.any(Number),
      { hospital: '/api/hospitals/5', lastname: 'Иванов', drugGroup: 2 },
      { lastname: 'asc' }
    )
  })

  it('loadStatuses fetches only ids that are not already known and stores the result', async () => {
    apiClient.post.mockResolvedValueOnce({ data: { member: [{ id: 1, status: 'активный' }] } })

    const store = usePatientListStore()
    store.statuses = { 2: 'неактивный' }
    await store.loadStatuses([1, 2])

    expect(apiClient.post).toHaveBeenCalledWith('/patients/status', { ids: [1] })
    expect(store.statuses).toEqual({ 1: 'активный', 2: 'неактивный' })
  })

  it('loadStatuses is a no-op when every id is already known', async () => {
    const store = usePatientListStore()
    store.statuses = { 1: 'активный' }
    await store.loadStatuses([1])

    expect(apiClient.post).not.toHaveBeenCalled()
  })

  describe('selectPatient', () => {
    it('uses the cached patient without calling the API', async () => {
      const store = usePatientListStore()
      store.rawPatients.set(1, { id: 1, lastname: 'Иванов' })

      await store.selectPatient(1)

      expect(patientApi.getOne).not.toHaveBeenCalled()
      expect(store.selectedPatient).toEqual({ id: 1, lastname: 'Иванов' })
    })

    it('fetches and caches an uncached patient', async () => {
      patientApi.getOne.mockResolvedValue({ id: 2, lastname: 'Петров' })

      const store = usePatientListStore()
      await store.selectPatient(2)

      expect(store.rawPatients.get(2)).toEqual({ id: 2, lastname: 'Петров' })
      expect(store.selectedPatient).toEqual({ id: 2, lastname: 'Петров' })
    })

    it('records an error message when the fetch fails', async () => {
      patientApi.getOne.mockRejectedValue(new Error('not found'))

      const store = usePatientListStore()
      await store.selectPatient(99)

      expect(store.error).toBe('Не удалось загрузить данные пациента')
    })
  })

  describe('search', () => {
    it('searchPatients sets the query and reloads the list', async () => {
      patientApi.getAll.mockResolvedValue(patientsPage([]))

      const store = usePatientListStore()
      await store.searchPatients('Иванов')

      expect(store.searchQuery).toBe('Иванов')
      expect(patientApi.getAll).toHaveBeenCalled()
    })

    it('an empty/whitespace query clears the search instead', async () => {
      patientApi.getAll.mockResolvedValue(patientsPage([]))

      const store = usePatientListStore()
      store.searchQuery = 'stale'
      await store.searchPatients('   ')

      expect(store.searchQuery).toBe('')
    })
  })

  describe('displayedPatients getter', () => {
    it('transforms cached patients in id order and skips missing ones', () => {
      const store = usePatientListStore()
      store.allPatientIds = [1, 2]
      store.rawPatients.set(1, { id: 1, lastname: 'Иванов', firstname: 'Пётр' })
      store.statuses = { 1: 'активный' }
      // id 2 has no cached patient (e.g. evicted) and must be filtered out

      expect(store.displayedPatients).toEqual([{ id: 1, name: 'Иванов П.', status: 'активный' }])
      expect(store.totalLoaded).toBe(2)
    })
  })
})
