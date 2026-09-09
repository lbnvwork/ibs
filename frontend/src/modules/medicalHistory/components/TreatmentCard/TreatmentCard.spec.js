import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import TreatmentCard from './TreatmentCard.vue'
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore'
import { mkb10Api } from '@/modules/shared/api/mkb10'

vi.mock('@/modules/shared/api/mkb10', () => ({ mkb10Api: { getByCode: vi.fn(), search: vi.fn(), getPopular: vi.fn().mockResolvedValue([]) } }))

function mountTreatmentCard(treatment) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const store = useTreatmentStore()
  store.treatment = treatment
  store.allDrugs = []
  const wrapper = mount(TreatmentCard, {
    global: { plugins: [pinia], mocks: { $route: { params: { patientId: '7' } } } }
  })
  return { wrapper, store }
}

const treatment = {
  diagnosis: 'Тромбоз глубоких вен',
  diagnosisCode: 'I80',
  comorbidities: '',
  mnoFrom: 2,
  mnoTo: 3,
  drug: '/api/drugs/1',
  begDt: '2024-01-01',
  planEndDt: '2024-06-01',
  comment: '',
}

describe('TreatmentCard.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('displays the treatment data', () => {
    const { wrapper } = mountTreatmentCard(treatment)
    expect(wrapper.text()).toContain('Тромбоз глубоких вен')
    expect(wrapper.text()).toContain('I80')
  })

  it('shows the edit button only for active treatments', () => {
    const { wrapper: activeWrapper } = mountTreatmentCard(treatment)
    expect(activeWrapper.find('.btn-edit-treatment').exists()).toBe(true)

    const { wrapper: inactiveWrapper } = mountTreatmentCard({ ...treatment, realEndDt: '2024-05-01' })
    return inactiveWrapper.vm.$nextTick().then(() => {
      expect(inactiveWrapper.find('.btn-edit-treatment').exists()).toBe(false)
    })
  })

  it('entering edit mode emits edit-start and calls the store', async () => {
    const { wrapper, store } = mountTreatmentCard(treatment)
    store.startEditingTreatment = vi.fn()

    await wrapper.find('.btn-edit-treatment').trigger('click')

    expect(store.startEditingTreatment).toHaveBeenCalled()
    expect(wrapper.emitted('edit-start')).toBeTruthy()
  })

  it('cancelling emits edit-end and calls the store', async () => {
    const { wrapper, store } = mountTreatmentCard(treatment)
    store.editingTreatment = true
    store.editingTreatmentData = { diagnosis: 'x', comorbiditiesRaw: '' }
    store.cancelEditingTreatment = vi.fn()
    await wrapper.vm.$nextTick()

    await wrapper.find('.btn-cancel-treatment').trigger('click')

    expect(store.cancelEditingTreatment).toHaveBeenCalled()
    expect(wrapper.emitted('edit-end')).toBeTruthy()
  })

  it('saving successfully emits edit-end using the route patientId', async () => {
    const { wrapper, store } = mountTreatmentCard(treatment)
    store.editingTreatment = true
    store.editingTreatmentData = { diagnosis: 'x', comorbiditiesRaw: '' }
    store.saveTreatment = vi.fn().mockResolvedValue(true)
    await wrapper.vm.$nextTick()

    await wrapper.find('.btn-save-treatment').trigger('click')
    await wrapper.vm.$nextTick()

    expect(store.saveTreatment).toHaveBeenCalledWith('7')
    expect(wrapper.emitted('edit-end')).toBeTruthy()
  })

  it('renders MultiDiagnosisSelect instead of a plain input when editing', async () => {
    const { wrapper, store } = mountTreatmentCard(treatment)
    store.editingTreatment = true
    store.editingTreatmentData = { diagnosis: 'x', diagnosisCode: 'I80', comorbiditiesRaw: '' }
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent({ name: 'MultiDiagnosisSelect' }).exists()).toBe(true)
  })

  it('selecting a code fills diagnosis and diagnosisCode', async () => {
    mkb10Api.getByCode.mockResolvedValue({ member: [{ mkbCode: 'I82.4', mkbName: 'Острый тромбоз глубоких вен нижних конечностей' }] })
    const { wrapper, store } = mountTreatmentCard(treatment)
    store.editingTreatment = true
    store.editingTreatmentData = { diagnosis: '', diagnosisCode: '', comorbiditiesRaw: '' }
    await wrapper.vm.$nextTick()

    wrapper.vm.selectedDiagnosisCode = 'I82.4'
    await flushPromises()

    expect(store.editingTreatmentData.diagnosisCode).toBe('I82.4')
    expect(store.editingTreatmentData.diagnosis).toBe('Острый тромбоз глубоких вен нижних конечностей')
  })

  it('shows the real end date row only when the treatment is finished', async () => {
    const { wrapper } = mountTreatmentCard({ ...treatment, realEndDt: '2024-05-01' })
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('2024-05-01'.split('-').reverse().join('.'))
  })
})
