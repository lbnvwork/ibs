import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import MultiDiagnosisSelect from './MultiDiagnosisSelect.vue'
import { mkb10Api } from '@/modules/shared/api/mkb10'

vi.mock('@/modules/shared/api/mkb10', () => ({
  mkb10Api: { getPopular: vi.fn(), search: vi.fn() }
}))

describe('MultiDiagnosisSelect.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mkb10Api.getPopular.mockResolvedValue([{ id: 1, mkbCode: 'I80', mkbName: 'Флебит и тромбофлебит' }])
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('loads popular diagnoses as the initial option list', async () => {
    const wrapper = mount(MultiDiagnosisSelect)
    await flushPromises()

    expect(mkb10Api.getPopular).toHaveBeenCalled()
    expect(wrapper.vm.options).toEqual([{ id: 1, mkbCode: 'I80', mkbName: 'Флебит и тромбофлебит' }])
  })

  it('syncs selectedCodes from the modelValue prop', async () => {
    const wrapper = mount(MultiDiagnosisSelect, { props: { modelValue: ['I80', 'K29'] } })
    await flushPromises()

    expect(wrapper.vm.selectedCodes).toEqual(['I80', 'K29'])
  })

  it('emits update:modelValue when a diagnosis is removed', async () => {
    const wrapper = mount(MultiDiagnosisSelect, { props: { modelValue: ['I80', 'K29'] } })
    await flushPromises()

    wrapper.vm.removeDiagnosis('I80')
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([['K29']])
  })

  it('debounces the search and normalizes snake_case API fields', async () => {
    vi.useFakeTimers()
    mkb10Api.search.mockResolvedValue([{ id: 2, mkb_code: 'J45', mkb_name: 'Астма' }])

    const wrapper = mount(MultiDiagnosisSelect)
    await flushPromises()

    wrapper.vm.search = 'аст'
    wrapper.vm.onSearchInput()
    expect(mkb10Api.search).not.toHaveBeenCalled()

    vi.advanceTimersByTime(300)
    await flushPromises()

    expect(mkb10Api.search).toHaveBeenCalledWith('аст')
    expect(wrapper.vm.options).toEqual([{ id: 2, mkbCode: 'J45', mkbName: 'Астма' }])
  })

  it('reverts to the popular list when the search is cleared', async () => {
    const wrapper = mount(MultiDiagnosisSelect)
    await flushPromises()

    wrapper.vm.search = ''
    await wrapper.vm.doSearch()

    expect(wrapper.vm.options).toEqual(wrapper.vm.popular)
  })
})
