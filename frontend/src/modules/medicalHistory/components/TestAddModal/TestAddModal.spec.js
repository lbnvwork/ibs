import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TestAddModal from './TestAddModal.vue'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({ default: { post: vi.fn() } }))

function mountTestAddModal(overrides = {}) {
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
})
