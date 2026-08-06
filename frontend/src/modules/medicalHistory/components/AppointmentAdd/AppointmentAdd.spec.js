import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AppointmentAdd from './AppointmentAdd.vue'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

function mountAppointmentAdd(lastAppointments = []) {
  apiClient.get.mockResolvedValueOnce({ data: { member: lastAppointments } })
  return mount(AppointmentAdd, {
    props: { treatment: '/api/treatments/10', drugId: 1, treatmentId: 10 }
  })
}

describe('AppointmentAdd.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads the previous appointment dose on mount for later comparison', async () => {
    const wrapper = mountAppointmentAdd([{ doze: 2 }])
    await flushPromises()

    expect(apiClient.get).toHaveBeenCalledWith('/appointments', {
      params: { treatment: '/api/treatments/10', itemsPerPage: 1, order: { appointmentDt: 'desc' } }
    })
    expect(wrapper.vm.lastAppointmentDose).toBe(2)
  })

  describe('calculateDose', () => {
    it('populates variants, explanation and selects the first (main) variant', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      apiClient.get.mockResolvedValue({
        data: { variants: [{ label: 'Основной', dose: 2.25 }, { label: 'Сниженный', dose: 2 }], explanation: 'МНО в норме' }
      })

      await wrapper.vm.calculateDose()

      expect(apiClient.get).toHaveBeenCalledWith('/dosage/recommendation', { params: { treatment_id: 10 } })
      expect(wrapper.vm.dose).toBe(2.25)
      expect(wrapper.vm.selectedVariant).toBe(0)
      expect(wrapper.vm.error).toBeNull()
    })

    it('shows an error when there are no variants (e.g. treatment not found)', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      apiClient.get.mockResolvedValue({ data: { variants: [], explanation: 'Лечение не найдено' } })

      await wrapper.vm.calculateDose()

      expect(wrapper.vm.error).toBe('Лечение не найдено')
      expect(wrapper.vm.dose).toBeNull()
    })

    it('shows a connection error message when the request fails (regression: the reported 404 bug)', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      apiClient.get.mockRejectedValue({ response: { status: 404 } })

      await wrapper.vm.calculateDose()

      expect(wrapper.vm.error).toBe('Не удалось рассчитать дозу. Проверьте соединение или повторите позже.')
      expect(wrapper.vm.isLoading).toBe(false)
    })
  })

  describe('dose change warning', () => {
    it('warns when the new dose differs from the last one by more than 50%', async () => {
      const wrapper = mountAppointmentAdd([{ doze: 2 }])
      await flushPromises()

      wrapper.vm.dose = 3.5
      wrapper.vm.onDoseManualChange()

      expect(wrapper.vm.showDoseWarning).toBe(true)
      expect(wrapper.vm.selectedVariant).toBeNull()
    })

    it('does not warn for a small dose change', async () => {
      const wrapper = mountAppointmentAdd([{ doze: 2 }])
      await flushPromises()

      wrapper.vm.dose = 2.25
      wrapper.vm.onDoseManualChange()

      expect(wrapper.vm.showDoseWarning).toBe(false)
    })
  })

  describe('validation and save', () => {
    it('rejects a dose that is not a multiple of 0.25', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 1.1

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toContain('кратна 0.25')
      expect(apiClient.post).not.toHaveBeenCalled()
    })

    it('rejects a dose above the 10 tablet maximum', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 12

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toContain('Максимальная доза 10 таблеток')
    })

    it('requires confirmation before saving a >50% dose change, then saves on the second call', async () => {
      const wrapper = mountAppointmentAdd([{ doze: 2 }])
      await flushPromises()
      apiClient.post.mockResolvedValue({})

      wrapper.vm.dose = 3.5
      wrapper.vm.onDoseManualChange()

      await wrapper.vm.save()
      expect(apiClient.post).not.toHaveBeenCalled()
      expect(wrapper.vm.confirmOver50).toBe(true)

      await wrapper.vm.save()
      expect(apiClient.post).toHaveBeenCalledTimes(1)
      expect(wrapper.emitted('saved')).toBeTruthy()
    })

    it('saves a valid appointment and emits "saved"', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.25

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/appointments', expect.objectContaining({
        treatment: '/api/treatments/10',
        doze: 2.25,
        doze2: -1,
        drug: '/api/drugs/1'
      }))
      expect(wrapper.emitted('saved')).toBeTruthy()
    })

    it('shows an inactive-treatment message on a 422 response', async () => {
      apiClient.post.mockRejectedValue({ response: { status: 422 } })
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.25

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toBe('Лечение не активно. Сохранение назначения невозможно.')
    })

    it('sends the second dose only when alternation is enabled', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.25
      wrapper.vm.enableAlternation = true
      wrapper.vm.dose2 = 1.75

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/appointments', expect.objectContaining({ doze2: 1.75 }))
    })
  })
})
