import { describe, it, expect, vi, beforeEach } from 'vitest'
import { defineComponent } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { useDrugTabs } from './useDrugTabs'
import { drugApi } from '@/modules/shared/api/drug'

vi.mock('@/modules/shared/api/drug', () => ({
  drugApi: { getAll: vi.fn() }
}))

function mountDrugTabs() {
  const TestComponent = defineComponent({
    setup() {
      return useDrugTabs()
    },
    template: '<div></div>'
  })
  return mount(TestComponent)
}

describe('useDrugTabs', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('capitalizes the first letter and lowercases the rest of each drug name', async () => {
    drugApi.getAll.mockResolvedValue({ member: [{ id: 1, nominative: 'ВАРФАРИН' }, { id: 2, nominative: 'ривароксабан' }] })

    const wrapper = mountDrugTabs()
    await flushPromises()

    expect(wrapper.vm.tabs).toEqual([
      { id: 1, name: 'Варфарин', style: '' },
      { id: 2, name: 'Ривароксабан', style: '' }
    ])
  })

  it('falls back to an empty array on API failure', async () => {
    drugApi.getAll.mockRejectedValue(new Error('network error'))

    const wrapper = mountDrugTabs()
    await flushPromises()

    expect(wrapper.vm.tabs).toEqual([])
  })

  it('supports responses shaped as { items } instead of { member }', async () => {
    drugApi.getAll.mockResolvedValue({ items: [{ id: 3, nominative: 'аспирин' }] })

    const wrapper = mountDrugTabs()
    await flushPromises()

    expect(wrapper.vm.tabs).toEqual([{ id: 3, name: 'Аспирин', style: '' }])
  })
})
