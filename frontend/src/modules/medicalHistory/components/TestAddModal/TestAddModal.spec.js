import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TestAddModal from './TestAddModal.vue'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({ default: { post: vi.fn(), get: vi.fn() } }))

function mountTestAddModal(lastAppointments = []) {
  apiClient.get.mockResolvedValueOnce({ data: { member: lastAppointments } })
  return mount(TestAddModal, { props: { treatment: '/api/treatments/10', drugId: 1 } })
}

describe('TestAddModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
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
