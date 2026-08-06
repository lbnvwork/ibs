import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTreatmentStore } from './treatmentStore'
import { treatmentApi } from '@/modules/shared/api/treatments'
import { drugApi } from '@/modules/shared/api/drug'

vi.mock('@/modules/shared/api/treatments', () => ({ treatmentApi: { getAll: vi.fn(), update: vi.fn() } }))
vi.mock('@/modules/shared/api/drug', () => ({ drugApi: { getAll: vi.fn() } }))

const rawTreatment = {
  '@id': '/api/treatments/10',
  diagnosis: 'Тромбоз',
  diagnosisCode: 'I80',
  comorbidities: '',
  mnoFrom: 2,
  mnoTo: 3,
  drug: '/api/drugs/1',
  begDt: '2026-01-01T00:00:00',
  planEndDt: null,
  comment: '',
  realEndDt: null
}

describe('treatmentStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchTreatment', () => {
    it('stores the most recent treatment for the patient', async () => {
      treatmentApi.getAll.mockResolvedValue({ member: [rawTreatment] })

      const store = useTreatmentStore()
      await store.fetchTreatment(1)

      expect(treatmentApi.getAll).toHaveBeenCalledWith({
        patient: '/api/patients/1',
        itemsPerPage: 1,
        order: { begDt: 'desc' }
      })
      expect(store.treatment).toEqual(rawTreatment)
      expect(store.isActive).toBe(true)
      expect(store.treatmentIri).toBe('/api/treatments/10')
    })

    it('sets treatment to null when the patient has none', async () => {
      treatmentApi.getAll.mockResolvedValue({ member: [] })

      const store = useTreatmentStore()
      await store.fetchTreatment(1)

      expect(store.treatment).toBeNull()
      expect(store.isActive).toBe(false)
    })

    it('sets an error message on failure', async () => {
      treatmentApi.getAll.mockRejectedValue(new Error('boom'))

      const store = useTreatmentStore()
      await store.fetchTreatment(1)

      expect(store.error).toBe('Не удалось загрузить данные лечения.')
    })
  })

  describe('isActive getter', () => {
    it('is false once realEndDt is set', async () => {
      treatmentApi.getAll.mockResolvedValue({ member: [{ ...rawTreatment, realEndDt: '2026-02-01T00:00:00' }] })
      const store = useTreatmentStore()
      await store.fetchTreatment(1)

      expect(store.isActive).toBe(false)
    })
  })

  describe('loadDrugsIfNeeded', () => {
    it('fetches the drug list only once', async () => {
      drugApi.getAll.mockResolvedValue({ member: [{ id: 1, nominative: 'Варфарин' }] })

      const store = useTreatmentStore()
      await store.loadDrugsIfNeeded()
      await store.loadDrugsIfNeeded()

      expect(drugApi.getAll).toHaveBeenCalledTimes(1)
      expect(store.allDrugs).toHaveLength(1)
    })
  })

  describe('validateTreatmentForm', () => {
    it('flags missing required fields', () => {
      const store = useTreatmentStore()
      store.editingTreatmentData = { diagnosis: '', drugId: null, begDt: null, mnoFrom: null, mnoTo: null }

      expect(store.validateTreatmentForm()).toBe(true)
      expect(store.treatmentFormError).toContain('Диагноз обязателен')
    })

    it('flags an invalid MNO range (from >= to)', () => {
      const store = useTreatmentStore()
      store.editingTreatmentData = {
        diagnosis: 'Тромбоз', drugId: 1, begDt: '2026-01-01', mnoFrom: 3, mnoTo: 2
      }

      expect(store.validateTreatmentForm()).toBe(true)
      expect(store.treatmentFormError).toContain('Нижняя граница должна быть меньше верхней')
    })

    it('flags a plan end date earlier than the start date', () => {
      const store = useTreatmentStore()
      store.editingTreatmentData = {
        diagnosis: 'Тромбоз', drugId: 1, begDt: '2026-02-01', planEndDt: '2026-01-01', mnoFrom: 2, mnoTo: 3
      }

      expect(store.validateTreatmentForm()).toBe(true)
      expect(store.treatmentFormError).toContain('Плановая дата не может быть раньше даты госпитализации')
    })

    it('passes for consistent data', () => {
      const store = useTreatmentStore()
      store.editingTreatmentData = {
        diagnosis: 'Тромбоз', drugId: 1, begDt: '2026-01-01', planEndDt: '2026-02-01', mnoFrom: 2, mnoTo: 3
      }

      expect(store.validateTreatmentForm()).toBe(false)
    })
  })

  describe('saveTreatment', () => {
    it('skips the API call and exits edit mode when nothing changed', async () => {
      treatmentApi.getAll.mockResolvedValue({ member: [rawTreatment] })
      const store = useTreatmentStore()
      await store.fetchTreatment(1)
      store.startEditingTreatment()

      const result = await store.saveTreatment()

      expect(result).toBe(true)
      expect(treatmentApi.update).not.toHaveBeenCalled()
      expect(store.editingTreatment).toBe(false)
    })

    it('saves changed data and updates the local treatment', async () => {
      treatmentApi.getAll.mockResolvedValue({ member: [rawTreatment] })
      treatmentApi.update.mockResolvedValue({})
      const store = useTreatmentStore()
      await store.fetchTreatment(1)
      store.startEditingTreatment()
      store.editingTreatmentData.diagnosis = 'Новый диагноз'

      const result = await store.saveTreatment()

      expect(result).toBe(true)
      expect(treatmentApi.update).toHaveBeenCalledWith(10, expect.objectContaining({ diagnosis: 'Новый диагноз' }))
      expect(store.treatment.diagnosis).toBe('Новый диагноз')
    })

    it('parses violation messages from a 422 response', async () => {
      treatmentApi.getAll.mockResolvedValue({ member: [rawTreatment] })
      treatmentApi.update.mockRejectedValue({
        response: { status: 422, data: { violations: [{ propertyPath: 'mnoFrom', message: 'mnoFrom: must be positive' }] } }
      })
      const store = useTreatmentStore()
      await store.fetchTreatment(1)
      store.startEditingTreatment()
      store.editingTreatmentData.diagnosis = 'Изменение'

      const result = await store.saveTreatment()

      expect(result).toBe(false)
      expect(store.treatmentFormError).toContain('mnoFrom: must be positive')
    })
  })
})
