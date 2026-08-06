import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDrugGroupStore } from './drugGroupStore'
import apiClient from '@/modules/shared/api/client'

vi.mock('@/modules/shared/api/client', () => ({
  default: { get: vi.fn() }
}))

describe('drugGroupStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('loads drug groups from a Hydra "member" response', async () => {
    apiClient.get.mockResolvedValue({ data: { member: [{ id: 1, name: 'anticoagulants', fullName: 'Антикоагулянты' }] } })

    const store = useDrugGroupStore()
    await store.loadDrugGroups()

    expect(store.drugGroups).toEqual([{ id: 1, name: 'anticoagulants', fullName: 'Антикоагулянты' }])
  })

  it('accepts a plain array response too', async () => {
    apiClient.get.mockResolvedValue({ data: [{ id: 2, name: 'other' }] })

    const store = useDrugGroupStore()
    await store.loadDrugGroups()

    expect(store.drugGroups).toEqual([{ id: 2, name: 'other' }])
  })

  it('defaults to an empty array when the response is not an array', async () => {
    apiClient.get.mockResolvedValue({ data: null })

    const store = useDrugGroupStore()
    await store.loadDrugGroups()

    expect(store.drugGroups).toEqual([])
  })

  it('records the error message on failure', async () => {
    apiClient.get.mockRejectedValue(new Error('boom'))

    const store = useDrugGroupStore()
    await store.loadDrugGroups()

    expect(store.error).toBe('boom')
  })

  it('drugGroupOptions prepends "all categories" and prefers fullName over name', () => {
    const store = useDrugGroupStore()
    store.drugGroups = [
      { id: 1, name: 'short', fullName: 'Полное название' },
      { id: 2, name: 'onlyname' }
    ]

    expect(store.drugGroupOptions).toEqual([
      { value: '', label: 'Все категории' },
      { value: 1, label: 'Полное название' },
      { value: 2, label: 'onlyname' }
    ])
  })
})
