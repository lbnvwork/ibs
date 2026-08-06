import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Pharmacogenetics from './Pharmacogenetics.vue'
import { pharmacogeneticsApi } from '@/modules/shared/api/pharmacogenetics'

vi.mock('@/modules/shared/api/pharmacogenetics', () => ({
  pharmacogeneticsApi: {
    getForPatient: vi.fn(),
    deleteResult: vi.fn(),
    updateResult: vi.fn(),
    createResult: vi.fn(),
  }
}))

function mountPharmacogenetics(props = {}) {
  setActivePinia(createPinia())
  pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [] } })
  return mount(Pharmacogenetics, { props: { patientId: '7', ...props } })
}

describe('Pharmacogenetics.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches pharmacogenetics data for the patient on mount', async () => {
    mountPharmacogenetics({ drugIri: '/api/drugs/1' })
    await flushPromises()

    expect(pharmacogeneticsApi.getForPatient).toHaveBeenCalledWith('7', { params: { drug: '/api/drugs/1' } })
  })

  it('re-fetches when patientId changes', async () => {
    const wrapper = mountPharmacogenetics()
    await flushPromises()
    vi.clearAllMocks()
    pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [] } })

    await wrapper.setProps({ patientId: '9' })
    await flushPromises()

    expect(pharmacogeneticsApi.getForPatient).toHaveBeenCalledWith('9', { params: {} })
  })

  it('re-fetches when drugIri changes', async () => {
    const wrapper = mountPharmacogenetics()
    await flushPromises()
    vi.clearAllMocks()
    pharmacogeneticsApi.getForPatient.mockResolvedValue({ data: { markers: [] } })

    await wrapper.setProps({ drugIri: '/api/drugs/2' })
    await flushPromises()

    expect(pharmacogeneticsApi.getForPatient).toHaveBeenCalledWith('7', { params: { drug: '/api/drugs/2' } })
  })

  it('delegates getGenotypeLabel to the store', async () => {
    const wrapper = mountPharmacogenetics()
    await flushPromises()
    wrapper.vm.store.getGenotypeLabel = vi.fn().mockReturnValue('CYP2C9 *1/*1')

    expect(wrapper.vm.getGenotypeLabel('CYP2C9')).toBe('CYP2C9 *1/*1')
    expect(wrapper.vm.store.getGenotypeLabel).toHaveBeenCalledWith('CYP2C9')
  })
})
