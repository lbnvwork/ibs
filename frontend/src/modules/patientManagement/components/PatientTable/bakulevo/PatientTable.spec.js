import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PatientTable from './PatientTable.vue'

const p = (overrides = {}) => ({
  id: 1,
  name: 'Иванов Пётр Сергеевич',
  birthDate: '12.04.1980',
  sex: 1,
  hospitalName: 'ФГБУ НМИЦ им. Бакулева',
  indicators: '',
  highlightRed: false,
  highlightBlue: false,
  ...overrides,
})

function mountTable(props = {}) {
  return mount(PatientTable, {
    props: {
      patients: [],
      loading: false,
      error: '',
      totalPages: 1,
      currentPage: 1,
      itemsPerPage: 30,
      totalCount: 0,
      ...props,
    },
    global: { stubs: { 'router-link': { template: '<a><slot /></a>' } } }
  })
}

describe('PatientTable.vue (Бакулево)', () => {
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

  it('renders the template columns in the header', () => {
    const wrapper = mountTable()
    const headers = wrapper.findAll('.list-head .col').map(h => h.text())
    expect(headers).toEqual(['Статус', 'ФИО', 'Дата рождения', 'Пол', 'ЛПУ', 'Показатели', 'Результат'])
  })

  it('renders one row per patient with the new fields', () => {
    const wrapper = mountTable({ patients: [p(), p({ id: 2, name: 'Петров Иван' })] })
    const rows = wrapper.findAll('.patient-row')
    expect(rows).toHaveLength(2)
    expect(wrapper.text()).toContain('Иванов Пётр Сергеевич')
    expect(wrapper.text()).toContain('12.04.1980')
    expect(wrapper.text()).toContain('ФГБУ НМИЦ им. Бакулева')
  })

  // Вкладки препаратов и фильтр по нозологиям перенесены в PatientWorkList (bakulevo):
  // здесь остаётся только таблица + пагинация + легенда.
  it('card container keeps the patient-card class', () => {
    const wrapper = mountTable()
    expect(wrapper.find('.patient-table').classes()).toContain('patient-card')
  })

  describe('triage', () => {
    it('danger «Угроза кровотечения» by highlightRed', () => {
      const wrapper = mountTable()
      expect(wrapper.vm.triageBadge(p({ highlightRed: true }))).toEqual({ label: 'Угроза кровотечения', type: 'danger' })
    })

    it('info «Риск тромбоза» by highlightBlue', () => {
      const wrapper = mountTable()
      expect(wrapper.vm.triageBadge(p({ highlightBlue: true }))).toEqual({ label: 'Риск тромбоза', type: 'info' })
    })

    it('success «В норме» when no flags', () => {
      const wrapper = mountTable()
      expect(wrapper.vm.triageBadge(p())).toEqual({ label: 'В норме', type: 'success' })
    })

    it('renders a danger badge in the result column', () => {
      const wrapper = mountTable({ patients: [p({ highlightRed: true })] })
      const badge = wrapper.find('.triage-badge')
      expect(badge.text()).toBe('Угроза кровотечения')
      expect(badge.classes()).toContain('triage-badge--danger')
    })

    it('renders a status droplet with the triage modifier', () => {
      const wrapper = mountTable({ patients: [p({ highlightBlue: true })] })
      expect(wrapper.find('.status-icon--info').exists()).toBe(true)
    })
  })

  describe('gender', () => {
    it('maps sex 1 → ♂, 0 → ♀, else → —', () => {
      const wrapper = mountTable()
      expect(wrapper.vm.genderIcon(1)).toBe('♂')
      expect(wrapper.vm.genderIcon(0)).toBe('♀')
      expect(wrapper.vm.genderIcon(undefined)).toBe('—')
    })
  })

  describe('pagination', () => {
    it('is hidden when there is only one page', () => {
      const wrapper = mountTable({ totalPages: 1 })
      expect(wrapper.find('.pagination').exists()).toBe(false)
    })

    it('shows the total count and page buttons', () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 3, totalCount: 128 })
      expect(wrapper.find('.pagination-total').text()).toBe('Всего пациентов: 128')
      expect(wrapper.findAll('.pagination button').length).toBeGreaterThan(0)
    })

    it('marks the current page as active', () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 3 })
      const active = wrapper.findAll('.pagination button.page-btn.active')
      expect(active.length).toBe(1)
      expect(active[0].text()).toBe('3')
    })

    it('disables prev on the first page and keeps next enabled', () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 1 })
      const buttons = wrapper.findAll('.pagination button')
      expect(buttons[0].attributes('disabled')).toBeDefined()
      expect(buttons[buttons.length - 1].attributes('disabled')).toBeUndefined()
    })

    it('emits goToPage when a page button is clicked', async () => {
      const wrapper = mountTable({ totalPages: 5, currentPage: 1 })
      const pageBtn = wrapper.findAll('.pagination button.page-btn').find(b => b.text() === '3')
      await pageBtn.trigger('click')
      expect(wrapper.emitted('goToPage')).toEqual([[3]])
    })

    it('emits setItemsPerPage from the page-size selector', async () => {
      const wrapper = mountTable({ totalPages: 5 })
      const select = wrapper.find('#page-size')
      await select.setValue('20')
      expect(wrapper.emitted('setItemsPerPage')).toEqual([[20]])
    })
  })

  describe('legend', () => {
    it('shows the three active statuses with the legend title', () => {
      const wrapper = mountTable({
        patients: [p({ highlightRed: true }), p({ id: 2, highlightBlue: true }), p({ id: 3 })],
      })
      const legend = wrapper.find('.status-legend')
      expect(legend.exists()).toBe(true)
      expect(legend.text()).toContain('Статусы пациентов')
      expect(legend.text()).toContain('Угроза кровотечения')
      expect(legend.text()).toContain('Риск тромбоза')
      expect(legend.text()).toContain('В норме')
    })
  })
})
