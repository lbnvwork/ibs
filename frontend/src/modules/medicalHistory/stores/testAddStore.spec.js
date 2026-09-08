import { describe, it, expect, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useTestAddStore } from './testAddStore'

describe('testAddStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('starts closed', () => {
    const store = useTestAddStore()
    expect(store.isModalOpen).toBe(false)
  })

  it('openModal sets isModalOpen to true', () => {
    const store = useTestAddStore()
    store.openModal()
    expect(store.isModalOpen).toBe(true)
  })

  it('closeModal sets isModalOpen to false', () => {
    const store = useTestAddStore()
    store.openModal()
    store.closeModal()
    expect(store.isModalOpen).toBe(false)
  })
})
