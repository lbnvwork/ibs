import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useHospitalStore } from './hospitalStore'
import { hospitalApi } from '@/modules/shared/api/hospitals'

vi.mock('@/modules/shared/api/hospitals', () => ({
  hospitalApi: { getAll: vi.fn() }
}))

describe('hospitalStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('loads hospitals, extracts ids from IRIs, and sorts by name', async () => {
    hospitalApi.getAll.mockResolvedValue([
      { '@id': '/api/hospitals/2', name: 'Яузская больница' },
      { '@id': '/api/hospitals/1', name: 'Александровская больница' }
    ])

    const store = useHospitalStore()
    await store.loadHospitals()

    expect(store.hospitals).toEqual([
      { id: 1, name: 'Александровская больница' },
      { id: 2, name: 'Яузская больница' }
    ])
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('falls back to the raw id when there is no IRI', async () => {
    hospitalApi.getAll.mockResolvedValue([{ id: 5, name: 'ЦРБ' }])

    const store = useHospitalStore()
    await store.loadHospitals()

    expect(store.hospitals).toEqual([{ id: 5, name: 'ЦРБ' }])
  })

  it('records the error message on failure', async () => {
    hospitalApi.getAll.mockRejectedValue(new Error('network down'))

    const store = useHospitalStore()
    await store.loadHospitals()

    expect(store.error).toBe('network down')
    expect(store.hospitals).toEqual([])
  })

  it('hospitalOptions prepends the "all hospitals" option', () => {
    const store = useHospitalStore()
    store.hospitals = [{ id: 1, name: 'ЦРБ' }]

    expect(store.hospitalOptions).toEqual([
      { value: '', label: 'Все лечебные учреждения' },
      { value: 1, label: 'ЦРБ' }
    ])
  })

  it('setSelectedHospital and clearSelectedHospital toggle the selection', () => {
    const store = useHospitalStore()
    store.setSelectedHospital(3)
    expect(store.selectedHospitalId).toBe(3)

    store.clearSelectedHospital()
    expect(store.selectedHospitalId).toBeNull()
  })
})
