import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PatientCard from './PatientCard.vue'
import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore'

function mountPatientCard(patient) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const store = usePatientCardStore()
  store.patient = patient
  const wrapper = mount(PatientCard, {
    global: { plugins: [pinia], mocks: { $route: { params: { patientId: '7' } } } }
  })
  return { wrapper, store }
}

const patient = {
  name: 'Иванов Пётр',
  age: '45 лет',
  birthday: '1980-01-01',
  address: 'ул. Ленина, 1',
  phone: '8(900)123-45-67',
  email: 'ivanov@mail.ru',
  passport: '1234 567890',
  insurance: 'ОМС',
  snils: '123-456-789 95',
  hospital: 'ЦРБ',
  comment: '',
}

describe('PatientCard.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders nothing meaningful before the patient loads', () => {
    const { wrapper } = mountPatientCard(null)
    expect(wrapper.find('.patient-info-compact').exists()).toBe(false)
  })

  it('displays the patient data', () => {
    const { wrapper } = mountPatientCard(patient)
    expect(wrapper.text()).toContain('Иванов Пётр')
    expect(wrapper.text()).toContain('ул. Ленина, 1')
    expect(wrapper.text()).toContain('ЦРБ')
  })

  it('entering edit mode emits edit-start and calls the store', async () => {
    const { wrapper, store } = mountPatientCard(patient)
    store.startEditingPatient = vi.fn()

    await wrapper.find('.btn-edit-treatment').trigger('click')

    expect(store.startEditingPatient).toHaveBeenCalled()
    expect(wrapper.emitted('edit-start')).toBeTruthy()
  })

  it('cancelling emits edit-end and calls the store', async () => {
    const { wrapper, store } = mountPatientCard(patient)
    store.editingPatient = true
    store.editingPatientData = { address: '' }
    store.cancelEditingPatient = vi.fn()
    await wrapper.vm.$nextTick()

    await wrapper.find('.btn-cancel-treatment').trigger('click')

    expect(store.cancelEditingPatient).toHaveBeenCalled()
    expect(wrapper.emitted('edit-end')).toBeTruthy()
  })

  it('saving successfully emits edit-end using the route patientId', async () => {
    const { wrapper, store } = mountPatientCard(patient)
    store.editingPatient = true
    store.editingPatientData = { address: 'ул. Ленина, 1' }
    store.savePatient = vi.fn().mockResolvedValue(true)
    await wrapper.vm.$nextTick()

    await wrapper.find('.btn-save-treatment').trigger('click')
    await wrapper.vm.$nextTick()

    expect(store.savePatient).toHaveBeenCalledWith('7')
    expect(wrapper.emitted('edit-end')).toBeTruthy()
  })

  it('does not emit edit-end when saving fails', async () => {
    const { wrapper, store } = mountPatientCard(patient)
    store.editingPatient = true
    store.editingPatientData = { address: '' }
    store.savePatient = vi.fn().mockResolvedValue(false)
    await wrapper.vm.$nextTick()

    await wrapper.find('.btn-save-treatment').trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('edit-end')).toBeFalsy()
  })

  it('shows the form error message when present', async () => {
    const { wrapper, store } = mountPatientCard(patient)
    store.patientFormError = 'Телефон обязателен'
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.treatment-form-error').text()).toBe('Телефон обязателен')
  })
})
