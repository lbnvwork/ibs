import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMedicalTableStore } from './medicalTableStore'
import apiClient from '@/modules/shared/api/client'
import { testHistoryApi } from '@/modules/shared/api/testHistory'
import { vitalsApi } from '@/modules/shared/api/vitals'

vi.mock('@/modules/shared/api/client', () => ({ default: { get: vi.fn() } }))
vi.mock('@/modules/shared/api/testHistory', () => ({ testHistoryApi: { getAll: vi.fn() } }))
vi.mock('@/modules/shared/api/vitals', () => ({ vitalsApi: { getByTreatment: vi.fn() } }))

function mockResponses({ appointments = [], history = [], vitals = [] }) {
  apiClient.get.mockResolvedValue({ data: { member: appointments } })
  testHistoryApi.getAll.mockResolvedValue({ member: history })
  vitalsApi.getByTreatment.mockResolvedValue({ data: { member: vitals } })
}

describe('medicalTableStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('clears events and skips fetching when there is no treatment IRI', async () => {
    const store = useMedicalTableStore()
    store.events = [{ type: 'test' }]
    await store.fetchMedicalData(null)

    expect(store.events).toEqual([])
    expect(apiClient.get).not.toHaveBeenCalled()
  })

  it('merges a test-history entry with its same-day appointment', async () => {
    mockResponses({
      appointments: [{ appointmentDt: '2026-02-10T09:00:00', doze: 5, comment: 'Снизить дозу' }],
      history: [{ creationDt: '2026-02-10T12:00:00', mno: 2.4, doze: 4.5, comment: 'Пациент в норме' }]
    })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.events).toHaveLength(1)
    expect(store.events[0]).toMatchObject({
      type: 'test',
      mno: 2.4,
      currentDose: 4.5,
      prescribedDose: 5,
      recommendations: 'Снизить дозу'
    })
  })

  it('uses a dash for the prescribed dose when no appointment matches that day', async () => {
    mockResponses({ history: [{ creationDt: '2026-02-10T12:00:00', mno: 2.4, doze: 4.5 }] })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.events[0].prescribedDose).toBe('—')
  })

  it('adds a vitals-only event for a day with measurements but no test/appointment', async () => {
    mockResponses({ vitals: [{ recordDt: '2026-02-11T08:00:00', hb: 130, heartRate: 70, systolicPressure: null, diastolicPressure: null, saturation: null, weight: null }] })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.events).toHaveLength(1)
    expect(store.events[0]).toMatchObject({ type: 'vitals_only', hb: 130, heartRate: 70 })
  })

  it('does not add a vitals-only event when every measurement that day is null', async () => {
    mockResponses({ vitals: [{ recordDt: '2026-02-11T08:00:00', hb: null, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: null, weight: null }] })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.events).toEqual([])
  })

  it('keeps unmatched appointments as their own event', async () => {
    mockResponses({ appointments: [{ appointmentDt: '2026-02-12T09:00:00', doze: 3, comment: '' }] })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.events).toHaveLength(1)
    expect(store.events[0]).toMatchObject({ type: 'appointment', prescribedDose: 3, mno: null })
  })

  it('sorts all events from most recent to oldest', async () => {
    mockResponses({
      history: [
        { creationDt: '2026-01-01T10:00:00', mno: 2.0, doze: 4 },
        { creationDt: '2026-03-01T10:00:00', mno: 2.5, doze: 4.5 }
      ]
    })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.events.map(e => e.date)).toEqual(['2026-03-01T10:00:00', '2026-01-01T10:00:00'])
  })

  it('sets an error message when a request fails', async () => {
    apiClient.get.mockRejectedValue(new Error('network down'))
    testHistoryApi.getAll.mockResolvedValue({ member: [] })
    vitalsApi.getByTreatment.mockResolvedValue({ data: { member: [] } })

    const store = useMedicalTableStore()
    await store.fetchMedicalData('/api/treatments/1')

    expect(store.error).toBe('Не удалось загрузить историю')
  })
})
