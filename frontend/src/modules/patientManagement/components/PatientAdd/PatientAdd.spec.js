import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import PatientAdd from './PatientAdd.vue'
import { hospitalApi } from '@/modules/shared/api/hospitals'
import { patientApi } from '@/modules/shared/api/patients'

vi.mock('@/modules/shared/api/hospitals', () => ({ hospitalApi: { getAll: vi.fn() } }))
vi.mock('@/modules/shared/api/patients', () => ({ patientApi: { create: vi.fn() } }))

function validPatient() {
  return {
    lastname: 'Иванов',
    firstname: 'Пётр',
    secondName: '',
    birthday: '1980-01-01',
    sex: 0,
    smsPhone: '8(900)123-45-67',
    address: 'ул. Ленина, 1',
    passport: '1234 567890',
    snils: '123-456-789 95',
    healthInsurance: '',
    email: '',
    comment: '',
    hospitalId: 1,
  }
}

function mountPatientAdd(push = vi.fn()) {
  hospitalApi.getAll.mockResolvedValue([{ id: 1, name: 'ЦРБ' }])
  return mount(PatientAdd, { global: { mocks: { $router: { push } } } })
}

describe('PatientAdd.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads the hospital list on mount', async () => {
    const wrapper = mountPatientAdd()
    await flushPromises()

    expect(wrapper.vm.hospitals).toEqual([{ id: 1, name: 'ЦРБ' }])
    expect(wrapper.vm.loadingHospitals).toBe(false)
  })

  describe('field formatters', () => {
    it('formats the phone, passport and snils as the user types', async () => {
      const wrapper = mountPatientAdd()
      await flushPromises()

      wrapper.vm.patient.smsPhone = '89001234567'
      wrapper.vm.formatPhoneField()
      expect(wrapper.vm.patient.smsPhone).toBe('8(900)123-45-67')

      wrapper.vm.patient.passport = '1234567890'
      wrapper.vm.formatPassportField()
      expect(wrapper.vm.patient.passport).toBe('1234 567890')

      wrapper.vm.patient.snils = '12345678995'
      wrapper.vm.formatSnilsField()
      expect(wrapper.vm.patient.snils).toBe('123-456-789 95')
    })
  })

  describe('validateFormAndSetErrors', () => {
    it('flags all missing required fields', async () => {
      const wrapper = mountPatientAdd()
      await flushPromises()

      expect(wrapper.vm.validateFormAndSetErrors()).toBe(true)
      expect(wrapper.vm.fieldErrors.lastname).toBe('Фамилия обязательна')
      expect(wrapper.vm.fieldErrors.hospitalId).toBe('Больница обязательна')
    })

    it('rejects a birthday in the future', async () => {
      const wrapper = mountPatientAdd()
      await flushPromises()
      Object.assign(wrapper.vm.patient, validPatient(), { birthday: '2999-01-01' })

      expect(wrapper.vm.validateFormAndSetErrors()).toBe(true)
      expect(wrapper.vm.fieldErrors.birthday).toContain('не может быть в будущем')
    })

    it('rejects an invalid email but allows an empty one', async () => {
      const wrapper = mountPatientAdd()
      await flushPromises()
      Object.assign(wrapper.vm.patient, validPatient(), { email: 'not-an-email' })

      expect(wrapper.vm.validateFormAndSetErrors()).toBe(true)
      expect(wrapper.vm.fieldErrors.email).toBe('Неверный формат email')
    })

    it('passes for a fully valid patient', async () => {
      const wrapper = mountPatientAdd()
      await flushPromises()
      Object.assign(wrapper.vm.patient, validPatient())

      expect(wrapper.vm.validateFormAndSetErrors()).toBe(false)
    })
  })

  describe('submitForm', () => {
    it('does not call the API when validation fails', async () => {
      const wrapper = mountPatientAdd()
      await flushPromises()

      await wrapper.vm.submitForm()

      expect(patientApi.create).not.toHaveBeenCalled()
    })

    it('creates the patient and redirects to the treatment-add page', async () => {
      patientApi.create.mockResolvedValue({ id: 42 })
      const push = vi.fn()
      const wrapper = mountPatientAdd(push)
      await flushPromises()
      Object.assign(wrapper.vm.patient, validPatient())

      await wrapper.vm.submitForm()

      expect(patientApi.create).toHaveBeenCalledWith(expect.objectContaining({
        lastname: 'Иванов',
        hospital: '/api/hospitals/1',
      }))
      expect(push).toHaveBeenCalledWith('/patient/42/treatment/add')
    })

    it('maps 422 violations onto field errors', async () => {
      patientApi.create.mockRejectedValue({
        response: { status: 422, data: { violations: [{ propertyPath: 'snils', message: 'snils: invalid' }] } }
      })
      const wrapper = mountPatientAdd()
      await flushPromises()
      Object.assign(wrapper.vm.patient, validPatient())

      await wrapper.vm.submitForm()

      expect(wrapper.vm.fieldErrors.snils).toBe('invalid')
    })
  })
})
