import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Sidebar from './Sidebar.vue'
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore'
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

describe('Sidebar.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('disables the back button on the home page', () => {
    const { wrapper } = mountSidebar({ routePath: HOME_PATH })
    expect(wrapper.vm.isBackButtonActive).toBe(false)
  })

  it('enables the back button anywhere else', () => {
    const { wrapper } = mountSidebar({ routePath: '/patient/5' })
    expect(wrapper.vm.isBackButtonActive).toBe(true)
  })

  it('navigates to the route backTarget when set', async () => {
    const { wrapper, push } = mountSidebar({ routePath: '/patient/5', backTarget: '/patient/5' })
    wrapper.vm.handleBackButton()
    expect(push).toHaveBeenCalledWith('/patient/5')
  })

  it('falls back to HOME_PATH when there is no backTarget', () => {
    const { wrapper, push } = mountSidebar({ routePath: '/patient/5' })
    wrapper.vm.handleBackButton()
    expect(push).toHaveBeenCalledWith(HOME_PATH)
  })

  describe('the "recommendations" item', () => {
    it('is disabled outside the MedicalHistory route', () => {
      const { wrapper } = mountSidebar({ routeName: 'Home' })
      const item = wrapper.vm.sidebarItems.find(i => i.name === 'recommendations')
      expect(item.disabled).toBe(true)
    })

    it('is disabled on MedicalHistory when there is no active treatment', () => {
      const { wrapper } = mountSidebar({ routeName: 'MedicalHistory' })
      useAppointmentAddStore().isTreatmentActive = false
      const item = wrapper.vm.sidebarItems.find(i => i.name === 'recommendations')
      expect(item.disabled).toBe(true)
    })

    it('is enabled on MedicalHistory with an active treatment', () => {
      const { wrapper } = mountSidebar({ routeName: 'MedicalHistory' })
      useAppointmentAddStore().isTreatmentActive = true
      const item = wrapper.vm.sidebarItems.find(i => i.name === 'recommendations')
      expect(item.disabled).toBe(false)
    })

    it('opens the appointment modal only when active on MedicalHistory', () => {
      const { wrapper } = mountSidebar({ routeName: 'MedicalHistory' })
      const store = useAppointmentAddStore()
      store.isTreatmentActive = true
      store.openModal = vi.fn()

      wrapper.vm.handleButtonClick({ name: 'recommendations' })

      expect(store.openModal).toHaveBeenCalled()
    })

    it('does not open the modal when not on MedicalHistory', () => {
      const { wrapper } = mountSidebar({ routeName: 'Home' })
      const store = useAppointmentAddStore()
      store.isTreatmentActive = true
      store.openModal = vi.fn()

      wrapper.vm.handleButtonClick({ name: 'recommendations' })

      expect(store.openModal).not.toHaveBeenCalled()
    })
  })

  it('navigates to PATIENT_ADD_PATH for the patientAdd item', () => {
    const { wrapper, push } = mountSidebar()
    wrapper.vm.handleButtonClick({ name: 'patientAdd' })
    expect(push).toHaveBeenCalledWith(PATIENT_ADD_PATH)
  })

  it('does nothing (but does not throw) for unimplemented items', () => {
    const { wrapper } = mountSidebar()
    expect(() => wrapper.vm.handleButtonClick({ name: 'chat' })).not.toThrow()
  })

  it('renders a button for every sidebar item plus the back button', () => {
    const { wrapper } = mountSidebar()
    const buttons = wrapper.findAll('button')
    // back button + all non-divider items
    const buttonItems = wrapper.vm.sidebarItems.filter(i => i.type === 'button')
    expect(buttons.length).toBe(buttonItems.length + 1)
  })
})
