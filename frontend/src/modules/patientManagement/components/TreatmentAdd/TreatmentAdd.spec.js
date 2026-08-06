import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TreatmentAdd from './TreatmentAdd.vue'
import { drugApi } from '@/modules/shared/api/drug'
import { treatmentApi } from '@/modules/shared/api/treatments'
import { mkb10Api } from '@/modules/shared/api/mkb10'

vi.mock('@/modules/shared/api/drug', () => ({ drugApi: { getAll: vi.fn() } }))
vi.mock('@/modules/shared/api/treatments', () => ({ treatmentApi: { create: vi.fn() } }))
vi.mock('@/modules/shared/api/mkb10', () => ({ mkb10Api: { getByCode: vi.fn(), getPopular: vi.fn(), search: vi.fn() } }))

function mountTreatmentAdd(push = vi.fn()) {
  drugApi.getAll.mockResolvedValue({ member: [{ id: 1, nominative: 'Варфарин' }] })
  return mount(TreatmentAdd, {
    props: { patientId: '5' },
    global: {
      mocks: { $router: { push } },
      stubs: { MultiDiagnosisSelect: true }
    }
  })
}

describe('TreatmentAdd.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads the drug list on mount', async () => {
    const wrapper = mountTreatmentAdd()
    await flushPromises()

    expect(drugApi.getAll).toHaveBeenCalledWith({ order: { nominative: 'asc' } })
    expect(wrapper.vm.drugs).toEqual([{ id: 1, nominative: 'Варфарин' }])
  })

  // Regression test: validateForm was used without being imported, so every
  // submit attempt threw "ReferenceError: validateForm is not defined".
  it('does not throw and reports field errors when required fields are missing', async () => {
    const wrapper = mountTreatmentAdd()
    await flushPromises()

    expect(() => wrapper.vm.validateFormAndSetErrors()).not.toThrow()
    expect(wrapper.vm.validateFormAndSetErrors()).toBe(true)
    expect(wrapper.vm.fieldErrors.drugId).toBe('Выберите препарат')
    expect(wrapper.vm.fieldErrors.diagnosis).toBe('Введите диагноз')
    expect(wrapper.vm.fieldErrors.mnoFrom).toBe('Целевой диапазон МНО (от) обязателен')
  })

  it('flags an inverted MNO range', async () => {
    const wrapper = mountTreatmentAdd()
    await flushPromises()
    wrapper.vm.treatment.drugId = 1
    wrapper.vm.treatment.diagnosis = 'Тромбоз'
    wrapper.vm.treatment.mnoFrom = 3
    wrapper.vm.treatment.mnoTo = 2

    expect(wrapper.vm.validateFormAndSetErrors()).toBe(true)
    expect(wrapper.vm.fieldErrors.mnoFrom).toBe('МНО «от» не может быть больше МНО «до»')
  })

  it('submits valid data and redirects to the patient page', async () => {
    const push = vi.fn()
    treatmentApi.create.mockResolvedValue({ id: 99 })
    const wrapper = mountTreatmentAdd(push)
    await flushPromises()

    wrapper.vm.treatment.drugId = 1
    wrapper.vm.treatment.diagnosis = 'Тромбоз'
    wrapper.vm.treatment.mnoFrom = 2
    wrapper.vm.treatment.mnoTo = 3
    await wrapper.vm.submitForm()

    expect(treatmentApi.create).toHaveBeenCalledWith(expect.objectContaining({
      patient: '/api/patients/5',
      drug: '/api/drugs/1',
      diagnosis: 'Тромбоз',
    }))
    expect(push).toHaveBeenCalledWith('/patient/5')
  })

  it('does not call the API when validation fails', async () => {
    const wrapper = mountTreatmentAdd()
    await flushPromises()

    await wrapper.vm.submitForm()

    expect(treatmentApi.create).not.toHaveBeenCalled()
  })

  it('maps 422 violations onto field errors', async () => {
    treatmentApi.create.mockRejectedValue({
      response: { status: 422, data: { violations: [{ propertyPath: 'mnoFrom', message: 'mnoFrom: must be positive' }] } }
    })
    const wrapper = mountTreatmentAdd()
    await flushPromises()
    wrapper.vm.treatment.drugId = 1
    wrapper.vm.treatment.diagnosis = 'Тромбоз'
    wrapper.vm.treatment.mnoFrom = 2
    wrapper.vm.treatment.mnoTo = 3

    await wrapper.vm.submitForm()

    expect(wrapper.vm.fieldErrors.mnoFrom).toBe('must be positive')
  })

  it('resolves the diagnosis text/code when a diagnosis is selected via MKB-10 code', async () => {
    mkb10Api.getByCode.mockResolvedValue({ member: [{ mkbCode: 'I80', mkbName: 'Флебит и тромбофлебит' }] })
    const wrapper = mountTreatmentAdd()
    await flushPromises()

    wrapper.vm.selectedDiagnosisCodes = ['I80']
    await flushPromises()

    expect(wrapper.vm.treatment.diagnosis).toBe('Флебит и тромбофлебит')
    expect(wrapper.vm.treatment.diagnosisCode).toBe('I80')
  })

  it('clears the diagnosis when the selection is cleared', async () => {
    const wrapper = mountTreatmentAdd()
    await flushPromises()
    wrapper.vm.treatment.diagnosis = 'Флебит'
    wrapper.vm.treatment.diagnosisCode = 'I80'

    wrapper.vm.selectedDiagnosisCodes = []
    await flushPromises()

    expect(wrapper.vm.treatment.diagnosis).toBe('')
    expect(wrapper.vm.treatment.diagnosisCode).toBe('')
  })
})
