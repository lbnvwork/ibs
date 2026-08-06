import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TestAddModal from './TestAddModal.vue'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({ default: { post: vi.fn() } }))

function mountTestAddModal() {
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
})
