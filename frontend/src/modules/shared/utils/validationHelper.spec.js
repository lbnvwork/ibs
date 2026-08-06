import { describe, it, expect } from 'vitest'
import { validateForm } from './validationHelper'

describe('validateForm', () => {
  it('reports missing required fields', () => {
    const errors = validateForm(
      { address: '' },
      { address: { required: true, message: 'Адрес обязателен' } }
    )
    expect(errors).toEqual({ address: 'Адрес обязателен' })
  })

  it('treats null and undefined as missing for required fields', () => {
    const rules = { phone: { required: true, message: 'Телефон обязателен' } }
    expect(validateForm({ phone: null }, rules)).toEqual({ phone: 'Телефон обязателен' })
    expect(validateForm({ phone: undefined }, rules)).toEqual({ phone: 'Телефон обязателен' })
  })

  it('runs a custom validator and reports its error message on failure', () => {
    const rules = {
      email: { validator: (v) => v.includes('@'), errorMsg: 'Неверный формат email' }
    }
    expect(validateForm({ email: 'invalid' }, rules)).toEqual({ email: 'Неверный формат email' })
    expect(validateForm({ email: 'a@b.com' }, rules)).toEqual({})
  })

  it('runs the validator even on an empty, non-required field (validators must handle emptiness themselves)', () => {
    const rules = {
      email: { validator: (v) => v.includes('@'), errorMsg: 'Неверный формат email' }
    }
    expect(validateForm({ email: '' }, rules)).toEqual({ email: 'Неверный формат email' })
  })

  it('passes for an empty optional field when the validator itself treats empty as valid', () => {
    const rules = {
      email: { validator: (v) => !v || v.includes('@'), errorMsg: 'Неверный формат email' }
    }
    expect(validateForm({ email: '' }, rules)).toEqual({})
  })

  it('returns no errors when everything is valid', () => {
    const rules = {
      address: { required: true, message: 'Адрес обязателен' },
      phone: { required: true, validator: (v) => v.length === 11, errorMsg: 'Длина 11' }
    }
    expect(validateForm({ address: 'ул. Ленина', phone: '89001234567' }, rules)).toEqual({})
  })

  it('invokes extraChecks with the accumulated errors and raw data', () => {
    const rules = {}
    const extraChecks = (errors, data) => {
      if (data.mnoFrom >= data.mnoTo) {
        errors.mnoRange = 'Нижняя граница должна быть меньше верхней'
      }
    }
    const errors = validateForm({ mnoFrom: 3, mnoTo: 2 }, rules, extraChecks)
    expect(errors).toEqual({ mnoRange: 'Нижняя граница должна быть меньше верхней' })
  })
})
