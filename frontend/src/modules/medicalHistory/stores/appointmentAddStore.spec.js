import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAppointmentAddStore } from './appointmentAddStore'

describe('appointmentAddStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('starts closed and inactive', () => {
    const store = useAppointmentAddStore()
    expect(store.isModalOpen).toBe(false)
    expect(store.isTreatmentActive).toBe(false)
  })

  it('openModal/closeModal toggle the modal visibility', () => {
    const store = useAppointmentAddStore()
    store.openModal()
    expect(store.isModalOpen).toBe(true)
    store.closeModal()
    expect(store.isModalOpen).toBe(false)
  })

  it('setTreatmentActive updates the treatment-active flag', () => {
    const store = useAppointmentAddStore()
    store.setTreatmentActive(true)
    expect(store.isTreatmentActive).toBe(true)
  })
})
