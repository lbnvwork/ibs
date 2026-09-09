import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePatientCardStore } from './patientCardStore'
import { patientApi } from '@/modules/shared/api/patients'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/patients', () => ({ patientApi: { getOne: vi.fn(), update: vi.fn() } }))
vi.mock('@/modules/shared/api/client', () => ({ default: { get: vi.fn() } }))

const rawPatient = {
  id: 1,
  lastname: 'Иванов',
  firstname: 'Пётр',
  secondName: 'Сергеевич',
  address: 'ул. Ленина, 1',
  smsPhone: '89001234567',
  email: 'ivanov@mail.ru',
  passport: '1234567890',
  healthInsurance: 'ОМС 12345',
  snils: '12345678995',
  comment: 'комментарий',
  hospital: '/api/hospitals/1',
  birthday: '1980-01-01'
}

describe('patientCardStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchPatient', () => {
    it('loads the patient and resolves the hospital name', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      apiClient.get.mockResolvedValue({ data: { name: 'ЦРБ' } })

      const store = usePatientCardStore()
      await store.fetchPatient(1)

      expect(apiClient.get).toHaveBeenCalledWith('/hospitals/1')
      expect(store.patient.name).toBe('Иванов Пётр Сергеевич')
      expect(store.patient.hospital).toBe('ЦРБ')
      expect(store.isPatientLoaded).toBe(true)
    })

    it('falls back to a dash for the hospital when the patient has none', async () => {
      patientApi.getOne.mockResolvedValue({ ...rawPatient, hospital: null })

      const store = usePatientCardStore()
      await store.fetchPatient(1)

      expect(apiClient.get).not.toHaveBeenCalled()
      expect(store.patient.hospital).toBe('—')
    })

    it('sets an error message on failure', async () => {
      patientApi.getOne.mockRejectedValue(new Error('not found'))

      const store = usePatientCardStore()
      await store.fetchPatient(999)

      expect(store.error).toBe('Не удалось загрузить данные пациента.')
      expect(store.isPatientLoaded).toBe(false)
    })
  })

  describe('editing flow', () => {
    it('startEditingPatient seeds editable, formatted copies of the fields', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      const store = usePatientCardStore()
      await store.fetchPatient(1)

      store.startEditingPatient()

      expect(store.editingPatient).toBe(true)
      expect(store.editingPatientData.phone).toBe('8(900)123-45-67')
      expect(store.editingPatientData.snils).toBe('123-456-789 95')
    })

    it('cancelEditingPatient restores the original snapshot', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      const store = usePatientCardStore()
      await store.fetchPatient(1)
      store.startEditingPatient()

      store.editingPatientData.address = 'изменённый адрес'
      store.cancelEditingPatient()

      expect(store.editingPatient).toBe(false)
      expect(store.editingPatientData.address).toBe('ул. Ленина, 1')
    })

    it('resets editing state when fetching another patient', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      const store = usePatientCardStore()
      await store.fetchPatient(1)
      store.startEditingPatient()

      await store.fetchPatient(2)

      expect(store.editingPatient).toBe(false)
      expect(store.editingPatientData).toEqual({})
    })
  })

  describe('validatePatientForm', () => {
    it('returns true and sets an error message when required fields are missing', () => {
      const store = usePatientCardStore()
      store.editingPatientData = { address: '', phone: '', passport: '', snils: '', email: '' }

      expect(store.validatePatientForm()).toBe(true)
      expect(store.patientFormError.length).toBeGreaterThan(0)
    })

    it('returns false when all fields are valid', () => {
      const store = usePatientCardStore()
      store.editingPatientData = {
        address: 'ул. Ленина, 1',
        phone: '8(900)123-45-67',
        passport: '1234 567890',
        snils: '123-456-789 95',
        email: ''
      }

      expect(store.validatePatientForm()).toBe(false)
      expect(store.patientFormError).toBe('')
    })
  })

  describe('savePatient', () => {
    it('does not call the API and returns false when validation fails', async () => {
      const store = usePatientCardStore()
      store.editingPatientData = { address: '', phone: '', passport: '', snils: '', email: '' }

      const result = await store.savePatient(1)

      expect(result).toBe(false)
      expect(patientApi.update).not.toHaveBeenCalled()
    })

    it('saves valid data and updates the local patient snapshot', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      patientApi.update.mockResolvedValue({})
      const store = usePatientCardStore()
      await store.fetchPatient(1)
      store.startEditingPatient()
      store.editingPatientData.address = 'Новый адрес'

      const result = await store.savePatient(1)

      expect(result).toBe(true)
      expect(patientApi.update).toHaveBeenCalledWith(1, expect.objectContaining({ address: 'Новый адрес' }))
      expect(store.patient.address).toBe('Новый адрес')
      expect(store.editingPatient).toBe(false)
    })

    it('parses violation messages from a 422 response', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      patientApi.update.mockRejectedValue({
        response: { status: 422, data: { violations: [{ propertyPath: 'email', message: 'email: Invalid format' }] } }
      })
      const store = usePatientCardStore()
      await store.fetchPatient(1)
      store.startEditingPatient()

      const result = await store.savePatient(1)

      expect(result).toBe(false)
      expect(store.patientFormError).toContain('email: Invalid format')
    })

    it('shows a generic connection error for non-422 failures', async () => {
      patientApi.getOne.mockResolvedValue(rawPatient)
      patientApi.update.mockRejectedValue({ response: { status: 500 } })
      const store = usePatientCardStore()
      await store.fetchPatient(1)
      store.startEditingPatient()

      await store.savePatient(1)

      expect(store.patientFormError).toBe('Не удалось сохранить данные. Проверьте соединение.')
    })
  })
})
