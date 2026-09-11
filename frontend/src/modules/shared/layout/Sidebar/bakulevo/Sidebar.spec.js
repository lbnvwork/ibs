import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import Sidebar from './Sidebar.vue'
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore'
import { useTestAddStore } from '@/modules/medicalHistory/stores/testAddStore'
import { useAuthStore } from '@/modules/shared/stores/authStore'
import { HOME_PATH, PATIENT_ADD_PATH } from '@/router/paths'

function mountSidebar({ routeName = 'Home', routePath = '/', backTarget = undefined, push = vi.fn() } = {}) {
  setActivePinia(createPinia())
  const wrapper = mount(Sidebar, {
    global: {
      mocks: {
        $route: { name: routeName, path: routePath, meta: { backTarget } },
        $router: { push },
      }
    }
  })
  return { wrapper, push }
}

function findItem(wrapper, name) {
  return wrapper.vm.sidebarGroups.flatMap(g => g.items).find(i => i.name === name)
}

const STUB_NAMES = [
  'sendMessage', 'editData', 'calendar', 'aiHelp', 'statistics',
  'disabledPatients', 'chat', 'print', 'saveFormats'
]

describe('Sidebar.vue (bakulevo — доработка №1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  // --- Кнопка «Назад» (сохранено из исходного) ---
  it('disables the back button on the home page', () => {
    const { wrapper } = mountSidebar({ routePath: HOME_PATH })
    expect(wrapper.vm.isBackButtonActive).toBe(false)
  })

  it('enables the back button anywhere else', () => {
    const { wrapper } = mountSidebar({ routePath: '/patient/5' })
    expect(wrapper.vm.isBackButtonActive).toBe(true)
  })

  it('navigates to the route backTarget when set', () => {
    const { wrapper, push } = mountSidebar({ routePath: '/patient/5', backTarget: '/patient/5' })
    wrapper.vm.handleBackButton()
    expect(push).toHaveBeenCalledWith('/patient/5')
  })

  it('falls back to HOME_PATH when there is no backTarget', () => {
    const { wrapper, push } = mountSidebar({ routePath: '/patient/5' })
    wrapper.vm.handleBackButton()
    expect(push).toHaveBeenCalledWith(HOME_PATH)
  })

  // --- Структура (СЦ-3.58.17) ---
  describe('structure', () => {
    it('has exactly the two groups Пациенты and Рекомендации, expanded by default', () => {
      const { wrapper } = mountSidebar()
      expect(wrapper.vm.sidebarGroups.map(g => g.id)).toEqual(['patients', 'recommendations'])
      expect(wrapper.vm.sidebarGroups.every(g => g.expanded)).toBe(true)
    })

    it('contains no stub items', () => {
      const { wrapper } = mountSidebar()
      const names = wrapper.vm.sidebarGroups.flatMap(g => g.items).map(i => i.name)
      STUB_NAMES.forEach(s => expect(names).not.toContain(s))
    })

    it('renders two group headers and four sub-items in the DOM', () => {
      const { wrapper } = mountSidebar()
      expect(wrapper.findAll('.sidebar-group__header').length).toBe(2)
      expect(wrapper.findAll('.sidebar-group__item').length).toBe(4)
    })

    it('collapses/expands a group via toggleGroup', () => {
      const { wrapper } = mountSidebar()
      const group = wrapper.vm.sidebarGroups[0]
      wrapper.vm.toggleGroup(group)
      expect(group.expanded).toBe(false)
      wrapper.vm.toggleGroup(group)
      expect(group.expanded).toBe(true)
    })
  })

  // --- Навигация (СЦ-3.58.18) ---
  describe('navigation', () => {
    it('patientList navigates to HOME_PATH', () => {
      const { wrapper, push } = mountSidebar()
      wrapper.vm.handleItemClick(findItem(wrapper, 'patientList'))
      expect(push).toHaveBeenCalledWith(HOME_PATH)
    })

    it('patientAdd navigates to PATIENT_ADD_PATH', () => {
      const { wrapper, push } = mountSidebar()
      wrapper.vm.handleItemClick(findItem(wrapper, 'patientAdd'))
      expect(push).toHaveBeenCalledWith(PATIENT_ADD_PATH)
    })
  })

  // --- Гейтинг «Назначение»/«Анализ» (СЦ-3.58.19) ---
  describe('appointment/testAdd gating', () => {
    it('appointment is disabled outside MedicalHistory', () => {
      const { wrapper } = mountSidebar({ routeName: 'Home' })
      expect(wrapper.vm.isItemDisabled(findItem(wrapper, 'appointment'))).toBe(true)
    })

    it('appointment is enabled on MedicalHistory with active treatment', () => {
      const { wrapper } = mountSidebar({ routeName: 'MedicalHistory' })
      useAppointmentAddStore().isTreatmentActive = true
      expect(wrapper.vm.isItemDisabled(findItem(wrapper, 'appointment'))).toBe(false)
    })

    it('testAdd opens the appointment-style modal via testAddStore', () => {
      const { wrapper } = mountSidebar({ routeName: 'MedicalHistory' })
      const appStore = useAppointmentAddStore()
      appStore.isTreatmentActive = true
      appStore.openModal = vi.fn()
      const testStore = useTestAddStore()
      testStore.openModal = vi.fn()

      wrapper.vm.handleItemClick(findItem(wrapper, 'testAdd'))

      expect(testStore.openModal).toHaveBeenCalled()
    })

    it('testAdd does not open the modal outside MedicalHistory', () => {
      const { wrapper } = mountSidebar({ routeName: 'Home' })
      const testStore = useTestAddStore()
      testStore.openModal = vi.fn()

      wrapper.vm.handleItemClick(findItem(wrapper, 'testAdd'))

      expect(testStore.openModal).not.toHaveBeenCalled()
    })
  })

  // --- Выход (СЦ-3.58.21) ---
  describe('logout', () => {
    it('renders a logout button with label Выход', () => {
      const { wrapper } = mountSidebar()
      expect(wrapper.find('.sidebar__logout').exists()).toBe(true)
      expect(wrapper.find('.sidebar__logout').text()).toContain('Выход')
    })

    it('calls authStore.logout()', () => {
      const { wrapper } = mountSidebar()
      const auth = useAuthStore()
      auth.logout = vi.fn()
      wrapper.vm.handleLogout()
      expect(auth.logout).toHaveBeenCalled()
    })
  })

  // --- Темизация (3.58, СЦ-3.58.13) ---
  describe('темизация (3.58)', () => {
    it('Бакулево: пункты с подписями, логотип, «Служба поддержки» (СЦ-3.58.13)', () => {
      const { wrapper } = mountSidebar()
      expect(wrapper.find('.sidebar').classes()).toContain('sidebar--text')
      expect(wrapper.findAll('.label').length).toBeGreaterThan(0)
      expect(wrapper.find('.sidebar__logo').exists()).toBe(true)
      expect(wrapper.find('.sidebar__logo img').exists()).toBe(true)
      expect(wrapper.find('.sidebar__logo').text()).toContain('Coag Analyzer')
      expect(wrapper.find('.sidebar__support').exists()).toBe(true)
      expect(wrapper.find('.sidebar__support').text()).toContain('Служба поддержки')
    })
  })

  // --- Сворачивание сайдбара ---
  describe('collapse', () => {
    it('toggles collapsed state via toggleSidebar', () => {
      const { wrapper } = mountSidebar()
      expect(wrapper.vm.collapsed).toBe(false)
      wrapper.vm.toggleSidebar()
      expect(wrapper.vm.collapsed).toBe(true)
      wrapper.vm.toggleSidebar()
      expect(wrapper.vm.collapsed).toBe(false)
    })

    it('applies sidebar--collapsed class when collapsed', async () => {
      const { wrapper } = mountSidebar()
      wrapper.vm.toggleSidebar()
      await wrapper.vm.$nextTick()
      expect(wrapper.find('.sidebar').classes()).toContain('sidebar--collapsed')
    })

    it('hides group sub-items when collapsed', async () => {
      const { wrapper } = mountSidebar()
      wrapper.vm.toggleSidebar()
      await wrapper.vm.$nextTick()
      expect(wrapper.findAll('.sidebar-group__item').length).toBe(0)
    })

    it('expands the sidebar when a collapsed group header is clicked', () => {
      const { wrapper } = mountSidebar()
      wrapper.vm.toggleSidebar()
      const group = wrapper.vm.sidebarGroups[0]
      wrapper.vm.toggleGroup(group)
      expect(wrapper.vm.collapsed).toBe(false)
      expect(group.expanded).toBe(true)
    })
  })
})
