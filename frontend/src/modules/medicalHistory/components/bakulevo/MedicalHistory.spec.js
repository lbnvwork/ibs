import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MedicalHistory from './MedicalHistory.vue'
import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore'
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore'
import { useMedicalTableStore } from '@/modules/medicalHistory/stores/medicalTableStore'
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore'
import { useTestAddStore } from '@/modules/medicalHistory/stores/testAddStore'
import { patientApi } from '@/modules/shared/api/patients'

vi.mock('@/modules/shared/api/patients', () => ({ patientApi: { getOne: vi.fn(), update: vi.fn() } }))

const childStubs = {
  DashboardHeader: true,
  MetricCard: true,
  ComplicationRisk: true,
  CurrentTherapy: true,
  RecentEvents: true,
  MnoChart: true,
  MedicalTable: true,
  PatientCard: true,
  TreatmentCard: true,
  VitalsCard: true,
  AppointmentAdd: true,
  TestAddModal: true,
}

function mountMedicalHistory(id = '7') {
  const pinia = createPinia()
  setActivePinia(pinia)

  const patientCardStore = usePatientCardStore()
  const treatmentStore = useTreatmentStore()
  const medicalTableStore = useMedicalTableStore()
  patientCardStore.fetchPatient = vi.fn().mockResolvedValue()
  treatmentStore.fetchTreatment = vi.fn().mockResolvedValue()
  treatmentStore.loadDrugsIfNeeded = vi.fn().mockResolvedValue()
  medicalTableStore.fetchMedicalData = vi.fn().mockResolvedValue()
  patientApi.getOne.mockResolvedValue({ sex: 1 })

  const wrapper = mount(MedicalHistory, {
    props: { id },
    global: { plugins: [pinia], stubs: childStubs },
  })
  return { wrapper, patientCardStore, treatmentStore, medicalTableStore }
}

describe('MedicalHistory.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('the id watcher (immediate)', () => {
    it('fetches the patient and treatment in parallel when mounted with an id', async () => {
      const { patientCardStore, treatmentStore } = mountMedicalHistory('7')
      await flushPromises()

      expect(patientCardStore.fetchPatient).toHaveBeenCalledWith('7')
      expect(treatmentStore.fetchTreatment).toHaveBeenCalledWith('7')
    })

    it('does nothing when mounted without an id', async () => {
      const { patientCardStore, treatmentStore } = mountMedicalHistory(null)
      await flushPromises()

      expect(patientCardStore.fetchPatient).not.toHaveBeenCalled()
      expect(treatmentStore.fetchTreatment).not.toHaveBeenCalled()
    })

    it('re-fetches when the id prop changes', async () => {
      const { wrapper, patientCardStore, treatmentStore } = mountMedicalHistory('7')
      await flushPromises()
      vi.clearAllMocks()

      await wrapper.setProps({ id: '9' })
      await flushPromises()

      expect(patientCardStore.fetchPatient).toHaveBeenCalledWith('9')
      expect(treatmentStore.fetchTreatment).toHaveBeenCalledWith('9')
    })
  })

  describe('loadPatientData', () => {
    it('stops loading without fetching medical data when there is no treatment', async () => {
      const { wrapper, medicalTableStore, treatmentStore } = mountMedicalHistory('7')
      treatmentStore.treatment = null
      await flushPromises()

      expect(wrapper.vm.loading).toBe(false)
      expect(medicalTableStore.fetchMedicalData).not.toHaveBeenCalled()
    })

    it('marks the treatment active and fetches medical data', async () => {
      const { wrapper, treatmentStore, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null, drug: '/api/drugs/1' }

      await wrapper.vm.loadPatientData()

      expect(useAppointmentAddStore().isTreatmentActive).toBe(true)
      expect(medicalTableStore.fetchMedicalData).toHaveBeenCalledWith('/api/treatments/1')
      expect(treatmentStore.loadDrugsIfNeeded).toHaveBeenCalled()
      expect(wrapper.vm.loading).toBe(false)
    })

    it('marks a finished treatment as inactive', async () => {
      const { wrapper, treatmentStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: '2024-01-01' }
      await wrapper.vm.loadPatientData()

      expect(useAppointmentAddStore().isTreatmentActive).toBe(false)
    })

    it('surfaces an error message when fetching medical data fails', async () => {
      const { wrapper, treatmentStore, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null }
      medicalTableStore.fetchMedicalData.mockRejectedValue(new Error('network error'))

      await wrapper.vm.loadPatientData()

      expect(wrapper.vm.error).toBe('Не удалось загрузить данные пациента.')
      expect(wrapper.vm.loading).toBe(false)
    })
  })

  describe('dashboard computeds', () => {
    it('latestMetrics picks the latest non-null value of every field', async () => {
      const { wrapper, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      medicalTableStore.events = [
        { mno: 2.5, hb: 140, heartRate: null, systolicPressure: null, diastolicPressure: null, saturation: 98, weight: null },
        { mno: null, hb: null, heartRate: 72, systolicPressure: 120, diastolicPressure: 80, saturation: null, weight: 78 },
      ]
      await flushPromises()

      expect(wrapper.vm.latestMetrics).toEqual({
        mno: 2.5, hb: 140, heartRate: 72,
        systolicPressure: 120, diastolicPressure: 80, saturation: 98, weight: 78,
      })
    })

    it('metrics builds six cards and tones МНО by the target range', async () => {
      const { wrapper, treatmentStore, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', mnoFrom: 2, mnoTo: 3 }
      medicalTableStore.events = [{ mno: 3.4, hb: 140, heartRate: 70, systolicPressure: 120, diastolicPressure: 80, saturation: 98, weight: 78 }]
      await flushPromises()

      const cards = wrapper.vm.metrics
      expect(cards).toHaveLength(6)
      expect(cards[0].label).toBe('МНО (INR)')
      expect(cards[0].value).toBe('3.4')
      expect(cards[0].tone).toBe('danger')
      expect(cards[0].hint).toBe('Целевой диапазон 2.00–3.00')
    })

    it('chartData maps only events with a numeric МНО', async () => {
      const { wrapper, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      medicalTableStore.events = [
        { date: '2024-01-02', mno: 2.5, prescribedDose: 5 },
        { date: '2024-01-01', mno: null, prescribedDose: 5 },
      ]
      await flushPromises()

      expect(wrapper.vm.chartData).toEqual([{ date: '2024-01-02', inr: 2.5, dose: 5 }])
    })

    it('doctorName and latestDose come from the newest event carrying them', async () => {
      const { wrapper, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      medicalTableStore.events = [
        { doctorName: 'Петров А. В.', prescribedDose: 2.5, displayDate: '02.01.2024', mno: 2.5 },
      ]
      await flushPromises()

      expect(wrapper.vm.doctorName).toBe('Петров А. В.')
      expect(wrapper.vm.latestDose).toBe(2.5)
      expect(wrapper.vm.latestDoseDate).toBe('02.01.2024')
    })

    it('drugName resolves the drug name from the loaded drugs list', async () => {
      const { wrapper, treatmentStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', drug: '/api/drugs/1' }
      treatmentStore.allDrugs = [{ id: 1, nominative: 'варфарин' }]
      await flushPromises()

      expect(wrapper.vm.drugName).toBe('варфарин')
    })

    it('recentEvents maps events to titled items', async () => {
      const { wrapper, treatmentStore, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', mnoFrom: 2, mnoTo: 3 }
      medicalTableStore.events = [
        { type: 'test', mno: 3.4, displayDate: '01.01.2024' },
        { type: 'appointment', prescribedDose: 5, displayDate: '02.01.2024', mno: null },
      ]
      await flushPromises()

      const items = wrapper.vm.recentEvents
      expect(items[0].title).toBe('Анализ МНО 3.4')
      expect(items[0].tone).toBe('danger')
      expect(items[1].title).toBe('Назначена доза 5')
    })
  })

  describe('tab and modal handlers', () => {
    it('switches between Обзор and История наблюдений', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.vm.activeTab).toBe('overview')
      wrapper.vm.activeTab = 'history'
      expect(wrapper.vm.activeTab).toBe('history')
    })

    it('openPatientEdit/closePatientEdit toggle the patient modal', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      wrapper.vm.openPatientEdit()
      expect(wrapper.vm.showPatientModal).toBe(true)
      wrapper.vm.closePatientEdit()
      expect(wrapper.vm.showPatientModal).toBe(false)
    })

    it('openTreatmentEdit/closeTreatmentEdit toggle the treatment modal', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      wrapper.vm.openTreatmentEdit()
      expect(wrapper.vm.showTreatmentModal).toBe(true)
      wrapper.vm.closeTreatmentEdit()
      expect(wrapper.vm.showTreatmentModal).toBe(false)
    })

    it('openVitalsEdit/closeVitalsEdit toggle the vitals modal and reload data', async () => {
      const { wrapper, medicalTableStore, treatmentStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null }
      await flushPromises()
      wrapper.vm.openVitalsEdit()
      expect(wrapper.vm.showVitalsModal).toBe(true)
      wrapper.vm.closeVitalsEdit()
      await flushPromises()
      expect(wrapper.vm.showVitalsModal).toBe(false)
      expect(medicalTableStore.fetchMedicalData).toHaveBeenCalled()
    })

    it('openTestModal/onTestSaved toggle showTestModal via the store', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      wrapper.vm.openTestModal()
      expect(useTestAddStore().isModalOpen).toBe(true)
      wrapper.vm.onTestSaved()
      expect(useTestAddStore().isModalOpen).toBe(false)
    })

    it('openAppointmentInlineModal/onAppointmentInlineSaved toggle the inline modal', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      wrapper.vm.openAppointmentInlineModal()
      expect(wrapper.vm.showAppointmentInlineModal).toBe(true)
      wrapper.vm.onAppointmentInlineSaved()
      expect(wrapper.vm.showAppointmentInlineModal).toBe(false)
    })

    it('closeAppointmentModal delegates to the appointmentAddStore', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      const store = useAppointmentAddStore()
      store.closeModal = vi.fn()

      wrapper.vm.closeAppointmentModal()

      expect(store.closeModal).toHaveBeenCalled()
    })
  })

  describe('rendering', () => {
    it('shows «Пациент не найден» when there is no treatment loaded', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.text()).toContain('Пациент не найден')
    })

    it('renders the dashboard header and tabs once patient + treatment are loaded', async () => {
      const { wrapper, patientCardStore, treatmentStore } = mountMedicalHistory('7')
      patientCardStore.patient = { id: '7', name: 'Иванов Пётр', age: '45 лет', sex: 1, phone: '8(900)123-45-67' }
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null, drug: '/api/drugs/1' }
      await flushPromises()

      expect(wrapper.findComponent({ name: 'DashboardHeader' }).exists()).toBe(true)
      expect(wrapper.text()).toContain('Обзор')
      expect(wrapper.text()).toContain('История наблюдений')
    })

    it('renders the overview widgets in the Обзор tab', async () => {
      const { wrapper, patientCardStore, treatmentStore } = mountMedicalHistory('7')
      patientCardStore.patient = { id: '7', name: 'Иванов Пётр', age: '45 лет', sex: 1, phone: '8(900)123-45-67' }
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null, drug: '/api/drugs/1' }
      await flushPromises()

      expect(wrapper.findComponent({ name: 'ComplicationRisk' }).exists()).toBe(true)
      expect(wrapper.findComponent({ name: 'CurrentTherapy' }).exists()).toBe(true)
      expect(wrapper.findComponent({ name: 'RecentEvents' }).exists()).toBe(true)
    })

    it('renders MedicalTable with hidden columns in the История наблюдений tab', async () => {
      const { wrapper, patientCardStore, treatmentStore } = mountMedicalHistory('7')
      patientCardStore.patient = { id: '7', name: 'Иванов Пётр', age: '45 лет', sex: 1, phone: '8(900)123-45-67' }
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null, drug: '/api/drugs/1' }
      await flushPromises()
      wrapper.vm.activeTab = 'history'
      await flushPromises()

      const table = wrapper.findComponent({ name: 'MedicalTable' })
      expect(table.exists()).toBe(true)
      expect(table.props('hideChart')).toBe(true)
      expect(table.props('hideRecommendations')).toBe(true)
      expect(table.props('hideComment')).toBe(true)
    })

    it('does not render RiskScale or Pharmacogenetics in Бакулево', async () => {
      const { wrapper, patientCardStore, treatmentStore } = mountMedicalHistory('7')
      patientCardStore.patient = { id: '7', name: 'Иванов Пётр', age: '45 лет', sex: 1, phone: '8(900)123-45-67' }
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null, drug: '/api/drugs/1' }
      await flushPromises()

      expect(wrapper.findComponent({ name: 'RiskScale' }).exists()).toBe(false)
      expect(wrapper.findComponent({ name: 'Pharmacogenetics' }).exists()).toBe(false)
      expect(wrapper.text()).not.toContain('Фармакогенетика')
    })
  })
})
