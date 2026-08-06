import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PatientListPanel from './PatientListPanel.vue'
import { usePatientListStore } from '@/modules/patientManagement/stores/patientListStore'
import { useHospitalStore } from '@/modules/shared/stores/hospitalStore'
import { useDrugGroupStore } from '@/modules/shared/stores/drugGroupStore'
import { useAuthStore } from '@/modules/shared/stores/authStore'

class MockIntersectionObserver {
  constructor(callback) { this.callback = callback }
  observe() {}
  disconnect() {}
}

function mountPanel({ isLoginPage = false, authenticated = true, push = vi.fn() } = {}) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const authStore = useAuthStore()
  authStore.token = authenticated ? 'fake-token' : null

  const patientListStore = usePatientListStore()
  const hospitalStore = useHospitalStore()
  const drugGroupStore = useDrugGroupStore()
  patientListStore.loadMore = vi.fn().mockResolvedValue()
  patientListStore.selectPatient = vi.fn().mockResolvedValue()
  patientListStore.searchPatients = vi.fn().mockResolvedValue()
  patientListStore.clearSearch = vi.fn()
  patientListStore.setHospitalFilter = vi.fn()
  patientListStore.setDrugGroupFilter = vi.fn()
  hospitalStore.loadHospitals = vi.fn().mockResolvedValue()
  drugGroupStore.loadDrugGroups = vi.fn().mockResolvedValue()

  const wrapper = mount(PatientListPanel, {
    global: {
      plugins: [pinia],
      mocks: { $route: { meta: { isLoginPage } }, $router: { push } },
    }
  })
  return { wrapper, patientListStore, hospitalStore, drugGroupStore, authStore, push }
}

describe('PatientListPanel.vue', () => {
  const originalIO = global.IntersectionObserver

  beforeEach(() => {
    vi.clearAllMocks()
    global.IntersectionObserver = MockIntersectionObserver
  })

  afterEach(() => {
    global.IntersectionObserver = originalIO
    vi.useRealTimers()
  })

  describe('computed', () => {
    it('isLoginPage reflects the route meta flag', () => {
      const { wrapper } = mountPanel({ isLoginPage: true })
      expect(wrapper.vm.isLoginPage).toBe(true)
    })

    it('isAuthenticated reflects the auth store token', () => {
      const { wrapper } = mountPanel({ authenticated: false })
      expect(wrapper.vm.isAuthenticated).toBe(false)
    })

    it('selectedPatientInfo has a placeholder when no patient is selected', () => {
      const { wrapper } = mountPanel()
      expect(wrapper.vm.selectedPatientInfo).toEqual({ name: '', info: 'Выберите пациента' })
    })

    it('selectedPatientInfo formats the name/age/phone when a patient is selected', async () => {
      const { wrapper, patientListStore } = mountPanel()
      patientListStore.selectedPatient = {
        lastname: 'Иванов', firstname: 'Пётр', secondName: 'Ильич',
        birthday: '1980-01-01', smsPhone: '8(900)123-45-67',
      }
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.selectedPatientInfo.name).toBe('Иванов Пётр Ильич')
      expect(wrapper.vm.selectedPatientInfo.info).toContain('8(900)123-45-67')
    })
  })

  describe('created() lifecycle', () => {
    it('loads initial data when authenticated and not on the login page', async () => {
      const { hospitalStore, drugGroupStore, patientListStore } = mountPanel({ isLoginPage: false, authenticated: true })
      await flushPromises()

      expect(hospitalStore.loadHospitals).toHaveBeenCalled()
      expect(drugGroupStore.loadDrugGroups).toHaveBeenCalled()
      expect(patientListStore.loadMore).toHaveBeenCalled()
    })

    it('skips loading initial data on the login page', async () => {
      const { hospitalStore } = mountPanel({ isLoginPage: true, authenticated: true })
      await flushPromises()

      expect(hospitalStore.loadHospitals).not.toHaveBeenCalled()
    })

    it('skips loading initial data when not authenticated', async () => {
      const { hospitalStore } = mountPanel({ isLoginPage: false, authenticated: false })
      await flushPromises()

      expect(hospitalStore.loadHospitals).not.toHaveBeenCalled()
    })
  })

  describe('search and filters', () => {
    it('onClearSearch resets the query and clears the store search', () => {
      const { wrapper, patientListStore } = mountPanel()
      wrapper.vm.searchQuery = 'ivanov'

      wrapper.vm.onClearSearch()

      expect(wrapper.vm.searchQuery).toBe('')
      expect(patientListStore.clearSearch).toHaveBeenCalled()
    })

    it('onDrugGroupChange parses the selected id and forwards it, or null for the empty option', () => {
      const { wrapper, patientListStore } = mountPanel()

      wrapper.vm.onDrugGroupChange({ target: { value: '3' } })
      expect(patientListStore.setDrugGroupFilter).toHaveBeenCalledWith(3)

      wrapper.vm.onDrugGroupChange({ target: { value: '' } })
      expect(patientListStore.setDrugGroupFilter).toHaveBeenCalledWith(null)
    })

    it('onHospitalChange parses the selected id and forwards it, or null for the empty option', () => {
      const { wrapper, patientListStore } = mountPanel()

      wrapper.vm.onHospitalChange({ target: { value: '5' } })
      expect(patientListStore.setHospitalFilter).toHaveBeenCalledWith(5)

      wrapper.vm.onHospitalChange({ target: { value: '' } })
      expect(patientListStore.setHospitalFilter).toHaveBeenCalledWith(null)
    })

    it('onSearchInput searches after the debounce delay once at least 2 characters are typed', () => {
      vi.useFakeTimers()
      const { wrapper, patientListStore } = mountPanel()

      wrapper.vm.searchQuery = 'iv'
      wrapper.vm.onSearchInput()
      vi.advanceTimersByTime(300)

      expect(patientListStore.searchPatients).toHaveBeenCalledWith('iv')
    })

    it('onSearchInput clears the search once the query becomes empty', () => {
      vi.useFakeTimers()
      const { wrapper, patientListStore } = mountPanel()

      wrapper.vm.searchQuery = ''
      wrapper.vm.onSearchInput()
      vi.advanceTimersByTime(300)

      expect(patientListStore.clearSearch).toHaveBeenCalled()
    })
  })

  describe('handlePatientClick', () => {
    it('selects the patient then navigates to their page', async () => {
      const { wrapper, patientListStore, push } = mountPanel()

      await wrapper.vm.handlePatientClick({ id: 42 })

      expect(patientListStore.selectPatient).toHaveBeenCalledWith(42)
      expect(push).toHaveBeenCalledWith('/patient/42')
    })

    it('logs but does not throw when selecting the patient fails', async () => {
      const { wrapper, patientListStore } = mountPanel()
      patientListStore.selectPatient.mockRejectedValue(new Error('boom'))
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      await expect(wrapper.vm.handlePatientClick({ id: 42 })).resolves.not.toThrow()
      expect(consoleSpy).toHaveBeenCalled()
    })
  })
})
