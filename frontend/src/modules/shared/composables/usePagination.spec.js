import { describe, it, expect, vi } from 'vitest'
import { reactive } from 'vue'
import { usePagination } from './usePagination'

function createStore(overrides = {}) {
  return reactive({
    currentPage: 1,
    totalPages: 5,
    setPage: vi.fn(),
    ...overrides
  })
}

describe('usePagination', () => {
  it('initializes pageInput from the store current page', () => {
    const store = createStore({ currentPage: 3 })
    const { pageInput } = usePagination(store)
    expect(pageInput.value).toBe(3)
  })

  it('calls store.setPage with the parsed page number', () => {
    const store = createStore()
    const { goToPage } = usePagination(store)
    goToPage('3')
    expect(store.setPage).toHaveBeenCalledWith(3)
  })

  it('clamps a page number above totalPages down to totalPages', () => {
    const store = createStore({ totalPages: 5 })
    const { goToPage } = usePagination(store)
    goToPage(999)
    expect(store.setPage).toHaveBeenCalledWith(5)
  })

  it('clamps a page number below 1 up to 1', () => {
    const store = createStore({ currentPage: 3 })
    const { goToPage } = usePagination(store)
    goToPage(0)
    expect(store.setPage).toHaveBeenCalledWith(1)
  })

  it('defaults to page 1 for a non-numeric input', () => {
    const store = createStore({ currentPage: 2 })
    const { goToPage } = usePagination(store)
    goToPage('not-a-number')
    expect(store.setPage).toHaveBeenCalledWith(1)
  })

  it('does not call setPage when the target page equals the current page', () => {
    const store = createStore({ currentPage: 2 })
    const { goToPage } = usePagination(store)
    goToPage(2)
    expect(store.setPage).not.toHaveBeenCalled()
  })

  it('updates pageInput reactively when the store current page changes elsewhere', async () => {
    const store = createStore({ currentPage: 1 })
    const { pageInput } = usePagination(store)
    store.currentPage = 4
    await vi.waitFor(() => expect(pageInput.value).toBe(4))
  })
})
