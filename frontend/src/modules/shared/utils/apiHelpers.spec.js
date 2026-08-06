import { describe, it, expect } from 'vitest'
import { extractData, extractIdFromIri, createPaginationParams, getIdFromIri } from './apiHelpers'

describe('extractData', () => {
  it('unwraps a Hydra/JSON-LD collection response', () => {
    const response = { data: { member: [{ id: 1 }, { id: 2 }], totalItems: 2, view: { next: '/api/patients?page=2' } } }
    expect(extractData(response)).toEqual({
      items: [{ id: 1 }, { id: 2 }],
      totalItems: 2,
      view: { next: '/api/patients?page=2' },
      next: '/api/patients?page=2'
    })
  })

  it('returns the raw data unchanged when there is no "member" key', () => {
    const response = { data: { id: 1, name: 'Больница' } }
    expect(extractData(response)).toEqual({ id: 1, name: 'Больница' })
  })

  it('defaults next to null when there is no view', () => {
    const response = { data: { member: [] } }
    expect(extractData(response).next).toBeNull()
  })
})

describe('extractIdFromIri', () => {
  it('extracts the trailing numeric id from an IRI', () => {
    expect(extractIdFromIri('/api/hospitals/42')).toBe(42)
  })

  it('returns null for falsy input', () => {
    expect(extractIdFromIri(null)).toBeNull()
    expect(extractIdFromIri('')).toBeNull()
  })

  it('returns null when the IRI has no trailing number', () => {
    expect(extractIdFromIri('/api/hospitals/')).toBeNull()
  })
})

describe('createPaginationParams', () => {
  it('builds params with defaults', () => {
    expect(createPaginationParams()).toEqual({ page: 1, itemsPerPage: 30 })
  })

  it('merges extra filters into the params', () => {
    expect(createPaginationParams(2, 10, { lastname: 'Ivanov' })).toEqual({
      page: 2,
      itemsPerPage: 10,
      lastname: 'Ivanov'
    })
  })
})

describe('getIdFromIri', () => {
  it('parses the trailing segment as an integer', () => {
    expect(getIdFromIri('/api/patients/7')).toBe(7)
  })

  it('returns null for falsy input', () => {
    expect(getIdFromIri(null)).toBeNull()
    expect(getIdFromIri('')).toBeNull()
  })
})
