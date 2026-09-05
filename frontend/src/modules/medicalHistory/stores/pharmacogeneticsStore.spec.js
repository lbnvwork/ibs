import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePharmacogeneticsStore } from './pharmacogeneticsStore'
import { pharmacogeneticsApi } from '@/modules/shared/api/pharmacogenetics'

vi.mock('@/modules/shared/api/pharmacogenetics', () => ({
  pharmacogeneticsApi: {
    getForPatient: vi.fn(),
    createResult: vi.fn(),
    updateResult: vi.fn(),
    deleteResult: vi.fn()
  }
}))

const markerFixture = {
  markerId: 1,
  geneSymbol: 'CYP2C9_2',
  currentValueId: 10,
  currentValue: 'CC',
  resultId: 100,
  possibleValues: [{ id: 10, label: 'CC (норма)', description: 'Нормальная активность' }]
}

describe('pharmacogeneticsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchPharmacogenetics loads markers and seeds editingValueId from currentValueId', async () => {
    pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [markerFixture] } })

    const store = usePharmacogeneticsStore()
    await store.fetchPharmacogenetics(1, '/api/drugs/1')

    expect(pharmacogeneticsApi.getForPatient).toHaveBeenCalledWith(1, { params: { drug: '/api/drugs/1' } })
    expect(store.markers[0].editingValueId).toBe(10)
  })

  it('resets to an empty list on failure', async () => {
    pharmacogeneticsApi.getForPatient.mockRejectedValue(new Error('boom'))

    const store = usePharmacogeneticsStore()
    await store.fetchPharmacogenetics(1)

    expect(store.markers).toEqual([])
  })

  it('cancelEditing restores values from the snapshot taken at startEditing', async () => {
    pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [markerFixture] } })
    const store = usePharmacogeneticsStore()
    await store.fetchPharmacogenetics(1)

    store.startEditing()
    store.markers[0].editingValueId = 20

    store.cancelEditing()

    expect(store.editing).toBe(false)
    expect(store.markers[0].editingValueId).toBe(10)
  })

  it('resets editing state when fetching another patient', async () => {
    pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [markerFixture] } })
    const store = usePharmacogeneticsStore()
    await store.fetchPharmacogenetics(1)
    store.startEditing()

    await store.fetchPharmacogenetics(2)

    expect(store.editing).toBe(false)
  })

  describe('save', () => {
    it('creates a result for a marker that had none and now has a value', async () => {
      pharmacogeneticsApi.getForPatient.mockResolvedValue({
        data: { markers: [{ ...markerFixture, resultId: null, currentValueId: null }] }
      })
      pharmacogeneticsApi.createResult.mockResolvedValue({})
      const store = usePharmacogeneticsStore()
      await store.fetchPharmacogenetics(5)
      store.startEditing()
      store.markers[0].editingValueId = 10

      await store.save()

      expect(pharmacogeneticsApi.createResult).toHaveBeenCalledWith({
        patient: '/api/patients/5',
        marker: '/api/genetic_markers/1',
        markerValue: '/api/genetic_marker_values/10'
      })
    })

    it('updates a result whose genotype value changed', async () => {
      pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [markerFixture] } })
      pharmacogeneticsApi.updateResult.mockResolvedValue({})
      const store = usePharmacogeneticsStore()
      await store.fetchPharmacogenetics(5)
      store.startEditing()
      store.markers[0].editingValueId = 20

      await store.save()

      expect(pharmacogeneticsApi.updateResult).toHaveBeenCalledWith(100, expect.objectContaining({ markerValue: '/api/genetic_marker_values/20' }))
    })

    it('deletes a result when its genotype was cleared', async () => {
      pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [markerFixture] } })
      pharmacogeneticsApi.deleteResult.mockResolvedValue({})
      const store = usePharmacogeneticsStore()
      await store.fetchPharmacogenetics(5)
      store.startEditing()
      store.markers[0].editingValueId = null

      await store.save()

      expect(pharmacogeneticsApi.deleteResult).toHaveBeenCalledWith(100)
    })

    it('skips markers with no result and no selected value', async () => {
      pharmacogeneticsApi.getForPatient.mockResolvedValue({
        data: { markers: [{ ...markerFixture, resultId: null, currentValueId: null }] }
      })
      const store = usePharmacogeneticsStore()
      await store.fetchPharmacogenetics(5)
      store.startEditing()
      // editingValueId stays null/undefined

      await store.save()

      expect(pharmacogeneticsApi.createResult).not.toHaveBeenCalled()
      expect(store.editing).toBe(false)
    })

    it('records a save error and reloads data when a request fails', async () => {
      pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [markerFixture] } })
      pharmacogeneticsApi.updateResult.mockRejectedValue(new Error('boom'))
      const store = usePharmacogeneticsStore()
      await store.fetchPharmacogenetics(5)
      store.startEditing()
      store.markers[0].editingValueId = 20

      await store.save()

      expect(store.saveError).toBe('Не удалось сохранить фармакогенетические данные.')
    })
  })

  describe('getGenotypeLabel', () => {
    it('returns null when no genotype is selected', () => {
      const store = usePharmacogeneticsStore()
      expect(store.getGenotypeLabel({ currentValueId: null, possibleValues: [] })).toBeNull()
    })

    it('formats "label: description" for the selected genotype', () => {
      const store = usePharmacogeneticsStore()
      expect(store.getGenotypeLabel(markerFixture)).toBe('CC (норма): Нормальная активность')
    })
  })
})
