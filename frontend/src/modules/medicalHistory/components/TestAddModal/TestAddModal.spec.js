import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TestAddModal from './TestAddModal.vue'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({ default: { post: vi.fn(), get: vi.fn() } }))

// opts может быть массивом (последние назначения, 3.65) или объектом (переопределения props, 3.68).
function mountTestAddModal(opts = {}) {
  const lastAppointments = Array.isArray(opts) ? opts : []
  const overrides = Array.isArray(opts) ? {} : opts
  apiClient.get.mockResolvedValueOnce({ data: { member: lastAppointments } })
  return mount(TestAddModal, {
    props: { treatment: '/api/treatments/10', drugId: 1, ...overrides }
  })
}

describe('TestAddModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  describe('validateForm', () => {
    it('requires MNO and dose', () => {
      const wrapper = mountTestAddModal()
      expect(wrapper.vm.validateForm()).toBe(true)
      expect(wrapper.vm.fieldErrors.mno).toBe('МНО обязательно')
      expect(wrapper.vm.fieldErrors.doze).toBe('Доза обязательна')
    })

    it('rejects an MNO outside the 0.8-10.0 range', () => {
      const wrapper = mountTestAddModal()
      wrapper.vm.mno = 0.5
      wrapper.vm.doze = 1
      expect(wrapper.vm.validateForm()).toBe(true)
      expect(wrapper.vm.fieldErrors.mno).toContain('0.8–10.0')
    })

    it('rejects a dose not a multiple of 0.25', () => {
      const wrapper = mountTestAddModal()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 1.1
      expect(wrapper.vm.validateForm()).toBe(true)
      expect(wrapper.vm.fieldErrors.doze).toContain('0.25')
    })

    it('passes for valid data', () => {
      const wrapper = mountTestAddModal()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 1.5
      expect(wrapper.vm.validateForm()).toBe(false)
      expect(wrapper.vm.fieldErrors).toEqual({})
    })
  })

  describe('save', () => {
    it('does not call the API when invalid', async () => {
      const wrapper = mountTestAddModal()
      await wrapper.vm.save()
      expect(apiClient.post).not.toHaveBeenCalled()
    })

    it('posts the test history entry and emits "saved"', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountTestAddModal()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 1.5

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/test_histories', expect.objectContaining({
        treatment: '/api/treatments/10',
        mno: 2.5,
        doze: 1.5,
        drug: '/api/drugs/1',
      }))
      expect(wrapper.emitted('saved')).toBeTruthy()
    })

    it('sends the final comment (patient message) on save', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountTestAddModal()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 1.5
      wrapper.vm.comment = 'Ваше МНО - 2.5. Дозировку варфарина оставьте прежней'

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/test_histories', expect.objectContaining({
        comment: 'Ваше МНО - 2.5. Дозировку варфарина оставьте прежней'
      }))
    })

    it('sets a save error when the request fails', async () => {
      apiClient.post.mockRejectedValue(new Error('boom'))
      const wrapper = mountTestAddModal()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 1.5

      await wrapper.vm.save()

      expect(wrapper.vm.saveError).toBe('Не удалось сохранить анализ.')
      expect(wrapper.emitted('saved')).toBeFalsy()
    })
  })

  describe('autofill', () => {
    it('autofills the patient message on mno input (debounced)', async () => {
      apiClient.post.mockResolvedValue({ data: { body: 'Ваше МНО - 2.3. Дозировку варфарина оставьте прежней' } })
      const wrapper = mountTestAddModal({ drugGenitive: 'варфарина' })

      wrapper.vm.mno = 2.3
      await vi.advanceTimersByTimeAsync(300)

      expect(apiClient.post).toHaveBeenCalledWith('/notification_templates/resolve', {
        code: 'analysis_result',
        data: { mno: 2.3, drug_genitive: 'варфарина' }
      })
      expect(wrapper.vm.comment).toBe('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней')
    })

    it('does not overwrite the message after a manual edit (dirty)', async () => {
      apiClient.post.mockResolvedValue({ data: { body: 'Ваше МНО - 2.3. Дозировку варфарина оставьте прежней' } })
      const wrapper = mountTestAddModal({ drugGenitive: 'варфарина' })

      wrapper.vm.mno = 2.3
      await vi.advanceTimersByTimeAsync(300)
      expect(wrapper.vm.comment).toContain('Ваше МНО')

      wrapper.vm.markCommentDirty()
      wrapper.vm.comment = 'Ручная правка врача'

      wrapper.vm.mno = 3.0
      await vi.advanceTimersByTimeAsync(300)

      expect(wrapper.vm.comment).toBe('Ручная правка врача')
    })

    it('does not autofill for empty mno', async () => {
      const wrapper = mountTestAddModal({ drugGenitive: 'варфарина' })

      wrapper.vm.mno = null
      await vi.advanceTimersByTimeAsync(300)

      expect(apiClient.post).not.toHaveBeenCalled()
    })
  })

  it('labels the field «Сообщение пациенту» (not «Комментарий»)', () => {
    const wrapper = mountTestAddModal()
    expect(wrapper.text()).toContain('Сообщение пациенту')
    expect(wrapper.text()).not.toContain('Комментарий')
  })

  describe('alternation and autofill', () => {
    it('prefills the dose and alternation from the last appointment (СЦ-3.65.8)', async () => {
      const wrapper = mountTestAddModal([{ doze: 2.5, doze2: 2.75 }])
      await flushPromises()

      expect(wrapper.vm.doze).toBe(2.5)
      expect(wrapper.vm.enableAlternation).toBe(true)
      expect(wrapper.vm.dose2).toBe(2.75)
    })

    it('leaves the dose empty when there is no appointment (СЦ-3.65.9)', async () => {
      const wrapper = mountTestAddModal([])
      await flushPromises()

      expect(wrapper.vm.doze).toBeNull()
      expect(wrapper.vm.enableAlternation).toBe(false)
    })

    it('sends doze2 with the test history when alternation is enabled', async () => {
      apiClient.post.mockResolvedValue({})
      const wrapper = mountTestAddModal([])
      await flushPromises()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 2.5
      wrapper.vm.enableAlternation = true
      wrapper.vm.alternationDelta = '0.25'

      await wrapper.vm.save()

      expect(apiClient.post).toHaveBeenCalledWith('/test_histories', expect.objectContaining({ doze: 2.5, doze2: 2.75 }))
    })

    it('rejects when alternation is enabled but no deviation is selected', async () => {
      const wrapper = mountTestAddModal([])
      await flushPromises()
      wrapper.vm.mno = 2.5
      wrapper.vm.doze = 2.5
      wrapper.vm.enableAlternation = true
      wrapper.vm.alternationDelta = null

      expect(wrapper.vm.validateForm()).toBe(true)
      expect(wrapper.vm.fieldErrors.alternationDelta).toContain('отклонение')
    })
  })
})
