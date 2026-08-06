import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PatientTable from './PatientTable.vue'

const tabs = [
  { id: 'all', name: 'Все' },
  { id: 'warfarin', name: 'Варфарин' },
]

function mountTable(props = {}) {
  return mount(PatientTable, {
    props: {
      tabs,
      activeTab: 'all',
      selectedDiagnosis: [],
      patients: [],
      loading: false,
      error: '',
      totalPages: 1,
      currentPage: 1,
      pageInput: 1,
      ...props,
    },
    global: { stubs: { MultiDiagnosisSelect: true, 'router-link': { template: '<a><slot /></a>' } } }
  })
}

describe('PatientTable.vue', () => {
  it('shows a loading row while loading', () => {
    const wrapper = mountTable({ loading: true })
    expect(wrapper.find('.loading-row').exists()).toBe(true)
  })

  it('shows an empty-state row when there are no patients and no error', () => {
    const wrapper = mountTable({ patients: [] })
    expect(wrapper.find('.empty-row').text()).toBe('Нет пациентов для отображения')
  })

  it('shows the error row when an error is present', () => {
    const wrapper = mountTable({ error: 'Ошибка сети' })
    expect(wrapper.find('.error-row').text()).toBe('Ошибка сети')
  })

  it('renders one row per patient', () => {
    const wrapper = mountTable({
      patients: [
        { id: 1, name: 'Иванов Пётр', age: '45', diagnosis: 'I80', indicators: '', comment: '', smsStatus: '✓' },
        { id: 2, name: 'Петров Иван', age: '50', diagnosis: 'I26', indicators: '', comment: '', smsStatus: '✗' },
      ]
    })
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(wrapper.text()).toContain('Иванов Пётр')
  })

  it('emits update:activeTab when a tab is clicked', async () => {
    const wrapper = mountTable()
    await wrapper.findAll('.tabs button')[1].trigger('click')
    expect(wrapper.emitted('update:activeTab')).toEqual([['warfarin']])
  })

  it('the selectedDiagnosisLocal setter emits update:selectedDiagnosis', () => {
    const wrapper = mountTable()
    wrapper.vm.selectedDiagnosisLocal = ['I80']
    expect(wrapper.emitted('update:selectedDiagnosis')).toEqual([[['I80']]])
  })

  it('hides the diagnosis filter when showDiagnosisFilter is false', () => {
    const wrapper = mountTable({ showDiagnosisFilter: false })
    expect(wrapper.find('.filter-controls').exists()).toBe(false)
  })

  describe('pagination', () => {
    it('is hidden when there is only one page', () => {
      const wrapper = mountTable({ totalPages: 1 })
      expect(wrapper.find('.pagination').exists()).toBe(false)
    })

    it('emits page navigation events', async () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 3 })
      const buttons = wrapper.findAll('.pagination button')

      await buttons[0].trigger('click')
      await buttons[1].trigger('click')
      await buttons[2].trigger('click')
      await buttons[3].trigger('click')

      expect(wrapper.emitted('firstPage')).toBeTruthy()
      expect(wrapper.emitted('prevPage')).toBeTruthy()
      expect(wrapper.emitted('nextPage')).toBeTruthy()
      expect(wrapper.emitted('lastPage')).toBeTruthy()
    })

    it('disables first/prev on the first page and next/last on the last page', () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 1 })
      const buttons = wrapper.findAll('.pagination button')
      expect(buttons[0].attributes('disabled')).toBeDefined()
      expect(buttons[1].attributes('disabled')).toBeDefined()
      expect(buttons[2].attributes('disabled')).toBeUndefined()
      expect(buttons[3].attributes('disabled')).toBeUndefined()
    })

    it('emits update:pageInput and goToPage from the page number input', async () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 1 })
      const input = wrapper.find('.pagination input')

      await input.setValue('3')
      await input.trigger('keyup.enter')

      expect(wrapper.emitted('update:pageInput')[0]).toEqual(['3'])
      expect(wrapper.emitted('goToPage')[0]).toEqual(['3'])
    })
  })
})
