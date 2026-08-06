import { describe, it, expect } from 'vitest'
import { parseApiError } from './apiErrorHandler'

describe('parseApiError', () => {
  it('returns violations when the API responds with a validation problem', () => {
    const err = { response: { data: { violations: [{ propertyPath: 'lastname', message: 'required' }] } } }
    expect(parseApiError(err)).toEqual({ violations: err.response.data.violations })
  })

  it('translates a NULL constraint detail into a friendly Russian message', () => {
    const err = { response: { data: { detail: 'null value in column violates NOT NULL constraint' } } }
    expect(parseApiError(err)).toEqual({ generalError: 'Это значение не должно быть пустым.' })
  })

  it('passes through a non-NULL detail message unchanged', () => {
    const err = { response: { data: { detail: 'Лечение не активно.' } } }
    expect(parseApiError(err)).toEqual({ generalError: 'Лечение не активно.' })
  })

  it('falls back to a generic message when there is no usable response body', () => {
    expect(parseApiError({})).toEqual({ generalError: 'Ошибка сервера' })
    expect(parseApiError({ response: {} })).toEqual({ generalError: 'Ошибка сервера' })
  })
})
