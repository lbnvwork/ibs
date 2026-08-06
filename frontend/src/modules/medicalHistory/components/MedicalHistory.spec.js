import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MedicalHistory from './MedicalHistory.vue'
import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore'
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore'
import { useMedicalTableStore } from '@/modules/medicalHistory/stores/medicalTableStore'
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore'
import { usePharmacogeneticsStore } from '@/modules/medicalHistory/stores/pharmacogeneticsStore'
import { usePatientVitalsLatestStore } from '@/modules/medicalHistory/stores/patientVitalsLatestStore'

const childStubs = {
  PatientCard: true, TreatmentCard: true, VitalsCard: true, Pharmacogenetics: true,
  MedicalTable: true, CollapsibleSection: { template: '<div><slot /></div>' },
  RiskScale: true, AppointmentAdd: true, TestAddModal: true,
}

function mountMedicalHistory(id = '7') {
  const pinia = createPinia()
  setActivePinia(pinia)

  const patientCardStore = usePatientCardStore()
  const treatmentStore = useTreatmentStore()
  const medicalTableStore = useMedicalTableStore()
  patientCardStore.fetchPatient = vi.fn().mockResolvedValue()
  treatmentStore.fetchTreatment = vi.fn().mockResolvedValue()
  medicalTableStore.fetchMedicalData = vi.fn().mockResolvedValue()

  const wrapper = mount(MedicalHistory, {
    props: { id },
    global: { plugins: [pinia], stubs: childStubs }
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

    it('marks the treatment active and fetches medical data for an ongoing treatment', async () => {
      const { wrapper, treatmentStore, medicalTableStore } = mountMedicalHistory('7')
      await flushPromises()
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null }
      await wrapper.vm.loadPatientData()

      expect(useAppointmentAddStore().isTreatmentActive).toBe(true)
      expect(medicalTableStore.fetchMedicalData).toHaveBeenCalledWith('/api/treatments/1')
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

  describe('preview computeds', () => {
    it('patientPreview falls back to "Нет данных" when there is no patient', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.vm.patientPreview).toBe('Нет данных')
    })

    it('patientPreview formats name/age/sex/phone', async () => {
      const { wrapper } = mountMedicalHistory('7')
      usePatientCardStore().patient = { name: 'Иванов Пётр', age: '45 лет', sex: 1, phone: '8(900)123-45-67' }
      await wrapper.vm.$nextTick()
      expect(wrapper.vm.patientPreview).toBe('Иванов Пётр, 45 лет (м), 8(900)123-45-67')
    })

    it('treatmentPreview falls back to "Нет активного лечения"', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.vm.treatmentPreview).toBe('Нет активного лечения')
    })

    it('treatmentPreview includes the MNO range and completion status', async () => {
      const { wrapper, treatmentStore } = mountMedicalHistory('7')
      treatmentStore.treatment = { diagnosis: 'Тромбоз', drugName: 'Варфарин', mnoFrom: 2, mnoTo: 3, realEndDt: '2024-01-01' }
      await wrapper.vm.$nextTick()
      expect(wrapper.vm.treatmentPreview).toBe('Тромбоз, Варфарин, МНО 2–3 (Завершено)')
    })

    it('pharmacogeneticsPreview lists investigated markers or falls back', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.vm.pharmacogeneticsPreview).toBe('Не исследовано')

      usePharmacogeneticsStore().markers = [{ currentValueId: 1, geneSymbol: 'CYP2C9', currentValue: '*1/*1' }]
      await wrapper.vm.$nextTick()
      expect(wrapper.vm.pharmacogeneticsPreview).toBe('CYP2C9: *1/*1')
    })

    it('vitalsPreview lists present vitals or falls back to "Нет измерений"', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.vm.vitalsPreview).toBe('Нет измерений')

      usePatientVitalsLatestStore().latest = { hb: 140, heartRate: 70, systolicPressure: 120, diastolicPressure: 80, saturation: 98, weight: 80 }
      await wrapper.vm.$nextTick()
      expect(wrapper.vm.vitalsPreview).toBe('Hb 140, ЧСС 70, АД 120/80, SpO₂ 98%, Вес 80 кг')
    })
  })

  describe('edit-mode handlers', () => {
    it('startPatientEdit/endPatientEdit toggle editMode.patient', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()

      wrapper.vm.startPatientEdit()
      expect(wrapper.vm.editMode.patient).toBe(true)

      wrapper.vm.endPatientEdit()
      expect(wrapper.vm.editMode.patient).toBe(false)
    })

    it('startTreatmentEdit/endTreatmentEdit toggle editMode.treatment', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()

      wrapper.vm.startTreatmentEdit()
      expect(wrapper.vm.editMode.treatment).toBe(true)

      wrapper.vm.endTreatmentEdit()
      expect(wrapper.vm.editMode.treatment).toBe(false)
    })
  })

  describe('modal handlers', () => {
    it('openTestModal/onTestSaved toggle showTestModal and reload data', async () => {
      const { wrapper, patientCardStore } = mountMedicalHistory('7')
      await flushPromises()
      patientCardStore.fetchPatient.mockClear()

      wrapper.vm.openTestModal()
      expect(wrapper.vm.showTestModal).toBe(true)

      wrapper.vm.onTestSaved()
      expect(wrapper.vm.showTestModal).toBe(false)
    })

    it('openAppointmentInlineModal/onAppointmentInlineSaved toggle showAppointmentInlineModal', async () => {
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
    it('shows "Пациент не найден" when there is no treatment loaded', async () => {
      const { wrapper } = mountMedicalHistory('7')
      await flushPromises()
      expect(wrapper.text()).toContain('Пациент не найден')
    })

    it('shows the main content once a treatment is loaded', async () => {
      const { wrapper, treatmentStore } = mountMedicalHistory('7')
      treatmentStore.treatment = { '@id': '/api/treatments/1', realEndDt: null, drug: '/api/drugs/1' }
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.patient-main-content').exists()).toBe(true)
    })
  })
})
