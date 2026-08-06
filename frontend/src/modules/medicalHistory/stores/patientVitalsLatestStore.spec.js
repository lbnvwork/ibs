import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePatientVitalsLatestStore } from './patientVitalsLatestStore'
import { vitalsApi } from '@/modules/shared/api/vitals'

vi.mock('@/modules/shared/api/vitals', () => ({ vitalsApi: { getLatest: vi.fn(), create: vi.fn() } }))

describe('patientVitalsLatestStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchLatest takes the first item from a Hydra "member" response', async () => {
    vitalsApi.getLatest.mockResolvedValue({ data: { member: [{ hb: 130 }] } })

    const store = usePatientVitalsLatestStore()
    await store.fetchLatest(1)

    expect(store.latest).toEqual({ hb: 130 })
  })

  it('fetchLatest sets null when there is no record yet', async () => {
    vitalsApi.getLatest.mockResolvedValue({ data: { member: [] } })

    const store = usePatientVitalsLatestStore()
    await store.fetchLatest(1)

    expect(store.latest).toBeNull()
  })

  it('fetchLatest is a no-op without a patient id', async () => {
    const store = usePatientVitalsLatestStore()
    await store.fetchLatest(null)

    expect(vitalsApi.getLatest).not.toHaveBeenCalled()
  })

  it('fetchLatest records an error message on failure', async () => {
    vitalsApi.getLatest.mockRejectedValue(new Error('boom'))

    const store = usePatientVitalsLatestStore()
    await store.fetchLatest(1)

    expect(store.error).toBe('Не удалось загрузить данные.')
    expect(store.latest).toBeNull()
  })

  it('saveMeasurement delegates to vitalsApi.create and returns the saved data', async () => {
    vitalsApi.create.mockResolvedValue({ data: { id: 5, hb: 130 } })

    const store = usePatientVitalsLatestStore()
    const result = await store.saveMeasurement({ patient: '/api/patients/1', hb: 130 })

    expect(vitalsApi.create).toHaveBeenCalledWith({ patient: '/api/patients/1', hb: 130 })
    expect(result).toEqual({ id: 5, hb: 130 })
  })
})
