import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AppointmentAdd from './AppointmentAdd.vue'
import apiClient from '@/modules/shared/api/client'
import { testHistoryApi } from '@/modules/shared/api/testHistory'

vi.mock('@/modules/shared/api/client', () => ({ default: { get: vi.fn(), post: vi.fn() } }))
vi.mock('@/modules/shared/api/testHistory', () => ({ testHistoryApi: { getLatestByTreatments: vi.fn() } }))

function mountAppointmentAdd(lastAppointments = [], props = {}) {
  apiClient.get.mockResolvedValueOnce({ data: { member: lastAppointments } })
  testHistoryApi.getLatestByTreatments.mockResolvedValue([])
  return mount(AppointmentAdd, {
    props: { treatment: '/api/treatments/10', drugId: 1, treatmentId: 10, drugGenitive: '', ...props }
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

    it('sends the second dose computed from the deviation when alternation is enabled (СЦ-3.65.1/2)', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.5
      wrapper.vm.enableAlternation = true
      wrapper.vm.alternationDelta = '0.5'

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/appointments', expect.objectContaining({ doze: 2.5, doze2: 3.0 }))
    })

    it('computes the second dose from the selected deviation (СЦ-3.65.1/2)', () => {
      const wrapper = mountAppointmentAdd()
      wrapper.vm.dose = 2.5
      wrapper.vm.enableAlternation = true

      wrapper.vm.alternationDelta = '0.25'
      expect(wrapper.vm.dose2).toBe(2.75)

      wrapper.vm.alternationDelta = '-0.5'
      expect(wrapper.vm.dose2).toBe(2.0)
    })

    it('rejects saving when alternation is enabled but no deviation is selected', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.5
      wrapper.vm.enableAlternation = true
      wrapper.vm.alternationDelta = null

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toContain('отклонение')
      expect(apiClient.post).not.toHaveBeenCalledWith('/appointments', expect.anything())
    })

    it('rejects a second dose above the 10 tablet maximum', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 10
      wrapper.vm.enableAlternation = true
      wrapper.vm.alternationDelta = '0.5'

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toContain('Максимальная доза 10')
      expect(apiClient.post).not.toHaveBeenCalledWith('/appointments', expect.anything())
    })

    it('sends nextTestDt when provided (СЦ-3.66.1)', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.25
      wrapper.vm.appointmentDt = '2026-08-10'
      wrapper.vm.nextTestDt = '2026-08-20'

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/appointments', expect.objectContaining({
        nextTestDt: '2026-08-20T00:00:00.000Z'
      }))
    })

    it('rejects a next test date before the appointment date (СЦ-3.66.4)', async () => {
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.25
      wrapper.vm.appointmentDt = '2026-08-10'
      wrapper.vm.nextTestDt = '2026-08-09'

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toContain('следующей сдачи')
      expect(apiClient.post).not.toHaveBeenCalledWith('/appointments', expect.anything())
    })
  })

  describe('patient message autofill', () => {
    it('autofills the message for an ordinary dose (СЦ-3.69.1)', async () => {
      apiClient.post.mockResolvedValue({ data: { body: 'Ваше МНО - 2.3. С 01.08.2026 ВАМ НУЖНО ПРИНИМАТЬ 2.5 варфарина.' } })
      const wrapper = mountAppointmentAdd([], { drugGenitive: 'варфарина' })
      await flushPromises()
      wrapper.vm.dose = 2.5
      wrapper.vm.mno = 2.3
      wrapper.vm.appointmentDt = '2026-08-01'

      await wrapper.vm.autofillComment()

      expect(apiClient.post).toHaveBeenCalledWith('/notification_templates/resolve', expect.objectContaining({
        code: 'appointment_dose',
        data: expect.objectContaining({ dose: 2.5, mno: 2.3, drug_genitive: 'варфарина', date: '01.08.2026' })
      }))
      expect(wrapper.vm.comment).toBe('Ваше МНО - 2.3. С 01.08.2026 ВАМ НУЖНО ПРИНИМАТЬ 2.5 варфарина.')
    })

    it('autofills the message for alternation (СЦ-3.69.2)', async () => {
      apiClient.post.mockResolvedValue({ data: { body: 'Ваше МНО - 2.3. С 01.08.2026 ВАМ НУЖНО ЧЕРЕДОВАТЬ 2.5 и 2.75 варфарина.' } })
      const wrapper = mountAppointmentAdd([], { drugGenitive: 'варфарина' })
      await flushPromises()
      wrapper.vm.dose = 2.5
      wrapper.vm.mno = 2.3
      wrapper.vm.enableAlternation = true
      wrapper.vm.alternationDelta = '0.25'

      await wrapper.vm.autofillComment()

      expect(apiClient.post).toHaveBeenCalledWith('/notification_templates/resolve', expect.objectContaining({
        code: 'appointment_alternate',
        data: expect.objectContaining({ dose: 2.5, sdose: 2.75, drug_genitive: 'варфарина' })
      }))
      expect(wrapper.vm.comment).toBe('Ваше МНО - 2.3. С 01.08.2026 ВАМ НУЖНО ЧЕРЕДОВАТЬ 2.5 и 2.75 варфарина.')
    })

    it('does not overwrite the message after a manual edit (СЦ-3.69.3)', async () => {
      apiClient.post.mockResolvedValue({ data: { body: 'Автозаполненный текст' } })
      const wrapper = mountAppointmentAdd([], { drugGenitive: 'варфарина' })
      await flushPromises()
      wrapper.vm.dose = 2.5
      wrapper.vm.mno = 2.3

      await wrapper.vm.autofillComment()
      expect(wrapper.vm.comment).toBe('Автозаполненный текст')

      wrapper.vm.comment = 'Отредактированный врачом текст'
      wrapper.vm.markCommentDirty()
      wrapper.vm.dose = 3.0

      await wrapper.vm.autofillComment()

      expect(wrapper.vm.comment).toBe('Отредактированный врачом текст')
    })

    it('sends the final comment as payload on save', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountAppointmentAdd()
      await flushPromises()
      wrapper.vm.dose = 2.5
      wrapper.vm.comment = 'Итоговое сообщение пациенту'

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/appointments', expect.objectContaining({ comment: 'Итоговое сообщение пациенту' }))
    })

    it('labels the field «Сообщение пациенту»', () => {
      const wrapper = mountAppointmentAdd()
      const labels = wrapper.findAll('label').map(l => l.text())
      expect(labels.some(t => t.includes('Сообщение пациенту'))).toBe(true)
    })
  })
})
