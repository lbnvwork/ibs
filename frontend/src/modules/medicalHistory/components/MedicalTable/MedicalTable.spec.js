import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MedicalTable from './MedicalTable.vue'
import { useMedicalTableStore } from '@/modules/medicalHistory/stores/medicalTableStore'

function mountMedicalTable(events = [], props = {}) {
  setActivePinia(createPinia())
  const store = useMedicalTableStore()
  store.events = events
  const wrapper = mount(MedicalTable, {
    props,
    global: { stubs: { MnoChart: true } }
  })
  return { wrapper, store }
}

describe('MedicalTable.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders one row per event from the store', () => {
    const { wrapper } = mountMedicalTable([
      { displayDate: '01.01.2024', currentDose: 2, prescribedDose: 2.25, recommendations: '', comment: '', type: 'test', mno: 2.5 },
      { displayDate: '02.01.2024', currentDose: 2.25, prescribedDose: 2.25, recommendations: '', comment: '', type: 'appointment', mno: null },
    ])
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('marks appointment rows and hides indicators for them', () => {
    const { wrapper } = mountMedicalTable([
      { displayDate: '02.01.2024', currentDose: 2.25, prescribedDose: 2.25, recommendations: '', comment: '', type: 'appointment', mno: null },
    ])
    const row = wrapper.find('tbody tr')
    expect(row.classes()).toContain('appointment-row')
    expect(row.findAll('td')[1].text()).toBe('—')
  })

  it('builds chartData from events that have a non-null mno', () => {
    const { wrapper } = mountMedicalTable([
      { displayDate: '01.01.2024', date: '2024-01-01', mno: 2.5, type: 'test' },
      { displayDate: '02.01.2024', date: '2024-01-02', mno: null, type: 'appointment' },
    ])
    expect(wrapper.vm.chartData).toEqual([{ date: '2024-01-01', inr: 2.5 }])
  })

  it('only renders the MnoChart when there is at least one chartable point', () => {
    const { wrapper: withData } = mountMedicalTable([{ displayDate: '01.01.2024', date: '2024-01-01', mno: 2.5 }])
    expect(withData.findComponent({ name: 'MnoChart' }).exists()).toBe(true)

    const { wrapper: withoutData } = mountMedicalTable([{ displayDate: '01.01.2024', date: '2024-01-01', mno: null }])
    expect(withoutData.findComponent({ name: 'MnoChart' }).exists()).toBe(false)
  })

  it('emits open-test-modal and open-appointment-modal', async () => {
    const { wrapper } = mountMedicalTable([])
    await wrapper.find('.btn-add-test').trigger('click')
    await wrapper.find('.btn-add-appointment').trigger('click')

    expect(wrapper.emitted('open-test-modal')).toBeTruthy()
    expect(wrapper.emitted('open-appointment-modal')).toBeTruthy()
  })
})
